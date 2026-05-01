<?php

return [
    // Auth Responses
    'registration_success' => 'Registrasi berhasil. Silakan verifikasi email Anda.',
    'email_already_verified' => 'Email telah terverifikasi sebelumnya.',
    'email_otp_sent' => 'Kode Verifikasi OTP telah dikirim ke :email. Kode ini berlaku selama 10 menit.',
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
        // Indonesian Professional Variations
        ":greeting!\n\nTerima kasih telah memilih ConnectX sebagai platform kolaborasi Anda. Untuk alasan keamanan, berikut adalah kode verifikasi (OTP) yang Anda perlukan untuk mengakses akun Anda:\n\n*👉 :code 👈*\n\nKode ini hanya berlaku selama :expiry menit. Mohon untuk tidak membagikan kode ini kepada siapa pun, termasuk pihak yang mengatasnamakan ConnectX.\n\nSalam hangat,\nTim Keamanan ConnectX",
        
        ":greeting!\n\nKami menerima permintaan verifikasi untuk akun ConnectX Anda. Silakan gunakan kode otentikasi berikut untuk melanjutkan proses:\n\n*👉 :code 👈*\n\nDemi keamanan privasi Anda, kode ini akan kedaluwarsa dalam waktu :expiry menit. Pastikan Anda tidak meneruskan pesan ini kepada siapapun.\n\nHormat kami,\nLayanan Pelanggan ConnectX",
        
        ":greeting! Selamat datang di ekosistem ConnectX.\n\nUntuk memverifikasi identitas dan mengamankan akun Anda, silakan masukkan kode One-Time Password (OTP) berikut pada layar aplikasi:\n\n*👉 :code 👈*\n\nSebagai pengingat, kode ini bersifat rahasia dan hanya valid untuk :expiry menit ke depan. Terima kasih atas kepercayaan Anda.\n\nSalam,\nTim ConnectX",
        
        ":greeting!\n\nSistem kami mendeteksi upaya otorisasi untuk akun ConnectX Anda. Gunakan kode keamanan di bawah ini untuk menyelesaikan proses verifikasi:\n\n*👉 :code 👈*\n\nKode akan otomatis hangus setelah :expiry menit. Jika Anda tidak merasa melakukan permintaan ini, abaikan pesan ini untuk menjaga keamanan akun Anda.\n\nTerima kasih,\nConnectX Support",
        
        ":greeting!\n\nTerima kasih telah bergabung bersama ConnectX. Langkah terakhir untuk mengaktifkan sesi Anda adalah dengan memasukkan kode verifikasi berikut:\n\n*👉 :code 👈*\n\nMasa berlaku kode ini adalah :expiry menit. Kami berkomitmen untuk selalu menjaga kerahasiaan data dan akun Anda.\n\nSalam hangat,\nTim ConnectX",

        // English Professional Variations
        ":greeting!\n\nThank you for choosing ConnectX. To ensure the highest level of security for your account, please use the following One-Time Password (OTP) to complete your verification process:\n\n*👉 :code 👈*\n\nThis code will strictly expire in :expiry minutes. For your protection, please do not share this code with anyone under any circumstances.\n\nBest regards,\nConnectX Security Team",
        
        ":greeting!\n\nWe have received a verification request for your ConnectX account. Please proceed by entering the authentication code below:\n\n*👉 :code 👈*\n\nFor privacy and security reasons, this code is valid for only :expiry minutes. Please ensure that you do not forward this message to unauthorized individuals.\n\nSincerely,\nConnectX Customer Service",
        
        ":greeting! Welcome to the ConnectX ecosystem.\n\nTo verify your identity and secure your account access, please input the following OTP on your application screen:\n\n*👉 :code 👈*\n\nAs a gentle reminder, this code is strictly confidential and will remain valid for the next :expiry minutes. Thank you for your trust.\n\nWarm regards,\nThe ConnectX Team",
        
        ":greeting!\n\nOur system has detected an authorization attempt for your ConnectX account. Use the security code below to finalize the verification procedure:\n\n*👉 :code 👈*\n\nThe code will automatically expire after :expiry minutes. If you did not initiate this request, please disregard this message to keep your account secure.\n\nThank you,\nConnectX Support",
        
        ":greeting!\n\nThank you for joining ConnectX. The final step to activate your secure session is to enter the verification code provided below:\n\n*👉 :code 👈*\n\nThe validity period for this code is :expiry minutes. We are fully committed to maintaining the confidentiality of your data and account.\n\nBest regards,\nThe ConnectX Team",
    ],
];
