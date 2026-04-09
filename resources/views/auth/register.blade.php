<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ConnectX</title>
</head>
<body>
    <h1>Register</h1>
    <form id="registerForm">
        <input type="text" name="name" placeholder="Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="password_confirmation" placeholder="Confirm Password" required><br>
        
        <label>Entity Type:</label><br>
        <select name="entity_type" required>
            <option value="talent">Talent</option>
            <option value="startup">Startup</option>
        </select><br><br>
        
        <button type="submit">Register</button>
    </form>

    <p><a href="/">Kembali ke Home</a> | <a href="/login">Sudah punya akun? Login</a></p>

    <script>
        const baseUrl = '/api/v1';

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const object = {};
            formData.forEach((value, key) => object[key] = value);

            try {
                const response = await fetch(`${baseUrl}/auth/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(object)
                });
                const data = await response.json();
                
                if (response.ok) {
                    let token = data.token || (data.data && data.data.token) || data.access_token;
                    if (token) {
                        alert('Register Berhasil!');
                        localStorage.setItem('token', token);
                        window.location.href = '/dashboard';
                    } else {
                        alert('Register Berhasil tapi token tidak ditemukan! Silakan login manual.');
                        window.location.href = '/login';
                    }
                } else {
                    alert('Register gagal: ' + (data.message || JSON.stringify(data)));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    </script>
</body>
</html>
