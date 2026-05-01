<?php

return [
    // Auth Responses
    'registration_success' => 'Registrasi berhasil. Silakan verifikasi email Anda.',
    'email_already_verified' => 'Email telah terverifikasi sebelumnya.',
    'email_otp_sent' => 'Kode Verifikasi OTP telah dikirim ke :email. Kode ini berlaku selama 10 menit.',
    'login_otp_sent' => 'Kode login telah dikirim ke :email.',
    'email_verify_success' => 'Email berhasil diverifikasi.',
    'whatsapp_already_verified' => 'WhatsApp telah terverifikasi sebelumnya.',
    'whatsapp_otp_sent' => 'Kode Verifikasi OTP telah dikirim ke WhatsApp :number. Kode ini berlaku selama 10 menit.',
    'whatsapp_delivery_failed' => 'Gagal mengirim OTP WhatsApp. Silakan coba lagi.',
    'whatsapp_verify_success' => 'WhatsApp telah terverifikasi. Registrasi selesai.',
    'registration_complete' => 'Selamat! Registrasi kamu selesai! Selamat bergabung di ConnectX.',
    'login_success' => 'Login berhasil! Selamat datang kembali.',
    'login_failed' => 'Email atau password salah. Silakan coba lagi.',
    'inactive_user' => 'Akun Anda belum aktif. Selesaikan proses registrasi atau verifikasi terlebih dahulu.',
    
    // OAuth Responses
    'oauth_provider_unsupported' => "Provider ':provider' tidak didukung. Gunakan: :allowed.",
    'oauth_login_cancelled' => 'OAuth login dibatalkan atau terjadi kesalahan.',
    'oauth_info_failed' => 'Gagal mendapatkan informasi dari provider OAuth.',
    'oauth_token_invalid' => 'Token :provider tidak valid atau sudah kedaluwarsa. Silakan login ulang melalui :provider.',
    'oauth_login_success_new' => 'Login via :provider berhasil. Silakan lengkapi verifikasi WhatsApp.',
    'oauth_login_success_returning' => 'Login via :provider berhasil. Selamat datang kembali!',

    // Exception Responses
    'validation_failed' => 'Terjadi kesalahan pada isian form. Silakan periksa kembali kolom yang diisi.',
    'too_many_requests' => 'Terlalu banyak permintaan. Silakan tunggu beberapa saat sebelum mencoba lagi.',
    'otp_not_found' => 'Upsss... OTP tidak ditemukan atau sudah kadaluarsa. Silahkan minta OTP baru.',
    'otp_invalid' => 'Upsss... Kode OTP yang kamu masukkan salah, silahkan cek kembali.',
    'otp_rate_limit_minute' => 'Upsss... Terlalu banyak permintaan OTP. Coba lagi dalam :minutes menit.',
    
    // Validations (Register Request)
'val_email_required' => 'Alamat email wajib diisi.',
    'val_email_format' => 'Format email tidak valid (contoh yang benar: nama@email.com).',
    'val_email_max' => 'Email terlalu panjang (maksimal 255 karakter).',
    'val_email_unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.',
    'val_email_not_found' => 'Kami tidak menemukan akun dengan alamat email tersebut.',
    'val_password_required' => 'Password wajib diisi.',
    'val_password_confirmed' => 'Konfirmasi password tidak cocok dengan password yang diketik.',
    'val_password_min' => 'Password harus berisi minimal 8 karakter.',
    'val_password_mixed' => 'Password harus mengandung minimal 1 huruf kapital dan 1 huruf kecil.',
    'val_password_numbers' => 'Password harus mengandung minimal 1 angka.',
    'val_password_symbols' => 'Password harus mengandung minimal 1 simbol / karakter unik (@, #, !, dsb).',
    'val_password_uncompromised' => 'Password Anda terlalu umum / pernah bocor di internet. Silakan buat kombinasi password yang lebih aman dan unik.',
    'val_password_conf_required' => 'Konfirmasi password wajib diisi.',
    
    // Validations (Email & WhatsApp Verify)
    'val_otp_required' => 'Kode OTP wajib diisi.',
    'val_otp_digits' => 'Kode OTP harus berupa 6 digit angka, silahkan cek kembali.',
    'val_wa_number_required' => 'Nomor WhatsApp wajib diisi.',
    'val_wa_number_regex' => 'Nomor WhatsApp harus menggunakan format internasional, contoh: +6281234567890.',
    'val_provider_token_required' => 'Token dari provider OAuth wajib disertakan.',
    
    // WhatsApp Delivery Content
    'wa_otp_messages' => [
        ":greeting! Selamat datang di ConnectX. Kode verifikasi kamu adalah: *:code*. Berlaku selama :expiry menit. Jangan bagikan ke siapapun ya.",
        ":greeting! Sebelum memulai aplikasi ConnectX untuk temukan timmu yuk verifikasi dulu, berikut adalah kode OTP nya: *:code*. Aktif selama :expiry menit.",
        ":greeting! Siap membangun startup hebat? Gunakan OTP ini untuk masuk ke ConnectX: *:code*. Berlaku untuk :expiry menit.",
        ":greeting! Ini kode verifikasi ConnectX kamu: *:code*. Jangan berikan ke orang lain ya. Kadaluarsa dalam :expiry menit.",
        ":greeting! Mari mulai koneksimu! Kode OTP ConnectX kamu adalah: *:code*. Berlaku selama :expiry menit ke depan.",
        ":greeting! Untuk keamanan akun ConnectX kamu, gunakan kode verifikasi ini: *:code*. Berlaku selama :expiry menit.",
        ":greeting! Sedikit lagi selesai! Masukkan OTP ini untuk lanjut ke ConnectX: *:code*. Hangus dalam :expiry menit.",
        ":greeting! Kode login ConnectX kamu adalah: *:code*. Jaga kerahasiaannya ya. Berlaku untuk :expiry menit.",
        ":greeting! Selamat datang kembali di ConnectX. Silakan masukkan kode verifikasi ini: *:code*. Akan hangus dalam :expiry menit.",
        ":greeting! Verifikasi akun ConnectX kamu pakai kode OTP ini ya: *:code*. Jangan sampai bocor. Berlaku selama :expiry menit."
    ],
];
