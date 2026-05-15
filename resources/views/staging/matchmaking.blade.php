<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectX | {{ $title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.5);
            --secondary: #ec4899;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --text-dim: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .container {
            width: 100%;
            max-width: 900px;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(to right, #818cf8, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .card h2 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-dim);
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .btn-match {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
            margin-bottom: 40px;
        }

        .btn-match:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.5);
        }

        #result-container {
            display: none;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: conic-gradient(var(--primary) 0%, transparent 0%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            position: relative;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }

        .score-circle::after {
            content: '';
            position: absolute;
            width: 130px;
            height: 130px;
            background: var(--bg);
            border-radius: 50%;
        }

        .score-value {
            position: relative;
            z-index: 1;
            font-size: 2.5rem;
            font-weight: 800;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .breakdown-item {
            background: rgba(15, 23, 42, 0.4);
            padding: 15px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
        }

        .breakdown-label {
            font-size: 0.75rem;
            color: var(--text-dim);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .breakdown-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .weight-tag {
            font-size: 0.7rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="badge">{{ strtoupper($mode) }} MODE</div>
            <h1>ConnectX Algorithm Sandbox</h1>
            <p style="color: var(--text-dim)">Simulator koding algoritma matchmaking untuk Project Owner</p>
        </header>

        <div class="grid">
            <!-- User A -->
            <div class="card">
                <h2>👤 Tester Profile</h2>
                <div class="input-group">
                    <label>Role Category</label>
                    <select id="a-role">
                        <option value="Founder">Founder</option>
                        <option value="Builder">Builder</option>
                        <option value="Investor">Investor</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Interests / Tags (koma pisah)</label>
                    <input type="text" id="a-tags" placeholder="AI, SaaS, Laravel..." value="AI, SaaS">
                </div>
                @if($mode === 'pro')
                <div class="input-group">
                    <label>Commitment Level</label>
                    <select id="a-commitment">
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Weekends">Weekends</option>
                    </select>
                </div>
                @endif
            </div>

            <!-- User B -->
            <div class="card">
                <h2>🎯 Candidate Profile</h2>
                <div class="input-group">
                    <label>Role Category</label>
                    <select id="b-role">
                        <option value="Builder">Builder</option>
                        <option value="Founder">Founder</option>
                        <option value="Investor">Investor</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Interests / Tags (koma pisah)</label>
                    <input type="text" id="b-tags" placeholder="Python, React, Fintech..." value="AI, Python">
                </div>
                @if($mode === 'pro')
                <div class="input-group">
                    <label>Commitment Level</label>
                    <select id="b-commitment">
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Weekends">Weekends</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Velocity (Last Active Days)</label>
                    <input type="number" id="b-active" value="1" min="1">
                </div>
                @else
                <div class="input-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="b-pro" style="width: auto;"> Is Pro User?
                    </label>
                </div>
                @endif
            </div>
        </div>

        <button class="btn-match" onclick="calculateMatch()">SIMULATE MATCHMAKING</button>

        <div id="result-container" class="card">
            <div class="score-circle" id="score-circle">
                <div class="score-value" id="score-text">0%</div>
            </div>
            
            <div class="breakdown-grid" id="breakdown-container">
                <!-- Dynamic Content -->
            </div>
        </div>
    </div>

    <script>
        async function calculateMatch() {
            const data = {
                mode: '{{ $mode }}',
                userA: {
                    role: document.getElementById('a-role').value,
                    tags: document.getElementById('a-tags').value,
                    commitment: document.getElementById('a-commitment')?.value
                },
                userB: {
                    role: document.getElementById('b-role').value,
                    tags: document.getElementById('b-tags').value,
                    commitment: document.getElementById('b-commitment')?.value,
                    active_days: document.getElementById('b-active')?.value,
                    is_pro: document.getElementById('b-pro')?.checked
                }
            };

            const response = await fetch('/staging/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            showResult(result);
        }

        function showResult(result) {
            const container = document.getElementById('result-container');
            const scoreText = document.getElementById('score-text');
            const scoreCircle = document.getElementById('score-circle');
            const breakdown = document.getElementById('breakdown-container');

            container.style.display = 'block';
            scoreText.innerText = result.score + '%';
            scoreCircle.style.background = `conic-gradient(var(--primary) ${result.score}%, rgba(255,255,255,0.05) 0%)`;

            breakdown.innerHTML = result.breakdown.map(item => `
                <div class="breakdown-item">
                    <div class="breakdown-label">${item.label}</div>
                    <div class="breakdown-value">${item.value}</div>
                    <div class="weight-tag">${item.weight} Weight</div>
                </div>
            `).join('');

            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    </script>
</body>
</html>
