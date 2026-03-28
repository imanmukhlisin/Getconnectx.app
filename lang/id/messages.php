<?php

return [
    // Auth Responses
    'registration_success' => 'Registrasi berhasil. Silakan verifikasi email Anda.',
    'email_already_verified' => 'Email sudah diverifikasi sebelumnya.',
    'email_otp_sent' => 'Kode Verifikasi OTP telah dikirim ke :email. Kode ini berlaku selama 10 menit.',
    'email_verify_success' => 'Email berhasil diverifikasi.',
    'whatsapp_already_verified' => 'WhatsApp sudah diverifikasi sebelumnya.',
    'whatsapp_otp_sent' => 'Kode Verifikasi OTP telah dikirim ke WhatsApp :number. Kode ini berlaku selama 10 menit.',
    'whatsapp_delivery_failed' => 'Gagal mengirim OTP WhatsApp. Silakan coba lagi.',
    'whatsapp_verify_success' => 'WhatsApp sudah diverifikasi. Registrasi selesai.',
    'registration_complete' => 'Registrasi selesai! Selamat bergabung di ConnectX.',
    
    // Exception Responses
    'validation_failed' => 'Terjadi kesalahan pada isian form (Uppsss...). Silakan periksa kembali kolom yang diisi.',
    'too_many_requests' => 'Terlalu banyak permintaan (Uppsss...). Silakan tunggu beberapa saat sebelum mencoba lagi.',
    'otp_not_found' => 'Upsss... OTP tidak ditemukan atau sudah kadaluarsa. Silahkan minta OTP baru.',
    'otp_invalid' => 'Upsss... Kode OTP yang kamu masukkan salah nihh.',
    'otp_rate_limit_minute' => 'Upsss... Terlalu banyak permintaan OTP. Coba lagi dalam :minutes menit.',
    
    // Validations (Register Request)
    'val_entity_type_required' => 'Tipe entitas wajib dipilih (talent atau startup).',
    'val_entity_type_in' => 'Tipe entitas hanya boleh "talent" atau "startup".',
    'val_email_required' => 'Alamat email wajib diisi.',
    'val_email_format' => 'Format email tidak valid (contoh yang benar: nama@email.com).',
    'val_email_max' => 'Email terlalu panjang (maksimal 255 karakter).',
    'val_email_unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.',
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
    'val_otp_digits' => 'Kode OTP harus berupa 6 digit angka.',
    'val_wa_number_required' => 'Nomor WhatsApp wajib diisi.',
    'val_wa_number_regex' => 'Nomor WhatsApp harus menggunakan format internasional, contoh: +6281234567890.',
];
