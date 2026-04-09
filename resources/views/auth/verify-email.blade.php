<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - ConnectX</title>
</head>
<body>
    <h1>Verifikasi Email</h1>
    
    <p>Silakan minta OTP untuk email Anda, lalu masukkan kodenya.</p>

    <div style="margin-bottom: 20px;">
        <button id="btnSendOtp">1. Minta / Resend OTP Email</button>
        <span id="sendResult"></span>
    </div>

    <form id="verifyEmailForm">
        <label>2. Masukkan OTP Email:</label><br>
        <input type="text" name="otp" placeholder="Kode OTP Email" required>
        <button type="submit">Verifikasi</button>
    </form>

    <div style="margin-top: 20px;">
        <a href="/dashboard">Kembali ke Dashboard</a> | <a href="/verify-whatsapp">Lanjut ke Verifikasi WA</a>
    </div>

    <script>
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Anda belum login.');
            window.location.href = '/login';
        }

        const baseUrl = '/api/v1/auth';

        document.getElementById('btnSendOtp').addEventListener('click', async function() {
            document.getElementById('sendResult').textContent = 'Loading...';
            try {
                const response = await fetch(`${baseUrl}/email/send-otp`, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                const data = await response.json();
                if(response.ok) {
                    document.getElementById('sendResult').textContent = 'OTP telah dikirim ke email!';
                } else {
                    document.getElementById('sendResult').textContent = 'Gagal: ' + (data.message || JSON.stringify(data));
                }
            } catch (error) {
                document.getElementById('sendResult').textContent = 'Error: ' + error.message;
            }
        });

        document.getElementById('verifyEmailForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const otpCode = new FormData(this).get('otp');

            try {
                const response = await fetch(`${baseUrl}/verify-email`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ otp_code: otpCode })
                });
                const data = await response.json();
                
                if (response.ok) {
                    alert('Email berhasil diverifikasi!');
                    window.location.href = '/verify-whatsapp';
                } else {
                    alert('Gagal: ' + (data.message || JSON.stringify(data)));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    </script>
</body>
</html>
