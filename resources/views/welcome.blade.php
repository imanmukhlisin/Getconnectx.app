<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - ConnectX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --secondary: #3b82f6;
            --bg-color: #030305;
            --text-color: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
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

        /* --- Antigravity Background Objects --- */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 20s infinite ease-in-out alternate;
            z-index: 0;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            top: -100px;
            left: -100px;
            animation-duration: 25s;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #ec4899, var(--primary));
            bottom: -50px;
            right: 10%;
            animation-duration: 18s;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #06b6d4, var(--secondary));
            top: 40%;
            left: 50%;
            animation-duration: 22s;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translateY(0) translateX(0) scale(1); }
            33% { transform: translateY(-30px) translateX(50px) scale(1.1); }
            66% { transform: translateY(20px) translateX(-30px) scale(0.9); }
            100% { transform: translateY(0px) translateX(0) scale(1); }
        }

        /* --- Glassmorphism Container --- */
        .glass-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 3rem 4rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 90%;
            animation: fadeUp 1s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #fff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .subtitle {
            font-size: 1.1rem;
            font-weight: 300;
            color: #94a3b8;
            margin-bottom: 2.5rem;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 10px 20px -10px rgba(139, 92, 246, 0.5);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -10px rgba(139, 92, 246, 0.7);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Particles container (Optional small touch) */
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            background-image: 
                radial-gradient(1px 1px at 20px 30px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 40px 70px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 50px 160px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 90px 40px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 130px 80px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 160px 120px, #ffffff, rgba(0,0,0,0));
            background-repeat: repeat;
            background-size: 200px 200px;
            opacity: 0.3;
            animation: twinkle 10s infinite linear;
        }

        @keyframes twinkle {
            0% { transform: translateY(0); }
            100% { transform: translateY(-200px); }
        }

        @media (min-width: 640px) {
            .action-buttons {
                flex-direction: row;
                justify-content: center;
            }
            .btn {
                width: 160px;
            }
        }
    </style>
</head>
<body>

    <!-- Antigravity Space Background -->
    <div class="stars"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Main Content -->
    <div class="glass-container">
        <h1 class="logo">ConnectX</h1>
        <p class="subtitle">Experience the Gravity of Connection</p>
        
        <div class="action-buttons">
            <a href="/login" class="btn btn-primary">Login</a>
            <a href="/register" class="btn btn-secondary">Register</a>
        </div>
    </div>

    <script>
        // Check if token exists, redirect to dashboard if yes
        const token = localStorage.getItem('token');
        if (token) {
            // Animasi exit sekilas sebelum redirect
            document.querySelector('.glass-container').style.opacity = '0';
            document.querySelector('.glass-container').style.transform = 'translateY(-20px)';
            document.querySelector('.glass-container').style.transition = 'all 0.4s ease';
            
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 400);
        }
    </script>
</body>
</html>
