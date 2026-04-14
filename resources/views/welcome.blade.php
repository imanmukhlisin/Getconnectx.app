<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - ConnectX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc; /* Minimalist White */
            --text-color: #0f172a;
            --accent: #2563eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* --- Global Mouse Spotlight --- */
        .spotlight {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 1;
            /* Efek lampu senter kebiruan ngikutin kursor */
            background: radial-gradient(circle 800px at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(219, 234, 254, 0.4), transparent 80%);
            transition: background 0.1s ease;
        }

        /* --- 3D Parallax Grid --- */
        .grid-bg {
            position: absolute;
            width: 200vw;
            height: 200vh;
            top: -50%;
            left: -50%;
            background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px);
            background-size: 50px 50px;
            z-index: 0;
            /* Grid miring yang ngikutin koordinat mouse */
            transform: perspective(600px) rotateX(var(--grid-rx, 0deg)) rotateY(var(--grid-ry, 0deg));
            transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        /* --- Main Title Container --- */
        .container {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 4rem;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        h1 {
            font-size: 6rem;
            font-weight: 900;
            letter-spacing: -4px;
            color: #000;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        /* Efek teks X-Ray ngikutin mouse saat hover di atas teks */
        h1::before {
            content: "ConnectX";
            position: absolute;
            top: 0; left: 0;
            color: transparent;
            -webkit-text-stroke: 2px var(--accent);
            clip-path: circle(0% at var(--mouse-local-x, 50%) var(--mouse-local-y, 50%));
            transition: clip-path 0.1s;
            pointer-events: none;
        }

        .container:hover h1::before {
            clip-path: circle(120px at var(--mouse-local-x) var(--mouse-local-y));
        }

        p.subtitle {
            font-size: 1.3rem;
            color: #64748b;
            margin-bottom: 3.5rem;
            font-weight: 300;
            letter-spacing: -0.5px;
        }

        /* --- Insane Mouse Hover Buttons --- */
        .buttons {
            display: flex;
            gap: 2rem;
            justify-content: center;
            align-items: center;
        }

        /* Area sensitif (Membesarkan area hover supaya ditarik magnet dari jauh) */
        .btn-wrapper {
            position: relative;
            padding: 30px; 
        }

        .btn {
            position: relative;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 180px;
            height: 60px;
            border-radius: 40px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            overflow: hidden;
            /* Efek goyang (magnet) */
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.25);
        }

        /* Primary: Hitam murni ke Gradien Biru */
        .btn-primary {
            background-color: #0f172a;
            color: #fff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary .btn-fill {
            background: linear-gradient(135deg, #1d4ed8, #60a5fa);
        }

        /* Secondary: Putih Bersih ke Abu-abu halus */
        .btn-secondary {
            background-color: #fff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary .btn-fill {
            background: #f1f5f9;
        }

        /* Animasi Percikan Lingkaran dari arah Cursor (Liquid Fill) */
        .btn-fill {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            top: var(--btn-y);
            left: var(--btn-x);
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            z-index: 0;
            pointer-events: none;
        }

        .btn:hover .btn-fill {
            transform: translate(-50%, -50%) scale(1);
        }

        .btn span {
            position: relative;
            z-index: 1;
            transition: color 0.3s ease;
        }

    </style>
</head>
<body>
    <div class="grid-bg" id="gridBg"></div>
    <div class="spotlight" id="spotlight"></div>

    <div class="container" id="mainContainer">
        <h1>ConnectX</h1>
        <p class="subtitle">Experience the Gravity of Connection</p>

        <div class="buttons">
            <!-- Magnetic Auth Buttons -->
            <div class="btn-wrapper" onmousemove="magnetEffect(event, this)" onmouseleave="resetMagnet(this)">
                <a href="/login" class="btn btn-primary" onmousemove="btnFillEffect(event, this)">
                    <div class="btn-fill"></div>
                    <span>Login</span>
                </a>
            </div>

            <div class="btn-wrapper" onmousemove="magnetEffect(event, this)" onmouseleave="resetMagnet(this)">
                <a href="/register" class="btn btn-secondary" onmousemove="btnFillEffect(event, this)">
                    <div class="btn-fill"></div>
                    <span>Register</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        /* --- 1. Global Background Parallax & Spotlight --- */
        document.addEventListener('mousemove', (e) => {
            const x = e.clientX;
            const y = e.clientY;
            const w = window.innerWidth;
            const h = window.innerHeight;

            // Update posisi lampu spotlight kebiruan
            document.documentElement.style.setProperty('--mouse-x', `${x}px`);
            document.documentElement.style.setProperty('--mouse-y', `${y}px`);

            // Miringkan Grid menyesuaikan titik pandang mouse (Parallax)
            const rx = (y / h - 0.5) * 8; // Max 4 deg rotation
            const ry = (0.5 - x / w) * 8; 
            document.documentElement.style.setProperty('--grid-rx', `${rx}deg`);
            document.documentElement.style.setProperty('--grid-ry', `${ry}deg`);
        });

        /* --- 2. X-Ray Text Reveal Effect --- */
        const container = document.getElementById('mainContainer');
        container.addEventListener('mousemove', (e) => {
            const rect = container.getBoundingClientRect();
            document.documentElement.style.setProperty('--mouse-local-x', `${e.clientX - rect.left}px`);
            document.documentElement.style.setProperty('--mouse-local-y', `${e.clientY - rect.top}px`);
        });

        /* --- 3. Magnetic Button Effect (Ditarik sebelum disentuh) --- */
        function magnetEffect(e, wrapper) {
            const btn = wrapper.querySelector('.btn');
            const rect = wrapper.getBoundingClientRect();
            
            // Hitung jarak cursor dari TENGAN tombol
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            // Tombol bergeser kecil ke arah kursor
            btn.style.transform = `translate(${x * 0.4}px, ${y * 0.4}px)`;
        }

        function resetMagnet(wrapper) {
            const btn = wrapper.querySelector('.btn');
            btn.style.transform = `translate(0px, 0px)`;
        }

        /* --- 4. Tombol Fluid Fill ngikutin titik klik/hover --- */
        function btnFillEffect(e, btn) {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Lempar variabel X, Y ke CSS supaya animasi bulatannya membesar dari posisi cursor itu!
            btn.style.setProperty('--btn-x', `${x}px`);
            btn.style.setProperty('--btn-y', `${y}px`);
        }

        /* --- JWT Redirect Logic --- */
        if (localStorage.getItem('token')) {
            document.body.style.opacity = '0';
            setTimeout(() => { window.location.href = '/dashboard'; }, 400);
        }
    </script>
</body>
</html>
