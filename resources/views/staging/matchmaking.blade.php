<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectX | SAW + Profile Matching Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --secondary: #ec4899;
            --bg: #0b0f1a;
            --card-bg: rgba(23, 29, 45, 0.7);
            --text: #ffffff;
            --text-dim: #8ba1c1;
            --accent: #22d3ee;
            --success: #10b981;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body {
            background: var(--bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(236, 72, 153, 0.1) 0%, transparent 40%);
            color: var(--text);
            padding: 50px 20px;
        }

        .container { max-width: 1200px; margin: 0 auto; }
        header { text-align: center; margin-bottom: 60px; }
        h1 { font-size: 3rem; font-weight: 900; background: linear-gradient(135deg, #fff 30%, #6366f1, #22d3ee); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        p.subtitle { color: var(--text-dim); margin-top: 10px; font-weight: 300; }

        .badge { display: inline-block; padding: 6px 18px; border-radius: 99px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; background: rgba(34, 211, 238, 0.1); color: var(--accent); border: 1px solid rgba(34, 211, 238, 0.3); margin-bottom: 20px; }

        .sim-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        @media (max-width: 900px) { .sim-grid { grid-template-columns: 1fr; } }

        .card { background: var(--card-bg); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 40px; padding: 35px; box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.8); }
        .card h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 25px; color: var(--accent); display: flex; align-items: center; gap: 12px; }

        .field { margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; font-weight: 800; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        
        select, input { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 14px; color: white; font-size: 0.9rem; }
        
        .tag-wall { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 16px; max-height: 150px; overflow-y: auto; }
        .tag-pill { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; cursor: pointer; padding: 6px; }
        .tag-pill input { width: auto; }

        .btn-calculate { width: 100%; padding: 22px; border-radius: 22px; border: none; background: linear-gradient(90deg, #6366f1, #22d3ee); color: white; font-weight: 900; font-size: 1.3rem; cursor: pointer; transition: 0.3s; box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.5); }
        .btn-calculate:hover { transform: translateY(-4px); box-shadow: 0 25px 40px -10px rgba(34, 211, 238, 0.5); }

        #results { display: none; margin-top: 50px; animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .score-row { display: flex; justify-content: space-around; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .score-circle { width: 180px; height: 180px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(255,255,255,0.02); border: 2px solid var(--accent); box-shadow: 0 0 30px rgba(34, 211, 238, 0.2); }
        .score-val { font-size: 3rem; font-weight: 900; }
        .score-label { font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; }

        .avg-card { background: rgba(255,255,255,0.03); padding: 20px; border-radius: 20px; text-align: center; min-width: 140px; border: 1px solid rgba(255,255,255,0.05); }
        .avg-val { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
        .avg-label { font-size: 0.65rem; color: var(--text-dim); margin-top: 5px; }

        .breakdown { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .b-item { background: rgba(15, 23, 42, 0.5); padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .b-label { font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
        .b-val { font-size: 1.2rem; font-weight: 700; color: var(--accent); }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="badge">Engine: SAW + Profile Matching</div>
            <h1>Matchmaking Intelligence</h1>
            <p class="subtitle">Decision Support System based on GAP Analysis & Simple Additive Weighting</p>
        </header>

        <div class="sim-grid">
            <!-- User A (Initiator) -->
            <div class="card">
                <h2>👤 Initiator (Ideal)</h2>
                <div class="field">
                    <label>Role</label>
                    <select id="a-role">
                        @foreach($roles as $cat => $list)
                            <optgroup label="{{ $cat }}">
                                @foreach($list as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Industry Tags</label>
                    <div class="tag-wall" id="a-tags">
                        @foreach($industries as $t)
                            <label class="tag-pill"><input type="checkbox" value="{{ $t }}"> <span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Intent / Commitment</label>
                    <select id="a-commitment">
                        @foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach
                    </select>
                </div>
                @if($mode === 'pro')
                <div class="field">
                    <label>Experience Level</label>
                    <select id="a-experience">
                        @foreach($experience as $e) <option value="{{ $e }}">{{ $e }}</option> @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Startup Stage</label>
                    <select id="a-stage">
                        @foreach($stages as $s) <option value="{{ $s }}">{{ $s }}</option> @endforeach
                    </select>
                </div>
                @endif
            </div>

            <!-- User B (Target) -->
            <div class="card">
                <h2>🎯 Target Candidate</h2>
                <div class="field">
                    <label>Role</label>
                    <select id="b-role">
                        @foreach($roles as $cat => $list)
                            <optgroup label="{{ $cat }}">
                                @foreach($list as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Expertise Tags</label>
                    <div class="tag-wall" id="b-tags">
                        @foreach($industries as $t)
                            <label class="tag-pill"><input type="checkbox" value="{{ $t }}"> <span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Commitment</label>
                    <select id="b-commitment">
                        @foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach
                    </select>
                </div>
                @if($mode === 'pro')
                <div class="field">
                    <label>Leadership Style</label>
                    <select id="b-leadership">
                        @foreach($leadership as $l) <option value="{{ $l }}">{{ $l }}</option> @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Primary Language</label>
                    <select id="b-language">
                        @foreach($languages as $lan) <option value="{{ $lan }}">{{ $lan }}</option> @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        <button class="btn-calculate" onclick="calculate()">RUN INTELLIGENCE ANALYSIS</button>

        <div id="results" class="card">
            <div class="score-row">
                <div class="avg-card">
                    <div class="avg-val" id="ncf-val">0.0</div>
                    <div class="avg-label">Core Factor (NCF)</div>
                </div>
                <div class="score-circle">
                    <div class="score-label">Final Score</div>
                    <div class="score-val" id="score-text">0%</div>
                </div>
                <div class="avg-card">
                    <div class="avg-val" id="nsf-val">0.0</div>
                    <div class="avg-label">Secondary (NSF)</div>
                </div>
            </div>
            
            <div class="breakdown" id="breakdown-list">
                <!-- Dynamic -->
            </div>
        </div>
    </div>

    <script>
        async function calculate() {
            const getTags = (id) => Array.from(document.querySelectorAll(`#${id} input:checked`)).map(el => el.value);

            const payload = {
                mode: '{{ $mode }}',
                userA: {
                    role: document.getElementById('a-role').value,
                    tags: getTags('a-tags'),
                    commitment: document.getElementById('a-commitment').value,
                    experience: document.getElementById('a-experience')?.value,
                    stage: document.getElementById('a-stage')?.value
                },
                userB: {
                    role: document.getElementById('b-role').value,
                    tags: getTags('b-tags'),
                    commitment: document.getElementById('b-commitment').value,
                    leadership: document.getElementById('b-leadership')?.value,
                    language: document.getElementById('b-language')?.value,
                    education: 'Bachelor' // Hardcoded for simulation
                }
            };

            const resp = await fetch('{{ route("staging.calculate") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload)
            });

            const res = await resp.json();
            
            document.getElementById('results').style.display = 'block';
            document.getElementById('score-text').innerText = res.score + '%';
            document.getElementById('ncf-val').innerText = res.ncf;
            document.getElementById('nsf-val').innerText = res.nsf;

            const list = document.getElementById('breakdown-list');
            list.innerHTML = res.breakdown.map(b => `
                <div class="b-item">
                    <div class="b-label">${b.label}</div>
                    <div class="b-val">${b.value}</div>
                    <div style="font-size: 0.6rem; color: var(--primary); margin-top: 5px;">Group Weight: ${b.weight}</div>
                </div>
            `).join('');

            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    </script>
</body>
</html>
