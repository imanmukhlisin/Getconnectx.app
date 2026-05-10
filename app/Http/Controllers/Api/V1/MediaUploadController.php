<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_TYPES  = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * POST /api/v1/upload
     *
     * Accepts a multipart/form-data file upload.
     * Stores to Google Cloud Storage (or local fallback).
     * Returns a media_id (UUID) for use in sendMessage.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB in KB
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
        ]);

        $file     = $request->file('file');
        $mimeType = $file->getMimeType();
        $size     = $file->getSize();

        // Generate a unique media_id for FE to reference later
        $mediaId  = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename  = "chat-media/{$mediaId}.{$extension}";

        try {
            // Store via the configured filesystem (gcs or local)
            $disk = config('filesystems.default', 'local');
            $path = $file->storeAs('chat-media', "{$mediaId}.{$extension}", $disk);

            if (!$path) {
                throw new \Exception("Failed to store file on disk: {$disk}. Check your cloud storage credentials.");
            }

            $url = $this->resolvePublicUrl($path, $disk);

            // Thumbnail URL: same as main for now — in production, trigger a resize job
            $thumbnailUrl = $url;

            // Persist a reference row in messages so sendMessage can look it up
            // We use a "phantom" message row as a temporary upload record
            $uploadRecord = \App\Models\Message::create([
                'id'              => $mediaId,
                'conversation_id' => null, // null = not yet attached to a conversation
                'sender_id'       => $request->user()->id,
                'content'         => null,
                'type'            => 'image',
                'media'           => [
                    'url'           => $url,
                    'thumbnail_url' => $thumbnailUrl,
                    'mime_type'     => $mimeType,
                    'size_bytes'    => $size,
                ],
            ]);

            Log::info('MediaUploadController: File uploaded.', [
                'media_id' => $mediaId,
                'user_id'  => $request->user()->id,
                'size'     => $size,
            ]);

            return response()->json([
                'media_id'      => $mediaId,
                'url'           => $url,
                'thumbnail_url' => $thumbnailUrl,
                'mime_type'     => $mimeType,
                'size_bytes'    => $size,
            ]);

        } catch (\Throwable $e) {
            Log::error('MediaUploadController: Upload failed.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'File upload failed. Please try again.'], 500);
        }
    }

    private function resolvePublicUrl(string $path, string $disk): string
    {
        if ($disk === 'gcs') {
            $bucketName = config('filesystems.disks.gcs.bucket');
            $pathPrefix = trim(config('filesystems.disks.gcs.path_prefix', ''), '/');
            $fullPath   = $pathPrefix ? "{$pathPrefix}/{$path}" : $path;
            return "https://storage.googleapis.com/{$bucketName}/{$fullPath}";
        }

        return asset("storage/{$path}");
    }
}
