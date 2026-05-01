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
        ":greeting!\n\nTerima kasih telah memilih ConnectX sebagai platform kolaborasi Anda. Untuk alasan keamanan, berikut adalah kode verifikasi (OTP) yang Anda perlukan untuk mengakses akun Anda:\n\n*👉 :code 👈*\n\nKode ini hanya berlaku selama :expiry menit. Mohon untuk tidak membagikan kode ini kepada siapa pun, termasuk pihak yang mengatasnamakan ConnectX.\n\nSalam hangat,\nTim Keamanan ConnectX",
        
        ":greeting!\n\nKami menerima permintaan verifikasi untuk akun ConnectX Anda. Silakan gunakan kode otentikasi berikut untuk melanjutkan proses:\n\n*👉 :code 👈*\n\nDemi keamanan privasi Anda, kode ini akan kedaluwarsa dalam waktu :expiry menit. Pastikan Anda tidak meneruskan pesan ini kepada siapapun.\n\nHormat kami,\nLayanan Pelanggan ConnectX",
        
        ":greeting! Selamat datang di ekosistem ConnectX.\n\nUntuk memverifikasi identitas dan mengamankan akun Anda, silakan masukkan kode One-Time Password (OTP) berikut pada layar aplikasi:\n\n*👉 :code 👈*\n\nSebagai pengingat, kode ini bersifat rahasia dan hanya valid untuk :expiry menit ke depan. Terima kasih atas kepercayaan Anda.\n\nSalam,\nTim ConnectX",
        
        ":greeting!\n\nSistem kami mendeteksi upaya otorisasi untuk akun ConnectX Anda. Gunakan kode keamanan di bawah ini untuk menyelesaikan proses verifikasi:\n\n*👉 :code 👈*\n\nKode akan otomatis hangus setelah :expiry menit. Jika Anda tidak merasa melakukan permintaan ini, abaikan pesan ini untuk menjaga keamanan akun Anda.\n\nTerima kasih,\nConnectX Support",
        
        ":greeting!\n\nTerima kasih telah bergabung bersama ConnectX. Langkah terakhir untuk mengaktifkan sesi Anda adalah dengan memasukkan kode verifikasi berikut:\n\n*👉 :code 👈*\n\nMasa berlaku kode ini adalah :expiry menit. Kami berkomitmen untuk selalu menjaga kerahasiaan data dan akun Anda.\n\nSalam hangat,\nTim ConnectX",

        ":greeting!\n\nInilah saatnya menghubungkan ide Anda dengan ConnectX! Masukkan kode otentikasi berikut untuk melanjutkan sesi aman Anda:\n\n*👉 :code 👈*\n\nKode verifikasi ini akan kedaluwarsa setelah :expiry menit. Jaga kerahasiaan kode ini dengan baik demi keamanan data Anda.\n\nSukses selalu,\nTim IT ConnectX",

        ":greeting! Mari wujudkan kolaborasi hebat bersama ConnectX.\n\nBerikut adalah kode OTP unik Anda untuk mengakses platform kami:\n\n*👉 :code 👈*\n\nMasa aktif kode ini terbatas hanya :expiry menit. Pastikan Anda segera memasukkannya dan tidak menyebarkannya kepada pihak luar.\n\nSalam Inovasi,\nConnectX Indonesia",

        ":greeting!\n\nKami sangat menghargai privasi dan keamanan Anda. Untuk memastikan bahwa ini benar-benar Anda, silakan gunakan kode rahasia di bawah ini:\n\n*👉 :code 👈*\n\nSegera masukkan kode ini dalam waktu :expiry menit sebelum hangus. Abaikan pesan ini jika Anda tidak merasa melakukan pendaftaran.\n\nTerima kasih,\nTim Sekuritas ConnectX",

        ":greeting!\n\nSelamat! Anda hanya berjarak satu langkah lagi menuju dashboard ConnectX Anda. Gunakan OTP verifikasi di bawah ini:\n\n*👉 :code 👈*\n\nKode keamanan ini dikhususkan untuk akun Anda dan akan expired dalam :expiry menit. Hati-hati terhadap penipuan.\n\nSalam Hangat,\nCustomer Care ConnectX",

        ":greeting! Kami senang melihat antusiasme Anda di ConnectX.\n\nSelesaikan proses otentikasi akun Anda dengan memasukkan kode berikut secara akurat:\n\n*👉 :code 👈*\n\nIngat, kode ini hanya dapat digunakan sekali dan hanya berlaku :expiry menit. Jangan pernah membagikannya kepada siapa pun.\n\nSalam sukses,\nManajemen ConnectX",
    ],
];
