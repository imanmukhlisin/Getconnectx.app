<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stage B Technical - ConnectX</title>
</head>
<body>
    <h1>Onboarding Stage B (Technical)</h1>
    
    <p>Lengkapi profil teknis Anda (Skills, Tools, dll).</p>

    <form id="stageBForm">
        <label>Skills Utama (pisahkan dengan koma):</label><br>
        <input type="text" name="skills" placeholder="PHP, Laravel, Javascript" style="width: 300px;"><br><br>
        
        <label>Pengalaman (Tahun):</label><br>
        <input type="number" name="experience_years" placeholder="Misal: 3"><br><br>

        <label>Portofolio URL / GitHub:</label><br>
        <input type="url" name="portfolio_url" placeholder="https://github.com/anda" style="width: 300px;"><br><br>

        <!-- Tambahkan checkbok/select jika API mensyaratkannya array -->
        
        <button type="submit">Selesai & Ke Dashboard</button>
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

        document.getElementById('stageBForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const object = {};
            formData.forEach((value, key) => {
                if (value.trim() !== '') {
                    // Coba format string koma dipisah menjadi array untuk 'skills'
                    if (key === 'skills') {
                        object[key] = value.split(',').map(s => s.trim());
                    } else {
                        object[key] = value;
                    }
                }
            });

            try {
                // Method PUT sesuai dengan routes: Route::put('stage-b-technical', ...)
                const response = await fetch(`${baseUrl}/stage-b-technical`, {
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
                    alert('Stage B Berhasil Disimpan! Onboarding selesai!');
                    window.location.href = '/dashboard';
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
