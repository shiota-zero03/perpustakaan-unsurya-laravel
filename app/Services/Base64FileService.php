<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Base64FileService
{
    /**
     * Simpan file base64 ke dalam public/assets/{tipe_file}
     *
     * @param string $base64File
     * @return array
     */
    public static function saveBase64File(string $base64File, array $mimeMap, string $typeName): array
    {
        // Cek apakah format Base64 valid
        if (!preg_match('/^data:(.+);base64,(.+)$/', $base64File, $matches)) {
            return ["success" => false, "message" => "Invalid Base64 format"];
        }

        $mimeType = $matches[1]; // Contoh: "image/png" atau "application/pdf"
        $base64Data = $matches[2]; // Data Base64 tanpa prefix
        $buffer = base64_decode($base64Data);

        if (!isset($mimeMap[$mimeType])) {
            return ["success" => false, "message" => "Format file tidak didukung"];
        }

        $extension = $mimeMap[$mimeType];

        // Tentukan folder penyimpanan
        $subDir = str_starts_with($mimeType, "image/") ? "images" : "documents";
        $folderPath = public_path("assets/{$subDir}");

        // Buat folder jika belum ada
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true, true);
        }

        // Buat nama file unik
        $microtime = explode(" ", microtime());
        $milliseconds = substr($microtime[0], 2, 3);
        $timestamp = date("YmdHis") . $milliseconds;
        $fileName = "{$typeName}_{$timestamp}.{$extension}";
        $filePath = "{$folderPath}/{$fileName}";

        // Simpan file
        file_put_contents($filePath, $buffer);

        return [
            "success" => true,
            "filePath" => asset("assets/{$subDir}/{$fileName}"),
            "message" => "File berhasil disimpan!"
        ];
    }
}
