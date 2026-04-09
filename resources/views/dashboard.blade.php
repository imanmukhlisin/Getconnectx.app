<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ConnectX</title>
</head>
<body>
    <h1>Dashboard Utama</h1>
    <p>Selamat datang! Ini adalah halaman dashboard.</p>
    
    <div>
        <h3>Data Profil Anda</h3>
        <pre id="profileData">Loading...</pre>
    </div>

    <div style="margin: 20px 0; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;">
        <h3>Alur Registrasi & Onboarding (Manual Testing)</h3>
        <ul>
            <li><a href="/verify-email">Langkah 2: Verifikasi Email</a></li>
            <li><a href="/verify-whatsapp">Langkah 3: Verifikasi WhatsApp</a></li>
            <li><a href="/onboarding/stage-a">Langkah 4: Setup Stage A (Identity)</a></li>
            <li><a href="/onboarding/stage-b">Langkah 5: Setup Stage B (Technical)</a></li>
        </ul>
    </div>

    <button id="logoutBtn">Logout</button>

    <script>
        // Cek LocalStorage untuk token auth
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Anda belum login, silahkan login terlebih dahulu.');
            window.location.href = '/login';
        } else {
            fetchProfile();
        }

        async function fetchProfile() {
            try {
                const response = await fetch('/api/v1/profile', {
                    method: 'GET',
                    headers: { 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                // Jika unauthorized, hapus token dan kembali ke login
                if (response.status === 401 || response.status === 403) {
                    alert('Sesi anda telah berakhir atau tidak valid. Silakan login kembali.');
                    localStorage.removeItem('token');
                    window.location.href = '/login';
                    return;
                }

                const data = await response.json();
                document.getElementById('profileData').textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('profileData').textContent = 'Error: ' + error.message;
            }
        }

        document.getElementById('logoutBtn').addEventListener('click', function() {
            localStorage.removeItem('token');
            window.location.href = '/login';
        });
    </script>
</body>
</html>
