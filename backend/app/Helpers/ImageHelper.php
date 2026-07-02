<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function normalizePublicPath(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
        if (str_starts_with($raw, 'data:') || str_starts_with($raw, 'blob:')) {
            return $raw;
        }
        if (preg_match('#^https?://#i', $raw) && ! str_contains($raw, '/storage/')) {
            return $raw;
        }

        $path = parse_url($raw, PHP_URL_PATH) ?: $raw;
        $path = str_replace('\\', '/', $path);

        if (str_contains($path, '/storage/')) {
            $path = preg_replace('#^.*?/storage/#', '', $path);
        }

        $path = ltrim($path, '/');
        $path = preg_replace('#^public/#i', '', $path);
        $path = preg_replace('#^storage/#i', '', $path);
        $path = ltrim($path, '/');

        return $path ?: null;
    }

    public static function saveBase64Image(?string $base64, string $folder = 'uploads/sanpham'): ?string
    {
        if (! $base64) {
            return null;
        }

        if (! preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);

        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = str_replace(' ', '+', $imageData);
        $binaryData = base64_decode($imageData);

        if ($binaryData === false) {
            return null;
        }

        // hash để chống trùng
        $hash = md5($binaryData);
        $fileName = $hash.'.'.$extension;
        $filePath = $folder.'/'.$fileName;

        if (! Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->put($filePath, $binaryData);
        }

        return $filePath;
    }
}
