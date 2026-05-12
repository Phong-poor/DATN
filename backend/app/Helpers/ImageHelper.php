<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function saveBase64Image(?string $base64, string $folder = 'uploads/sanpham'): ?string
    {
        if (!$base64) return null;

        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);

        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = str_replace(' ', '+', $imageData);
        $binaryData = base64_decode($imageData);

        if ($binaryData === false) return null;

        // hash để chống trùng
        $hash = md5($binaryData);
        $fileName = $hash . '.' . $extension;
        $filePath = $folder . '/' . $fileName;

        if (!Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->put($filePath, $binaryData);
        }

        return $filePath;
    }
}