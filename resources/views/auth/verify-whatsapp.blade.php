<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify WhatsApp - ConnectX</title>
</head>
<body>
    <h1>Verifikasi WhatsApp</h1>
    
    <p>Silakan masukkan nomor WhatsApp Anda dan minta OTP.</p>

    <div style="margin-bottom: 20px;">
        <label>1. Masukkan Nomor WhatsApp (Kirim/Resend OTP):</label><br>
        <input type="text" id="waNumber" placeholder="Contoh: 08123456789">
        <button id="btnSendOtp">Kirim OTP WA</button>
        <span id="sendResult"></span>
    </div>

    <form id="verifyWaForm">
        <label>2. Masukkan OTP WhatsApp:</label><br>
        <input type="text" name="otp_code" placeholder="Kode OTP WA" required>
        <!-- hidden field to keep the wa number if needed by API -->
        <input type="hidden" name="whatsapp_number" id="hiddenWaNumber">
        <button type="submit">Verifikasi</button>
    </form>

    <div style="margin-top: 20px;">
        <a href="/dashboard">Kembali ke Dashboard</a> | <a href="/onboarding/stage-a">Lanjut ke Onboarding Stage A</a>
    </div>

    <script>
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Anda belum login.');
            window.location.href = '/login';
        }

        const baseUrl = '/api/v1/auth';

        document.getElementById('btnSendOtp').addEventListener('click', async function() {
            const waNumber = document.getElementById('waNumber').value;
            document.getElementById('sendResult').textContent = 'Loading...';
            document.getElementById('hiddenWaNumber').value = waNumber;

            try {
                const response = await fetch(`${baseUrl}/whatsapp/send-otp`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ whatsapp_number: waNumber }) // Kirim wa_number jika api butuh
                });
                const data = await response.json();
                if(response.ok) {
                    document.getElementById('sendResult').textContent = 'OTP telah dikirim ke WA!';
                } else {
                    document.getElementById('sendResult').textContent = 'Gagal: ' + (data.message || JSON.stringify(data));
                }
            } catch (error) {
                document.getElementById('sendResult').textContent = 'Error: ' + error.message;
            }
        });

        document.getElementById('verifyWaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const object = {};
            formData.forEach((value, key) => object[key] = value);

            try {
                const response = await fetch(`${baseUrl}/verify-whatsapp`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(object)
                });
                const data = await response.json();
                
                if (response.ok) {
                    alert('WhatsApp berhasil diverifikasi!');
                    window.location.href = '/onboarding/stage-a';
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
