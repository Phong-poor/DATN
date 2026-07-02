<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeController extends Controller
{
    private function normalizeQuery($q)
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($q)));
    }

    private function getProviders()
    {
        return [
            ['name' => 'mapbox', 'key' => config('services.mapbox.key')],
            ['name' => 'tomtom', 'key' => config('services.tomtom.key')],
            ['name' => 'here', 'key' => config('services.here.key')],
            ['name' => 'nominatim', 'key' => true], // fallback
        ];
    }

    private function safeLogWarning($type, $provider, $status, $msg, $hashData)
    {
        $logData = [
            'provider' => $provider,
            'status' => $status,
            'message' => $msg,
        ];

        if ($type === 'reverse') {
            $logData['coordinate_hash'] = $hashData;
            Log::warning('Reverse geocode provider failed', $logData);
        } else {
            $logData['query_hash'] = $hashData;
            Log::warning('Geocode provider failed', $logData);
        }
    }

    private function parseProvinceDistrictWard($provider, $raw)
    {
        $province = null;
        $district = null;
        $ward = null;

        if ($provider === 'mapbox') {
            $context = $raw['context'] ?? [];
            foreach ($context as $c) {
                $id = $c['id'] ?? '';
                $text = $c['text'] ?? '';
                if (str_starts_with($id, 'region')) {
                    $province = $text;
                }
                if (str_starts_with($id, 'place')) {
                    $district = $text;
                }
                if (str_starts_with($id, 'locality')) {
                    $ward = $text;
                }
            }
        } elseif ($provider === 'tomtom') {
            $addr = $raw['address'] ?? [];
            $province = $addr['countrySubdivision'] ?? null;
            $district = $addr['municipality'] ?? null;
            $ward = $addr['countrySecondarySubdivision'] ?? null;
        } elseif ($provider === 'here') {
            $addr = $raw['address'] ?? [];
            $province = $addr['state'] ?? null;
            $district = $addr['city'] ?? $addr['county'] ?? null;
            $ward = $addr['district'] ?? null;
        } elseif ($provider === 'nominatim') {
            $addr = $raw['address'] ?? [];
            $province = $addr['state'] ?? $addr['city'] ?? $addr['province'] ?? null;
            $district = $addr['county'] ?? $addr['district'] ?? $addr['town'] ?? null;
            $ward = $addr['suburb'] ?? $addr['village'] ?? $addr['neighbourhood'] ?? null;
        }

        return [$province, $district, $ward];
    }

    public function suggestions(Request $request)
    {
        $q = $request->input('q');
        $reqProvince = $request->input('province');
        $reqWard = $request->input('ward');
        if (! $q || mb_strlen($q) < 3) {
            return response()->json([]);
        }

        $normalizedQuery = $this->normalizeQuery($q);
        $queryHash = md5($normalizedQuery.'|'.$reqProvince.'|'.$reqWard);
        $cacheKey = "address:suggestions:{$queryHash}";

        $result = Cache::remember($cacheKey, now()->addDays(7), function () use ($normalizedQuery, $queryHash, $reqProvince, $reqWard, $q) {
            $limit = 5;

            foreach ($this->getProviders() as $p) {
                if (! $p['key']) {
                    continue;
                }
                $provider = $p['name'];

                try {
                    $response = null;
                    if ($provider === 'mapbox') {
                        $response = Http::withoutVerifying()->timeout(5)->get('https://api.mapbox.com/geocoding/v5/mapbox.places/'.urlencode($normalizedQuery).'.json', [
                            'access_token' => $p['key'],
                            'country' => 'vn',
                            'limit' => $limit,
                            'types' => 'address,poi',
                        ]);
                    } elseif ($provider === 'tomtom') {
                        $response = Http::withoutVerifying()->timeout(5)->get('https://api.tomtom.com/search/2/geocode/'.urlencode($normalizedQuery).'.json', [
                            'key' => $p['key'],
                            'countrySet' => 'VN',
                            'limit' => $limit,
                        ]);
                    } elseif ($provider === 'here') {
                        $response = Http::withoutVerifying()->timeout(5)->get('https://geocode.search.hereapi.com/v1/geocode', [
                            'q' => $normalizedQuery,
                            'apiKey' => $p['key'],
                            'in' => 'countryCode:VNM',
                            'limit' => $limit,
                        ]);
                    } elseif ($provider === 'nominatim') {
                        $response = Http::withoutVerifying()->timeout(15)->withHeaders([
                            'User-Agent' => 'DATN-App/1.0 (contact@example.com)',
                        ])->get('https://nominatim.openstreetmap.org/search', [
                            'format' => 'jsonv2',
                            'limit' => $limit,
                            'countrycodes' => 'vn',
                            'addressdetails' => 1,
                            'q' => $normalizedQuery,
                        ]);
                    }

                    if ($response && $response->successful()) {
                        $mapped = [];
                        if ($provider === 'mapbox') {
                            $features = $response->json('features') ?? [];
                            $mapped = collect($features)->map(function ($f) use ($provider) {
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $f);

                                return [
                                    'id' => (string) ($f['id'] ?? uniqid()),
                                    'provider' => $provider,
                                    'title' => $f['text'] ?? null,
                                    'subtitle' => str_replace(($f['text'] ?? '').', ', '', $f['place_name'] ?? '') ?: null,
                                    'display_name' => $f['place_name'] ?? null,
                                    'lat' => $f['center'][1] ?? null,
                                    'lng' => $f['center'][0] ?? null,
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'raw' => $f,
                                ];
                            })->toArray();
                        } elseif ($provider === 'tomtom') {
                            $results = $response->json('results') ?? [];
                            $mapped = collect($results)->map(function ($r) use ($provider) {
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $r);
                                $addr = $r['address'] ?? [];
                                $freeform = $addr['freeformAddress'] ?? $addr['streetName'] ?? $addr['municipality'] ?? $addr['countrySubdivisionName'] ?? null;

                                $titleParts = [];
                                if (! empty($addr['streetNumber'])) {
                                    $titleParts[] = $addr['streetNumber'];
                                }
                                if (! empty($addr['streetName'])) {
                                    $titleParts[] = $addr['streetName'];
                                }
                                $title = ! empty($titleParts) ? implode(' ', $titleParts) : $freeform;

                                $subtitleParts = [];
                                if (! empty($addr['municipalitySubdivision'])) {
                                    $subtitleParts[] = $addr['municipalitySubdivision'];
                                }
                                if (! empty($addr['municipality'])) {
                                    $subtitleParts[] = $addr['municipality'];
                                }
                                if (! empty($addr['countrySubdivisionName'])) {
                                    $subtitleParts[] = $addr['countrySubdivisionName'];
                                }
                                if (! empty($addr['country'])) {
                                    $subtitleParts[] = $addr['country'];
                                }
                                $subtitle = ! empty($subtitleParts) ? implode(', ', array_unique($subtitleParts)) : $freeform;

                                return [
                                    'id' => (string) ($r['id'] ?? uniqid()),
                                    'provider' => $provider,
                                    'title' => $title,
                                    'subtitle' => $subtitle,
                                    'display_name' => $title.', '.$subtitle,
                                    'lat' => $r['position']['lat'] ?? null,
                                    'lng' => $r['position']['lon'] ?? null,
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'raw' => $r,
                                ];
                            })->toArray();
                        } elseif ($provider === 'here') {
                            $items = $response->json('items') ?? [];
                            $mapped = collect($items)->map(function ($i) use ($provider) {
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $i);
                                $title = explode(',', $i['title'] ?? '')[0] ?? null;

                                return [
                                    'id' => (string) ($i['id'] ?? uniqid()),
                                    'provider' => $provider,
                                    'title' => $title,
                                    'subtitle' => $i['title'] ?? null,
                                    'display_name' => $i['title'] ?? null,
                                    'lat' => $i['position']['lat'] ?? null,
                                    'lng' => $i['position']['lng'] ?? null,
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'raw' => $i,
                                ];
                            })->toArray();
                        } elseif ($provider === 'nominatim') {
                            $data = $response->json() ?? [];
                            $mapped = collect($data)->map(function ($d) use ($provider) {
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $d);
                                $title = $d['name'] ?: (explode(',', $d['display_name'] ?? '')[0] ?? null);

                                return [
                                    'id' => (string) ($d['place_id'] ?? uniqid()),
                                    'provider' => $provider,
                                    'title' => $title,
                                    'subtitle' => $d['display_name'] ?? null,
                                    'display_name' => $d['display_name'] ?? null,
                                    'lat' => is_numeric($d['lat'] ?? null) ? (float) $d['lat'] : null,
                                    'lng' => is_numeric($d['lon'] ?? null) ? (float) $d['lon'] : null,
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'raw' => $d,
                                ];
                            })->toArray();
                        }

                        if (count($mapped) > 0) {
                            $mapped = collect($mapped)->filter(function ($item) {
                                return (! empty($item['title']) || ! empty($item['display_name'])) && ! empty($item['lat']) && ! empty($item['lng']);
                            })->map(function ($item) use ($reqProvince, $reqWard, $q) {
                                $score = 0;
                                if (isset($item['raw']['address']['streetNumber'])) {
                                    $score += 1;
                                }
                                if (isset($item['raw']['address']['streetName'])) {
                                    $score += 1;
                                }

                                if (! empty($reqProvince) && ! empty($item['province'])) {
                                    if (stripos($this->normalizeQuery($item['province']), $this->normalizeQuery($reqProvince)) !== false ||
                                        stripos($this->normalizeQuery($reqProvince), $this->normalizeQuery($item['province'])) !== false) {
                                        $score += 2;
                                    }
                                }

                                if (! empty($reqWard) && ! empty($item['ward'])) {
                                    if (stripos($this->normalizeQuery($item['ward']), $this->normalizeQuery($reqWard)) !== false ||
                                        stripos($this->normalizeQuery($reqWard), $this->normalizeQuery($item['ward'])) !== false) {
                                        $score += 2;
                                    }
                                }

                                $fullText = ($item['title'] ?? '').' '.($item['subtitle'] ?? '').' '.($item['display_name'] ?? '');
                                if (stripos($this->normalizeQuery($fullText), $this->normalizeQuery($q)) !== false) {
                                    $score += 1;
                                }

                                $item['score'] = $score;

                                return $item;
                            })->sortByDesc('score')->values()->toArray();

                            if (count($mapped) > 0) {
                                return $mapped;
                            }
                        }
                    } else {
                        $this->safeLogWarning('geocode', $provider, $response ? $response->status() : null, 'HTTP error', $queryHash);
                    }
                } catch (\Throwable $e) {
                    $this->safeLogWarning('geocode', $provider, null, 'Exception occurred: '.$e->getMessage(), $queryHash);
                }
            }

            return [];
        });

        return response()->json(is_array($result) ? $result : []);
    }

    public function geocode(Request $request)
    {
        $q = $request->input('q');
        if (! $q || mb_strlen($q) < 3) {
            return response()->json([
                'success' => false,
                'lat' => null,
                'lng' => null,
                'accuracy_level' => 'failed',
                'message' => 'Địa chỉ tìm kiếm quá ngắn hoặc không hợp lệ.',
                'provider' => null,
                'source' => null,
            ]);
        }

        $normalizedQuery = $this->normalizeQuery($q);
        $queryHash = md5($normalizedQuery);
        $cacheKey = "address:geocode:{$queryHash}";

        // Manual cache check to avoid caching failed results
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult) {
            return response()->json($cachedResult);
        }

        $tomtomKey = config('services.tomtom.key');
        if (! $tomtomKey) {
            return response()->json([
                'success' => false,
                'lat' => null,
                'lng' => null,
                'accuracy_level' => 'failed',
                'message' => 'Lỗi cấu hình hệ thống bản đồ.',
                'provider' => null,
                'source' => null,
            ]);
        }

        $runTomTomGeocode = function ($searchQuery) use ($tomtomKey) {
            try {
                $response = Http::withoutVerifying()->timeout(2)->get('https://api.tomtom.com/search/2/geocode/'.urlencode($searchQuery).'.json', [
                    'key' => $tomtomKey,
                    'countrySet' => 'VN',
                    'limit' => 1,
                    'language' => 'vi-VN',
                ]);

                if ($response && $response->successful()) {
                    $results = $response->json('results') ?? [];
                    if (count($results) > 0) {
                        $r = $results[0];
                        [$province, $district, $ward] = $this->parseProvinceDistrictWard('tomtom', $r);
                        $addr = $r['address'] ?? [];
                        $freeform = $addr['freeformAddress'] ?? $addr['streetName'] ?? null;

                        return [
                            'success' => true,
                            'provider' => 'tomtom',
                            'source' => 'tomtom',
                            'display_name' => $freeform,
                            'lat' => $r['position']['lat'] ?? null,
                            'lng' => $r['position']['lon'] ?? null,
                            'province' => $province,
                            'district' => $district,
                            'ward' => $ward,
                            'geojson' => null,
                            'boundingbox' => null,
                            'raw' => $r,
                        ];
                    }
                } else {
                    $this->safeLogWarning('geocode', 'tomtom', $response ? $response->status() : null, 'HTTP error', md5($searchQuery));
                }
            } catch (\Exception $e) {
                $this->safeLogWarning('geocode', 'tomtom', null, 'Exception occurred: '.$e->getMessage(), md5($searchQuery));
            }

            return null;
        };

        // Lần 1: Thử với query đầy đủ
        $resObj = $runTomTomGeocode($normalizedQuery);
        if ($resObj) {
            $resObj['accuracy_level'] = 'exact';
            $resObj['message'] = 'Tìm thấy địa chỉ chính xác.';
            Cache::put($cacheKey, $resObj, now()->addDays(7));

            return response()->json($resObj);
        }

        // Lần 2: Fallback rút gọn query (cắt bỏ phần đầu tiên trước dấu phẩy)
        $parts = explode(',', $q);
        if (count($parts) > 1) {
            array_shift($parts); // Vứt bỏ phần chi tiết số nhà/ngõ
            $fallbackQuery = trim(implode(',', $parts));
            $fallbackQuery = $this->normalizeQuery($fallbackQuery);

            if ($fallbackQuery && mb_strlen($fallbackQuery) > 3) {
                $resObj = $runTomTomGeocode($fallbackQuery);
                if ($resObj) {
                    $resObj['accuracy_level'] = 'approximate';
                    $resObj['message'] = 'Không tìm thấy vị trí cụ thể, đã chuyển về khu vực lân cận.';
                    Cache::put($cacheKey, $resObj, now()->addDays(7));

                    return response()->json($resObj);
                }
            }
        }

        // Failed: Không cache kết quả này để tránh lưu cache xấu
        return response()->json([
            'success' => false,
            'lat' => null,
            'lng' => null,
            'accuracy_level' => 'failed',
            'message' => 'Không thể tìm thấy tọa độ cho địa chỉ này trên bản đồ.',
            'provider' => null,
            'source' => null,
        ]);
    }

    public function reverse(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if (! is_numeric($lat) || ! is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return response()->json(null);
        }

        $rLat = round((float) $lat, 6);
        $rLng = round((float) $lng, 6);
        $coordHash = md5($rLat.','.$rLng);
        $cacheKey = "address:reverse:{$coordHash}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($rLat, $rLng, $coordHash) {
            foreach ($this->getProviders() as $p) {
                if (! $p['key']) {
                    continue;
                }
                $provider = $p['name'];

                try {
                    $response = null;
                    if ($provider === 'mapbox') {
                        $response = Http::withoutVerifying()->timeout(5)->get("https://api.mapbox.com/geocoding/v5/mapbox.places/{$rLng},{$rLat}.json", [
                            'access_token' => $p['key'],
                            'limit' => 1,
                        ]);
                    } elseif ($provider === 'tomtom') {
                        $response = Http::withoutVerifying()->timeout(5)->get("https://api.tomtom.com/search/2/reverseGeocode/{$rLat},{$rLng}.json", [
                            'key' => $p['key'],
                        ]);
                    } elseif ($provider === 'here') {
                        $response = Http::withoutVerifying()->timeout(5)->get('https://revgeocode.search.hereapi.com/v1/revgeocode', [
                            'at' => "{$rLat},{$rLng}",
                            'apiKey' => $p['key'],
                            'limit' => 1,
                        ]);
                    } elseif ($provider === 'nominatim') {
                        $response = Http::withoutVerifying()->timeout(15)->withHeaders([
                            'User-Agent' => 'DATN-App/1.0 (contact@example.com)',
                        ])->get('https://nominatim.openstreetmap.org/reverse', [
                            'format' => 'jsonv2',
                            'lat' => $rLat,
                            'lon' => $rLng,
                            'accept-language' => 'vi',
                        ]);
                    }

                    if ($response && $response->successful()) {
                        $resObj = null;
                        if ($provider === 'mapbox') {
                            $features = $response->json('features') ?? [];
                            if (count($features) > 0) {
                                $f = $features[0];
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $f);
                                $resObj = [
                                    'provider' => $provider,
                                    'display_name' => $f['place_name'] ?? null,
                                    'address' => clone ($f['place_name'] ?? null),
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'lat' => $rLat,
                                    'lng' => $rLng,
                                    'raw' => $f,
                                ];
                            }
                        } elseif ($provider === 'tomtom') {
                            $results = $response->json('addresses') ?? [];
                            if (count($results) > 0) {
                                $r = $results[0];
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $r);
                                $addr = $r['address'] ?? [];
                                $freeform = $addr['freeformAddress'] ?? null;
                                $resObj = [
                                    'provider' => $provider,
                                    'display_name' => $freeform,
                                    'address' => $freeform,
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'lat' => $rLat,
                                    'lng' => $rLng,
                                    'raw' => $r,
                                ];
                            }
                        } elseif ($provider === 'here') {
                            $items = $response->json('items') ?? [];
                            if (count($items) > 0) {
                                $i = $items[0];
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $i);
                                $resObj = [
                                    'provider' => $provider,
                                    'display_name' => $i['title'] ?? null,
                                    'address' => clone ($i['title'] ?? null),
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'lat' => $rLat,
                                    'lng' => $rLng,
                                    'raw' => $i,
                                ];
                            }
                        } elseif ($provider === 'nominatim') {
                            $data = $response->json() ?? [];
                            if (! isset($data['error'])) {
                                [$province, $district, $ward] = $this->parseProvinceDistrictWard($provider, $data);
                                $resObj = [
                                    'provider' => $provider,
                                    'display_name' => $data['display_name'] ?? null,
                                    'address' => clone ($data['display_name'] ?? null),
                                    'province' => $province,
                                    'district' => $district,
                                    'ward' => $ward,
                                    'lat' => $rLat,
                                    'lng' => $rLng,
                                    'raw' => $data,
                                ];
                            }
                        }

                        if ($resObj) {
                            return $resObj;
                        }
                    } else {
                        $this->safeLogWarning('reverse', $provider, $response ? $response->status() : null, 'HTTP error', $coordHash);
                    }
                } catch (\Exception $e) {
                    $this->safeLogWarning('reverse', $provider, null, 'Exception occurred', $coordHash);
                }
            }

            return null;
        });
    }
}
