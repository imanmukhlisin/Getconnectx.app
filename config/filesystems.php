<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk Penyimpanan Default Sistem
    |--------------------------------------------------------------------------
    |
    | Di sini bebas menentukan disk storage mana yang jadi andalan utama
    | buat aplikasi ini. Tersedia tipe "local" (simpan offline di server internal)
    | atau pake cloud system (kayak s3/gcs) buat nafas panjang aplikasi.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Daftar Disk Penyimpanan
    |--------------------------------------------------------------------------
    |
    | Bagian ini buat meracik ragam tempat penyimpanan sesuai kebutuhan.
    | Bahkan bisa bikin lebih dari satu disk dengan fungsi yang sama (misal 2 AWS S3).
    | Driver yang didukung framework ini: "local", "ftp", "sftp", "s3", dan "gcs".
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'gcs' => [
            'driver' => 'gcs',
            // key_file_path hanya dipakai jika file fisiknya beneran ada (local dev).
            // Di Vercel (serverless), file ini tidak ada → gunakan key_file (JSON string).
            'key_file_path' => (env('GOOGLE_CLOUD_KEY_FILE') && file_exists(base_path(env('GOOGLE_CLOUD_KEY_FILE'))))
                ? base_path(env('GOOGLE_CLOUD_KEY_FILE'))
                : null,
            // Vercel-friendly: credentials dari ENV variable sebagai JSON string.
            'key_file' => env('GOOGLE_CLOUD_KEY_JSON') ? json_decode(env('GOOGLE_CLOUD_KEY_JSON'), true) : null,
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'),
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''),
            'storage_api_uri' => null,
            'apiEndpoint' => null,
            'visibility' => 'private', // Bucket pakai "Uniform bucket-level access" → jangan pakai legacy ACL per-file
            'visibility_handler' => null,
            'throw' => true, // throw=true agar error GCS terlihat jelas di log
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Tautan Simbolis (Symbolic Links)
    |--------------------------------------------------------------------------
    |
    | Di sini digunakan untuk mengatur tautan penghubung (shortcut) yang bakal dibuat otomatis 
    | kalo jalanin perintah `php artisan storage:link`. 
    | Kuncinya (key) adalah lokasi umum/publik, sedangkan nilainya (value) 
    | adalah folder asli tempat file disimpen.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
