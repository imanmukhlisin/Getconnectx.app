<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi ConnectX</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .header p { color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 14px; }
        .body { padding: 40px; }
        .greeting { color: #1a1a2e; font-size: 16px; margin: 0 0 16px; }
        .code-box { background: #f0f0ff; border: 2px dashed #6366f1; border-radius: 10px; padding: 20px; text-align: center; margin: 24px 0; }
        .code { font-size: 40px; font-weight: 800; letter-spacing: 12px; color: #6366f1; font-family: 'Courier New', monospace; }
        .label { color: #6b7280; font-size: 13px; margin-top: 8px; }
        .note { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; color: #78350f; font-size: 13px; margin: 20px 0; }
        .footer { padding: 20px 40px; border-top: 1px solid #f3f4f6; color: #9ca3af; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>ConnectX</h1>
        <p>Verifikasi Email</p>
    </div>
    <div class="body">
        <p class="greeting" style="font-size:18px;">Dear <strong>{{ $user->name ?? $user->email }}</strong>,</p>
        <p style="color:#4b5563;font-size:16px; margin-bottom: 8px;">Kode verifikasi (OTP) Anda adalah:</p>

        <div class="code-box">
            <div class="code">{{ $code }}</div>
        </div>

        <p style="color:#4b5563;font-size:16px; line-height: 1.5; margin-bottom: 24px;">
            Demi keamanan, jangan berikan kode ini kepada siapapun,<br>
            termasuk pihak yang mengaku sebagai ConnectX. Kode ini hanya<br>
            berlaku selama <strong>{{ config('otp.expiry_minutes', 10) }} menit</strong>.
        </p>

        <p style="color:#4b5563;font-size:16px; line-height: 1.5;">
            Jika Anda tidak meminta kode ini,<br>
            silakan abaikan email ini.
        </p>
    </div>
    <div class="footer">
        © {{ date('Y') }} ConnectX. All rights reserved.
    </div>
</div>
</body>
</html>
