<?php

namespace App\Http\Controllers;

use App\Models\DiaChi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiaChiController extends Controller
{
    public function index(Request $request)
    {
        $addresses = DiaChi::where('id_user', $request->user()->id)
            ->orderByDesc('mac_dinh')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAddress($request);
        $validated['quan_huyen'] = $validated['quan_huyen'] ?? '';
        $userId = $request->user()->id;

        try {
            $address = DB::transaction(function () use ($validated, $userId) {
                $isFirstAddress = ! DiaChi::where('id_user', $userId)->exists();
                $shouldBeDefault = $isFirstAddress || (bool) ($validated['mac_dinh'] ?? false);

                if ($shouldBeDefault) {
                    DiaChi::where('id_user', $userId)->update(['mac_dinh' => false]);
                }

                return DiaChi::create([
                    ...$validated,
                    'id_user' => $userId,
                    'mac_dinh' => $shouldBeDefault,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Thêm địa chỉ thành công',
                'data' => $address,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thao tác thất bại, vui lòng thử lại',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateAddress($request);
        $validated['quan_huyen'] = $validated['quan_huyen'] ?? '';
        $address = $this->findUserAddress($request, $id);

        if (! $address) {
            return $this->forbiddenResponse();
        }

        try {
            $address = DB::transaction(function () use ($address, $validated) {
                $shouldBeDefault = (bool) ($validated['mac_dinh'] ?? false);

                if ($shouldBeDefault) {
                    DiaChi::where('id_user', $address->id_user)->update(['mac_dinh' => false]);
                }

                $address->update([
                    ...$validated,
                    'mac_dinh' => $shouldBeDefault || $address->mac_dinh,
                ]);

                return $address->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật địa chỉ thành công',
                'data' => $address,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thao tác thất bại, vui lòng thử lại',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $address = $this->findUserAddress($request, $id);

        if (! $address) {
            return $this->forbiddenResponse();
        }

        try {
            DB::transaction(function () use ($address) {
                $wasDefault = $address->mac_dinh;
                $userId = $address->id_user;
                $address->delete();

                if ($wasDefault) {
                    $nextAddress = DiaChi::where('id_user', $userId)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($nextAddress) {
                        $nextAddress->update(['mac_dinh' => true]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Xóa địa chỉ thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thao tác thất bại, vui lòng thử lại',
            ], 500);
        }
    }

    public function setDefault(Request $request, $id)
    {
        $address = $this->findUserAddress($request, $id);

        if (! $address) {
            return $this->forbiddenResponse();
        }

        try {
            DB::transaction(function () use ($address) {
                DiaChi::where('id_user', $address->id_user)->update(['mac_dinh' => false]);
                $address->update(['mac_dinh' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật địa chỉ mặc định thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thao tác thất bại, vui lòng thử lại',
            ], 500);
        }
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'tinh_thanhpho' => ['required', 'string', 'max:255'],
            'quan_huyen' => ['nullable', 'string', 'max:255'],
            'phuong_xa' => ['required', 'string', 'max:255'],
            'diachi_cuthe' => ['required', 'string', 'min:5', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'loai_diachi' => ['required', Rule::in(['home', 'company'])],
            'mac_dinh' => ['sometimes', 'boolean'],
        ], [
            'required' => 'Vui lòng nhập đầy đủ thông tin',
            'loai_diachi.in' => 'Loại địa chỉ không hợp lệ',
            'diachi_cuthe.min' => 'Địa chỉ cụ thể phải có ít nhất 5 ký tự',
        ]);
    }

    private function findUserAddress(Request $request, $id): ?DiaChi
    {
        return DiaChi::where('id_user', $request->user()->id)
            ->where('id_diachi', $id)
            ->first();
    }

    private function forbiddenResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Bạn không có quyền thực hiện thao tác này',
        ], 403);
    }
}
