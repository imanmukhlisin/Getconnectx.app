<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stage A Identity - ConnectX</title>
</head>
<body>
    <h1>Onboarding Stage A (Identity)</h1>
    
    <p>Lengkapi profil identitas Anda.</p>

    <form id="stageAForm">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="name" placeholder="Nama Anda" required><br><br>
        
        <label>Username / Handle:</label><br>
        <input type="text" name="username" placeholder="Username (Opsional)"><br><br>

        <label>Bio Singkat:</label><br>
        <textarea name="bio" rows="3" placeholder="Bio..."></textarea><br><br>

        <!-- Tambahkan field lain jika ada (misal company, title dll) -->
        <label>Pekerjaan / Job Title:</label><br>
        <input type="text" name="job_title" placeholder="Job Title"><br><br>

        <button type="submit">Simpan & Lanjut ke Stage B</button>
    </form>

    <div style="margin-top: 20px;">
        <a href="/dashboard">Kembali ke Dashboard</a>
    </div>

    <script>
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Anda belum login.');
            window.location.href = '/login';
        }

        const baseUrl = '/api/v1/profile';

        document.getElementById('stageAForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const object = {};
            formData.forEach((value, key) => {
                if (value.trim() !== '') {
                    object[key] = value;
                }
            });

            try {
                // Method bisa PUT sesuai dengan routes: Route::put('stage-a-identity', ...)
                const response = await fetch(`${baseUrl}/stage-a-identity`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(object)
                });
                const data = await response.json();
                
                if (response.ok) {
                    alert('Stage A Berhasil Disimpan!');
                    window.location.href = '/onboarding/stage-b';
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
