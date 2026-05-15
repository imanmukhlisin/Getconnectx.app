<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectX | Intelligence Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --apple-bg: #f5f5f7;
            --apple-card: #ffffff;
            --apple-blue: #0071e3;
            --apple-text: #1d1d1f;
            --apple-text-dim: #86868b;
            --apple-border: #d2d2d7;
            --radius: 20px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, sans-serif; }
        
        body {
            background-color: var(--apple-bg);
            color: var(--apple-text);
            padding: 80px 20px;
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 1000px; margin: 0 auto; }
        
        header { text-align: center; margin-bottom: 80px; }
        h1 { font-size: 3.5rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 10px; }
        p.subtitle { color: var(--apple-text-dim); font-size: 1.2rem; font-weight: 400; }

        .badge { display: inline-block; padding: 6px 14px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; background: #e8e8ed; color: #515154; margin-bottom: 20px; }

        .sim-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        @media (max-width: 850px) { .sim-grid { grid-template-columns: 1fr; } }

        .card { 
            background: var(--apple-card); 
            border-radius: var(--radius); 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .card:hover { transform: translateY(-5px); }
        .card h2 { font-size: 1.5rem; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid var(--apple-border); padding-bottom: 15px; }

        .field { margin-bottom: 25px; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--apple-text-dim); margin-bottom: 10px; }
        
        select, input { 
            width: 100%; 
            background: #ffffff; 
            border: 1px solid var(--apple-border); 
            border-radius: 12px; 
            padding: 14px; 
            color: var(--apple-text); 
            font-size: 1rem; 
            transition: all 0.3s;
        }
        select:focus, input:focus { outline: none; border-color: var(--apple-blue); box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1); }

        .tag-wall { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #fbfbfd; padding: 20px; border-radius: 16px; max-height: 180px; overflow-y: auto; border: 1px solid var(--apple-border); }
        .tag-pill { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer; padding: 5px; color: var(--apple-text); }
        .tag-pill input { width: auto; height: 18px; width: 18px; accent-color: var(--apple-blue); }

        .btn-run { 
            width: 100%; 
            padding: 20px; 
            border-radius: 99px; 
            border: none; 
            background: var(--apple-blue); 
            color: white; 
            font-weight: 600; 
            font-size: 1.1rem; 
            cursor: pointer; 
            transition: all 0.3s; 
            margin-top: 20px;
        }
        .btn-run:hover { opacity: 0.9; transform: scale(0.98); }

        #results { display: none; margin-top: 60px; animation: slideUp 0.8s cubic-bezier(0.165, 0.84, 0.44, 1); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }

        .score-section { display: flex; flex-direction: column; align-items: center; margin-bottom: 50px; }
        .score-circle { 
            width: 200px; 
            height: 200px; 
            border-radius: 50%; 
            background: white; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            border: 10px solid #f2f2f7;
            position: relative;
        }
        .score-val { font-size: 3.5rem; font-weight: 700; color: var(--apple-blue); }
        .score-label { font-size: 0.8rem; font-weight: 600; color: var(--apple-text-dim); text-transform: uppercase; }

        .averages { display: flex; gap: 20px; justify-content: center; margin-bottom: 40px; }
        .avg-box { background: #fbfbfd; padding: 20px 30px; border-radius: 20px; text-align: center; border: 1px solid var(--apple-border); }
        .avg-num { font-size: 1.8rem; font-weight: 700; }
        .avg-txt { font-size: 0.75rem; color: var(--apple-text-dim); }

        .breakdown { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .b-card { background: #ffffff; padding: 25px; border-radius: 20px; border: 1px solid var(--apple-border); }
        .b-lbl { font-size: 0.75rem; font-weight: 700; color: var(--apple-text-dim); text-transform: uppercase; margin-bottom: 10px; }
        .b-val { font-size: 1.3rem; font-weight: 600; color: var(--apple-text); }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="badge">{{ strtoupper($mode) }} Match Engine</div>
            <h1>Match Intelligence.</h1>
            <p class="subtitle">Designed for simplicity. Engineered for precision.</p>
        </header>

        <div class="sim-grid">
            <!-- User A -->
            <div class="card">
                <h2>Initiator.</h2>
                <div class="field">
                    <label>Professional Role</label>
                    <select id="a-role">
                        @foreach($roles as $main => $subCategories)
                            @foreach($subCategories as $sub => $list)
                                <optgroup label="{{ $main }} › {{ $sub }}">
                                    @foreach($list as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
                                </optgroup>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Industry Domains</label>
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
                    <label>Experience</label>
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

            <!-- User B -->
            <div class="card">
                <h2>Candidate.</h2>
                <div class="field">
                    <label>Professional Role</label>
                    <select id="b-role">
                        @foreach($roles as $main => $subCategories)
                            @foreach($subCategories as $sub => $list)
                                <optgroup label="{{ $main }} › {{ $sub }}">
                                    @foreach($list as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
                                </optgroup>
                            @endforeach
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
                    <label>Communication</label>
                    <select id="b-language">
                        @foreach($languages as $lan) <option value="{{ $lan }}">{{ $lan }}</option> @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        <button class="btn-run" onclick="runAnalysis()">Run Intelligence Analysis</button>

        <div id="results" class="card">
            <div class="score-section">
                <div class="score-circle">
                    <div class="score-label">Match.</div>
                    <div class="score-val" id="score-text">0%</div>
                </div>
            </div>

            <div class="averages">
                <div class="avg-box">
                    <div class="avg-num" id="ncf-val">0.0</div>
                    <div class="avg-txt">Core Factor (NCF)</div>
                </div>
                <div class="avg-box">
                    <div class="avg-num" id="nsf-val">0.0</div>
                    <div class="avg-txt">Secondary Factor (NSF)</div>
                </div>
            </div>
            
            <div class="breakdown" id="breakdown-list">
                <!-- Dynamic -->
            </div>
        </div>
    </div>

    <script>
        async function runAnalysis() {
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
                    language: document.getElementById('b-language')?.value
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
                <div class="b-card">
                    <div class="b-lbl">${b.label}</div>
                    <div class="b-val">${b.value}</div>
                </div>
            `).join('');

            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    </script>
</body>
</html>
