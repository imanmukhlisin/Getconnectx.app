<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectX | Match Engine ({{ strtoupper($mode) }})</title>
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

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--apple-bg); color: var(--apple-text); padding: 60px 20px; -webkit-font-smoothing: antialiased; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        header { text-align: center; margin-bottom: 60px; }
        h1 { font-size: 3rem; font-weight: 700; letter-spacing: -0.02em; }
        p.subtitle { color: var(--apple-text-dim); font-size: 1.1rem; margin-top: 10px; }

        .card { background: var(--apple-card); border-radius: var(--radius); padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card h2 { font-size: 1.4rem; font-weight: 600; margin-bottom: 25px; border-bottom: 1px solid var(--apple-border); padding-bottom: 15px; }

        .sim-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        @media (max-width: 800px) { .sim-grid { grid-template-columns: 1fr; } }

        .field { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--apple-text-dim); margin-bottom: 8px; text-transform: uppercase; }
        
        select { width: 100%; background: #ffffff; border: 1px solid var(--apple-border); border-radius: 12px; padding: 12px; color: var(--apple-text); font-size: 0.95rem; }
        
        .tag-wall { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #fbfbfd; padding: 15px; border-radius: 12px; max-height: 140px; overflow-y: auto; border: 1px solid var(--apple-border); }
        .tag-pill { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; padding: 4px; }
        .tag-pill input { height: 16px; width: 16px; accent-color: var(--apple-blue); }

        .btn-run { width: 100%; padding: 22px; border-radius: 99px; border: none; background: var(--apple-blue); color: white; font-weight: 600; font-size: 1.2rem; cursor: pointer; transition: 0.2s; margin-top: 20px; box-shadow: 0 10px 20px rgba(0, 113, 227, 0.2); }
        .btn-run:hover { opacity: 0.95; transform: scale(0.99); }

        #results { display: none; }
        .score-box { text-align: center; padding: 40px; }
        .score-val { font-size: 5rem; font-weight: 800; color: var(--apple-blue); }
        .avg-row { display: flex; justify-content: center; gap: 20px; margin-top: 20px; }
        .avg-item { background: #fbfbfd; padding: 20px 40px; border-radius: 20px; border: 1px solid var(--apple-border); text-align: center; }

        .geo-slider { background: #fbfbfd; padding: 20px; border-radius: 16px; border: 1px dashed var(--apple-border); margin-top: 10px; }
        input[type="range"] { width: 100%; accent-color: var(--apple-blue); margin: 15px 0; }

        /* Documentation Style */
        .doc-section { margin-top: 80px; padding-top: 60px; border-top: 1px solid var(--apple-border); }
        .doc-content { background: white; padding: 60px; border-radius: 30px; font-size: 1rem; line-height: 1.6; color: #333; }
        .doc-content h2 { font-size: 2rem; margin-bottom: 20px; color: #000; font-weight: 700; }
        .doc-content h3 { margin-top: 30px; font-size: 1.4rem; color: #000; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .doc-content table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem; }
        .doc-content th, .doc-content td { text-align: left; padding: 12px; border: 1px solid #eee; }
        .doc-content th { background: #fafafa; font-weight: 700; text-transform: uppercase; color: #888; font-size: 0.7rem; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Intelligence.</h1>
            <p class="subtitle">Matchmaking Engine Simulator v4.1 ({{ strtoupper($mode) }} MODE)</p>
        </header>

        <div class="sim-grid">
            <!-- User A -->
            <div class="card">
                <h2>Initiator (Ideal).</h2>
                <div class="field">
                    <label>skillComp (Professional Role)</label>
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
                    <label>industryFit (Domain Tags)</label>
                    <div class="tag-wall" id="a-tags">
                        @foreach($industries as $t)
                            <label class="tag-pill"><input type="checkbox" value="{{ $t }}"> <span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>modeFit (Commitment)</label>
                    <select id="a-commitment">
                        @foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach
                    </select>
                </div>
                @if($mode === 'pro')
                <div class="field">
                    <label>experienceFit</label>
                    <select id="a-experience">@foreach($experience as $e) <option value="{{ $e }}">{{ $e }}</option> @endforeach</select>
                </div>
                <div class="field">
                    <label>stageFit</label>
                    <select id="a-stage">@foreach($stages as $s) <option value="{{ $s }}">{{ $s }}</option> @endforeach</select>
                </div>
                <div class="field">
                    <label>educationFit</label>
                    <select id="a-education">@foreach($education as $ed) <option value="{{ $ed }}">{{ $ed }}</option> @endforeach</select>
                </div>
                @endif
            </div>

            <!-- User B -->
            <div class="card">
                <h2>Target (Candidate).</h2>
                <div class="field">
                    <label>skillComp</label>
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
                    <label>industryFit</label>
                    <div class="tag-wall" id="b-tags">
                        @foreach($industries as $t)
                            <label class="tag-pill"><input type="checkbox" value="{{ $t }}"> <span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>commitmentFit</label>
                    <select id="b-commitment">
                        @foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach
                    </select>
                </div>
                @if($mode === 'pro')
                <div class="field">
                    <label>leadershipFit</label>
                    <select id="b-leadership">@foreach($leadership as $l) <option value="{{ $l }}">{{ $l }}</option> @endforeach</select>
                </div>
                <div class="field">
                    <label>languageFit</label>
                    <select id="b-language">@foreach($languages as $lan) <option value="{{ $lan }}">{{ $lan }}</option> @endforeach</select>
                </div>
                <div class="geo-slider">
                    <label>locationScore (GAP)</label>
                    <input type="range" id="geo-dist" min="0" max="4" value="0">
                    <div style="font-weight: 700; color: var(--apple-blue);">GAP: <span id="geo-val">0</span></div>
                </div>
                @endif
            </div>
        </div>

        <button class="btn-run" onclick="calculate()">Run Intelligence Analysis</button>

        <div id="results" class="card">
            <div class="score-box">
                <div class="score-val" id="score-text">0%</div>
                <div class="score-label">MATCH SCORE (SAW)</div>
            </div>
            <div class="avg-row">
                <div class="avg-item">
                    <div style="font-weight:800; font-size:2.2rem;" id="ncf-val">0.0</div>
                    <div style="font-size:0.75rem; color:var(--apple-text-dim); font-weight:600;">NCF (60%)</div>
                </div>
                <div class="avg-item">
                    <div style="font-weight:800; font-size:2.2rem;" id="nsf-val">0.0</div>
                    <div style="font-size:0.75rem; color:var(--apple-text-dim); font-weight:600;">NSF (40%)</div>
                </div>
            </div>
        </div>

        <!-- Documentation Section -->
        <div class="doc-section">
            <div class="doc-content">
                <h2>🧠 Matchmaking Engine Specification: SAW + Profile Matching</h2>
                <p>Dokumen ini menjelaskan logika matchmaking ConnectX menggunakan metode <strong>Simple Additive Weighting (SAW)</strong> dan <strong>Profile Matching (GAP Analysis)</strong>.</p>
                <hr>
                <h3>1. Metodologi: Profile Matching</h3>
                <table>
                    <thead><tr><th>Selisih (Gap)</th><th>Bobot Nilai</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <tr><td>0</td><td>5.0</td><td>Kompetensi sesuai (Ideal)</td></tr>
                        <tr><td>1</td><td>4.5</td><td>Kompetensi kelebihan 1 tingkat</td></tr>
                        <tr><td>-1</td><td>4.0</td><td>Kompetensi kekurangan 1 tingkat</td></tr>
                        <tr><td>2</td><td>3.5</td><td>Kompetensi kelebihan 2 tingkat</td></tr>
                        <tr><td>-2</td><td>3.0</td><td>Kompetensi kekurangan 2 tingkat</td></tr>
                    </tbody>
                </table>
                <h3>2. Variabel Gating</h3>
                <p><strong>User FREE:</strong> <code>modeFit</code>, <code>skillComp</code>, <code>industryFit</code>, <code>commitmentFit</code>.</p>
                <p><strong>User PRO:</strong> Full 10 Variables (Experience, Stage, Location, Leadership, Language, Education).</p>
            </div>
        </div>
    </div>

    <script>
        const slider = document.getElementById('geo-dist');
        if(slider) slider.oninput = function() { document.getElementById('geo-val').innerText = this.value; }

        async function calculate() {
            const getTags = (id) => Array.from(document.querySelectorAll(`#${id} input:checked`)).map(el => el.value);
            const payload = {
                mode: '{{ $mode }}',
                userA: {
                    role: document.getElementById('a-role').value,
                    tags: getTags('a-tags'),
                    commitment: document.getElementById('a-commitment').value,
                    experience: document.getElementById('a-experience')?.value,
                    stage: document.getElementById('a-stage')?.value,
                    education: document.getElementById('a-education')?.value
                },
                userB: {
                    role: document.getElementById('b-role').value,
                    tags: getTags('b-tags'),
                    commitment: document.getElementById('b-commitment').value,
                    leadership: document.getElementById('b-leadership')?.value,
                    language: document.getElementById('b-language')?.value,
                    gap_location: document.getElementById('geo-dist')?.value || 0
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
            window.scrollTo({ top: 300, behavior: 'smooth' });
        }
    </script>
</body>
</html>
