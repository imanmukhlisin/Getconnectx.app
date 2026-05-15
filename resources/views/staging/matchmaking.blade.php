<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectX | SAW Match Engine</title>
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
        body { background-color: var(--apple-bg); color: var(--apple-text); padding: 60px 20px; -webkit-font-smoothing: antialiased; }
        .container { max-width: 900px; margin: 0 auto; }
        
        header { text-align: center; margin-bottom: 60px; }
        h1 { font-size: 3rem; font-weight: 700; letter-spacing: -0.02em; }
        p.subtitle { color: var(--apple-text-dim); font-size: 1.1rem; margin-top: 10px; }

        .card { 
            background: var(--apple-card); 
            border-radius: var(--radius); 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .card h2 { font-size: 1.4rem; font-weight: 600; margin-bottom: 25px; border-bottom: 1px solid var(--apple-border); padding-bottom: 15px; }

        .sim-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 800px) { .sim-grid { grid-template-columns: 1fr; } }

        .field { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--apple-text-dim); margin-bottom: 8px; text-transform: uppercase; }
        
        select { width: 100%; background: #ffffff; border: 1px solid var(--apple-border); border-radius: 12px; padding: 12px; color: var(--apple-text); font-size: 0.95rem; }
        
        .tag-wall { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #fbfbfd; padding: 15px; border-radius: 12px; max-height: 140px; overflow-y: auto; border: 1px solid var(--apple-border); }
        .tag-pill { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; padding: 4px; }
        .tag-pill input { height: 16px; width: 16px; accent-color: var(--apple-blue); }

        .btn-run { width: 100%; padding: 18px; border-radius: 99px; border: none; background: var(--apple-blue); color: white; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-run:hover { opacity: 0.9; transform: scale(0.99); }

        #results { display: none; }
        .score-box { text-align: center; padding: 30px; }
        .score-val { font-size: 4rem; font-weight: 700; color: var(--apple-blue); }
        .avg-row { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
        .avg-item { background: #fbfbfd; padding: 15px 25px; border-radius: 15px; border: 1px solid var(--apple-border); }

        /* Documentation Style */
        .doc-section { margin-top: 80px; padding-top: 60px; border-top: 1px solid var(--apple-border); }
        .doc-content { background: white; padding: 60px; border-radius: 30px; font-size: 1rem; line-height: 1.6; color: #333; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .doc-content h2 { margin: 30px 0 20px; font-size: 1.8rem; color: #000; }
        .doc-content h3 { margin: 25px 0 15px; font-size: 1.3rem; }
        .doc-content table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .doc-content th, .doc-content td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        .doc-content th { font-weight: 700; color: #888; text-transform: uppercase; font-size: 0.75rem; }
        .doc-content code { background: #f5f5f7; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        .doc-content hr { border: none; border-top: 1px solid #eee; margin: 40px 0; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Intelligence.</h1>
            <p class="subtitle">Matchmaking Engine Simulator v3.0 ({{ strtoupper($mode) }})</p>
        </header>

        <div class="sim-grid">
            <!-- User A -->
            <div class="card">
                <h2>Initiator.</h2>
                <div class="field">
                    <label>Role</label>
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
                    <label>Industries</label>
                    <div class="tag-wall" id="a-tags">
                        @foreach($industries as $t)
                            <label class="tag-pill"><input type="checkbox" value="{{ $t }}"> <span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Commitment</label>
                    <select id="a-commitment">
                        @foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach
                    </select>
                </div>
            </div>

            <!-- User B -->
            <div class="card">
                <h2>Target.</h2>
                <div class="field">
                    <label>Role</label>
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
                    <label>Industries</label>
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
            </div>
        </div>

        <button class="btn-run" onclick="calculate()">Analyze Compatibility</button>

        <div id="results" class="card">
            <div class="score-box">
                <div class="score-val" id="score-text">0%</div>
                <div class="score-label">MATCH SCORE</div>
            </div>
            <div class="avg-row">
                <div class="avg-item">
                    <div style="font-weight:700; font-size:1.5rem;" id="ncf-val">0.0</div>
                    <div style="font-size:0.7rem; color:var(--apple-text-dim);">NCF (60%)</div>
                </div>
                <div class="avg-item">
                    <div style="font-weight:700; font-size:1.5rem;" id="nsf-val">0.0</div>
                    <div style="font-size:0.7rem; color:var(--apple-text-dim);">NSF (40%)</div>
                </div>
            </div>
        </div>

        <!-- Documentation Section (EXACT SPEC) -->
        <div class="doc-section">
            <div class="doc-content">
                <h2>🧠 Matchmaking Engine Specification: SAW + Profile Matching</h2>
                <p>Dokumen ini menjelaskan logika algoritma matchmaking terbaru yang menggabungkan metode <strong>Simple Additive Weighting (SAW)</strong> dan <strong>Profile Matching</strong>. Algoritma ini dirancang untuk memberikan skor kecocokan yang lebih objektif dan manusiawi bagi pengguna ConnectX.</p>
                <hr>
                <h3>1. Metodologi: Profile Matching</h3>
                <p>Berbeda dengan pembobotan statis, Profile Matching menghitung <strong>GAP (Selisih)</strong> antara profil ideal yang dicari dengan profil target yang ditemukan.</p>
                <table>
                    <thead><tr><th>Selisih (Gap)</th><th>Bobot Nilai</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <tr><td>0</td><td>5.0</td><td>Kompetensi sesuai (Ideal)</td></tr>
                        <tr><td>1</td><td>4.5</td><td>Kompetensi kelebihan 1 tingkat</td></tr>
                        <tr><td>-1</td><td>4.0</td><td>Kompetensi kekurangan 1 tingkat</td></tr>
                        <tr><td>2</td><td>3.5</td><td>Kompetensi kelebihan 2 tingkat</td></tr>
                        <tr><td>-2</td><td>3.0</td><td>Kompetensi kekurangan 2 tingkat</td></tr>
                        <tr><td>3</td><td>2.5</td><td>Kompetensi kelebihan 3 tingkat</td></tr>
                        <tr><td>-3</td><td>2.0</td><td>Kompetensi kekurangan 3 tingkat</td></tr>
                        <tr><td>4</td><td>1.5</td><td>Kompetensi kelebihan 4 tingkat</td></tr>
                        <tr><td>-4</td><td>1.0</td><td>Kompetensi kekurangan 4 tingkat</td></tr>
                    </tbody>
                </table>
                <hr>
                <h3>2. Pembagian Variabel & Gating (Free vs Pro)</h3>
                <p>Skor dihitung berdasarkan pengelompokan kriteria menjadi <strong>Core Factor (CF)</strong> dan <strong>Secondary Factor (SF)</strong>.</p>
                <h4>💠 User FREE (4 Variabel)</h4>
                <table>
                    <thead><tr><th>Tipe</th><th>Variabel</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Core (60%)</strong></td><td><code>modeFit</code></td><td>Keselarasan niat (Intent alignment).</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>skillComp</code></td><td>Saling melengkapi (Hacker x Hustler).</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>industryFit</code></td><td>Kesamaan minat industri.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>commitmentFit</code></td><td>Kesamaan ketersediaan waktu.</td></tr>
                    </tbody>
                </table>
                <h4>💎 User PRO (10 Variabel)</h4>
                <table>
                    <thead><tr><th>Tipe</th><th>Variabel</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Core (60%)</strong></td><td><code>modeFit</code>, <code>skillComp</code></td><td>Dasar matchmaking.</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>industryFit</code></td><td>Relevansi sektor startup.</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>experienceFit</code></td><td>Kedalaman jam terbang.</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>stageFit</code></td><td>Kesesuaian tahap startup.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>commitmentFit</code></td><td>Ketersediaan waktu.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>locationScore</code></td><td>Proksimitas geografis.</td></tr>
                    </tbody>
                </table>
                <hr>
                <h3>3. Rumus Kalkulasi (SAW)</h3>
                <p><code>Total Score = (60% × Rata-rata Nilai CF) + (40% × Rata-rata Nilai SF)</code></p>
                <p><strong>Tahapan:</strong><br>1. Normalisasi: GAP → 1-5.<br>2. Rata-rata CF (NCF).<br>3. Rata-rata SF (NSF).<br>4. Final Score: Map to 0-100.</p>
            </div>
        </div>
    </div>

    <script>
        async function calculate() {
            const getTags = (id) => Array.from(document.querySelectorAll(`#${id} input:checked`)).map(el => el.value);

            // Clean Payload to prevent 500 errors
            const payload = {
                mode: '{{ $mode }}',
                userA: {
                    role: document.getElementById('a-role').value,
                    tags: getTags('a-tags'),
                    commitment: document.getElementById('a-commitment').value
                },
                userB: {
                    role: document.getElementById('b-role').value,
                    tags: getTags('b-tags'),
                    commitment: document.getElementById('b-commitment').value
                }
            };

            const resp = await fetch('{{ route("staging.calculate") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload)
            });

            if (!resp.ok) { alert('Simulation Error. Please check inputs.'); return; }

            const res = await resp.json();
            document.getElementById('results').style.display = 'block';
            document.getElementById('score-text').innerText = res.score + '%';
            document.getElementById('ncf-val').innerText = res.ncf;
            document.getElementById('nsf-val').innerText = res.nsf;

            window.scrollTo({ top: 400, behavior: 'smooth' });
        }
    </script>
</body>
</html>
