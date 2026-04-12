<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Membuat Google Cloud Storage Pre-signed / Temporary URL untuk upload langsung.
     */
    public function generateUploadUrl(Request $request)
    {
        $request->validate([
            'file_name' => 'required|string',
            'mime_type' => 'required|string', // Contoh: image/png, application/pdf
        ]);

        $fileName = $request->input('file_name');
        
        // Membersihkan nama file dan memberikan keunikan dengan UUID agar file dengan nama yang sama tidak tumpang tindih
        $sanitizedName = Str::slug(pathinfo($fileName, PATHINFO_FILENAME));
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueName = Str::uuid() . '_' . $sanitizedName . '.' . $extension;

        // Tentukan path di Bucket. Misalnya: uploads/awd-123_pitchdeck.pdf
        $path = 'uploads/' . $uniqueName;
        
        try {
            // Kita keluarkan tiket ijin dari backend untuk Frontend
            // Berlaku 10 menit, Method WAJIB PUT, dan Content-Type disesuaikan.
            $uploadUrl = Storage::disk('gcs')->temporaryUrl(
                $path,
                now()->addMinutes(10),
                [
                    'method' => 'PUT',
                    'contentType' => $request->input('mime_type')
                ]
            );

            // Membuat URL akhir yang nantinya bisa dipakai sebagai value untuk ditampilkan kembali ke frontend / user.
            // Google Storage public read access pattern URL:
            $bucketName = config('filesystems.disks.gcs.bucket');
            $fileUrl = "https://storage.googleapis.com/{$bucketName}/{$path}";

            return response()->json([
                'upload_url' => $uploadUrl,
                'file_url'   => $fileUrl,
                'path'       => $path,
                'expires_in' => '10 Minutes'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat signed URL. Pastikan kredensial GCS terpasang bener di .env!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
