<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ConnectX</title>
</head>
<body>
    <h1>Login</h1>
    
    <h3>Login Tradisional (Password)</h3>
    <form id="loginPasswordForm">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>

    <hr>
    <h3>Login OTP</h3>
    <form id="otpSendForm">
        <h4>Kirim OTP</h4>
        <input type="email" name="email" id="otpEmail" placeholder="Email" required><br>
        <button type="submit">Kirim OTP</button>
    </form>
    <form id="otpVerifyForm" style="margin-top: 10px; display: none;">
        <h4>Verifikasi OTP</h4>
        <p id="otpMessage"></p>
        <input type="text" name="otp" placeholder="Kode OTP" required><br>
        <button type="submit">Verifikasi OTP</button>
    </form>

    <p><a href="/">Kembali ke Home</a> | <a href="/register">Belum punya akun? Register</a></p>

    <script>
        const baseUrl = '/api/v1';

        function saveTokenAndRedirect(token) {
            localStorage.setItem('token', token);
            window.location.href = '/dashboard';
        }

        document.getElementById('loginPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const object = {};
            formData.forEach((value, key) => object[key] = value);

            try {
                const response = await fetch(`${baseUrl}/auth/login/password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(object)
                });
                const data = await response.json();
                
                if (response.ok) {
                    if (data.token) {
                        saveTokenAndRedirect(data.token);
                    } else if (data.data && data.data.token) {
                        saveTokenAndRedirect(data.data.token);
                    } else if (data.access_token) {
                        saveTokenAndRedirect(data.access_token);
                    } else {
                        alert('Login Berhasil (Tidak ada token direturn!)');
                    }
                } else {
                    alert('Login gagal: ' + (data.message || JSON.stringify(data)));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        // OTP Login Script
        let userEmailForOtp = '';
        document.getElementById('otpSendForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            userEmailForOtp = document.getElementById('otpEmail').value;

            try {
                const response = await fetch(`${baseUrl}/auth/login/otp/send`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: userEmailForOtp })
                });
                const data = await response.json();
                if (response.ok) {
                    alert('OTP berhasil dikirim ke ' + userEmailForOtp);
                    document.getElementById('otpVerifyForm').style.display = 'block';
                    document.getElementById('otpMessage').textContent = 'Masukkan kode OTP yang dikirim.';
                } else {
                    alert('Gagal mengirim OTP: ' + (data.message || JSON.stringify(data)));
                }
            } catch(error) {
                alert('Error: ' + error.message);
            }
        });

        document.getElementById('otpVerifyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const otpCode = formData.get('otp');

            try {
                const response = await fetch(`${baseUrl}/auth/login/otp/verify`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: userEmailForOtp, otp_code: otpCode })
                });
                const data = await response.json();
                
                if (response.ok) {
                    if (data.token) {
                        saveTokenAndRedirect(data.token);
                    } else if (data.data && data.data.token) {
                        saveTokenAndRedirect(data.data.token);
                    } else if (data.access_token) {
                        saveTokenAndRedirect(data.access_token);
                    } else {
                         alert('Berhasil Verifikasi OTP (Token tidak ditemukan)!');
                    }
                } else {
                    alert('Gagal verifikasi OTP: ' + (data.message || JSON.stringify(data)));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    </script>
</body>
</html>
