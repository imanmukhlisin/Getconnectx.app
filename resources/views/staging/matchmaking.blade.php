<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectX | SAW Match Engine (Final Spec)</title>
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

        /* --- DOCUMENTATION SECTION (FULL SPEC) --- */
        .doc-section { margin-top: 100px; padding-top: 80px; border-top: 2px solid #e8e8ed; }
        .doc-card { background: white; padding: 80px; border-radius: 40px; color: #1d1d1f; box-shadow: 0 20px 40px rgba(0,0,0,0.02); }
        .doc-card h2 { font-size: 2.2rem; font-weight: 800; margin-bottom: 40px; letter-spacing: -0.03em; }
        .doc-card h3 { font-size: 1.6rem; font-weight: 700; margin: 40px 0 20px; }
        .doc-card h4 { font-size: 1.2rem; font-weight: 700; margin: 30px 0 15px; color: var(--apple-blue); }
        .doc-card p { font-size: 1.1rem; line-height: 1.6; margin-bottom: 20px; color: #424245; }
        .doc-card hr { border: none; border-top: 1px solid #d2d2d7; margin: 50px 0; }
        .doc-card table { width: 100%; border-collapse: collapse; margin: 25px 0; border: 1px solid #d2d2d7; border-radius: 12px; overflow: hidden; }
        .doc-card th { background: #f5f5f7; padding: 15px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #86868b; text-align: left; }
        .doc-card td { padding: 15px; border: 1px solid #f5f5f7; font-size: 1rem; color: #1d1d1f; }
        .doc-card code { background: #f5f5f7; padding: 3px 8px; border-radius: 6px; font-family: monospace; color: #d6006d; }
        .doc-card ul { margin-left: 20px; margin-bottom: 20px; }
        .doc-card li { margin-bottom: 10px; font-size: 1.1rem; color: #424245; }
        .simulation-example { background: #fbfbfd; padding: 40px; border-radius: 24px; border: 1px solid #d2d2d7; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Intelligence.</h1>
            <p class="subtitle">Matchmaking Engine Simulator v5.0 ({{ strtoupper($mode) }} MODE)</p>
        </header>

        <div class="sim-grid">
            <!-- User A -->
            <div class="card">
                <h2>Initiator (Ideal Profile).</h2>
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
                    <select id="a-commitment">@foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach</select>
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
                <h2>Target (Candidate Profile).</h2>
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
                    <select id="b-commitment">@foreach($commitments as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach</select>
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
                    <label>locationScore (GAP Level)</label>
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
                <div class="score-label">MATCH COMPATIBILITY</div>
            </div>
            <div class="avg-row">
                <div class="avg-item">
                    <div style="font-weight:800; font-size:2rem;" id="ncf-val">0.0</div>
                    <div style="font-size:0.8rem; color:var(--apple-text-dim); font-weight:600;">NCF (CORE 60%)</div>
                </div>
                <div class="avg-item">
                    <div style="font-weight:800; font-size:2rem;" id="nsf-val">0.0</div>
                    <div style="font-size:0.8rem; color:var(--apple-text-dim); font-weight:600;">NSF (SEC 40%)</div>
                </div>
            </div>
        </div>

        <!-- 🧠 FULL DOCUMENTATION SECTION (100% PARITY) -->
        <div class="doc-section">
            <div class="doc-card">
                <h2>🧠 Matchmaking Engine Specification: SAW + Profile Matching</h2>
                <p>Dokumen ini menjelaskan logika algoritma matchmaking terbaru yang menggabungkan metode <strong>Simple Additive Weighting (SAW)</strong> dan <strong>Profile Matching</strong>. Algoritma ini dirancang untuk memberikan skor kecocokan yang lebih objektif dan manusiawi bagi pengguna ConnectX.</p>
                <hr>
                
                <h3>1. Metodologi: Profile Matching</h3>
                <p>Berbeda dengan pembobotan statis, Profile Matching menghitung <strong>GAP (Selisih)</strong> antara profil ideal yang dicari dengan profil target yang ditemukan.</p>
                <h4>Tabel Bobot Nilai GAP</h4>
                <p>Nilai GAP dikonversi menjadi bobot nilai standar (1-5) untuk menormalkan perhitungan:</p>
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
                <p>Fokus pada kompatibilitas dasar co-founder.</p>
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
                <p>Evaluasi mendalam untuk akurasi maksimal.</p>
                <table>
                    <thead><tr><th>Tipe</th><th>Variabel</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Core (60%)</strong></td><td><code>modeFit</code>, <code>skillComp</code></td><td>Dasar matchmaking.</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>industryFit</code></td><td>Relevansi sektor startup.</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>experienceFit</code></td><td>Kedalaman jam terbang.</td></tr>
                        <tr><td><strong>Core (60%)</strong></td><td><code>stageFit</code></td><td>Kesesuaian tahap startup.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>commitmentFit</code></td><td>Ketersediaan waktu.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>locationScore</code></td><td>Proksimitas geografis & remote readiness.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>leadershipFit</code></td><td>Gaya kepemimpinan.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>languageFit</code></td><td>Kemampuan komunikasi.</td></tr>
                        <tr><td><strong>Secondary (40%)</strong></td><td><code>educationFit</code></td><td>Latar belakang pendidikan.</td></tr>
                    </tbody>
                </table>
                <hr>

                <h3>3. Rumus Kalkulasi (SAW)</h3>
                <p>Setelah nilai CF dan SF didapatkan, skor akhir dihitung menggunakan rumus SAW:</p>
                <p style="font-size: 1.4rem; font-weight: 700; color: var(--apple-blue); text-align: center; background: #f5f5f7; padding: 20px; border-radius: 12px;">Total Score = (60% × Rata-rata Nilai CF) + (40% × Rata-rata Nilai SF)</p>
                <p><strong>Tahapan:</strong></p>
                <ol>
                    <li><strong>Normalisasi:</strong> Mengonversi setiap variabel menjadi nilai 1-5 berdasarkan tabel GAP.</li>
                    <li><strong>Rata-rata CF (NCF):</strong> Total nilai Core Factors / Jumlah variabel CF.</li>
                    <li><strong>Rata-rata SF (NSF):</strong> Total nilai Secondary Factors / Jumlah variabel SF.</li>
                    <li><strong>Final Score:</strong> Hasil penggabungan bobot dipetakan ke skala 0-100.</li>
                </ol>
                <hr>

                <h3>4. Contoh Simulasi (User Pro)</h3>
                <div class="simulation-example">
                    <p>Misalkan seorang user mencari partner dengan data sebagai berikut:</p>
                    <ul>
                        <li><strong>Core Factors:</strong> <code>modeFit</code> (5.0), <code>skillComp</code> (5.0), <code>industryFit</code> (3.0), <code>experienceFit</code> (5.0), <code>stageFit</code> (4.5).</li>
                        <li><strong>Rata-rata CF (NCF):</strong> (5+5+3+5+4.5) / 5 = <strong>4.5</strong></li>
                        <li><strong>Secondary Factors:</strong> <code>commitmentFit</code> (4.0), <code>locationScore</code> (2.0), <code>leadershipFit</code> (5.0), <code>languageFit</code> (5.0), <code>educationFit</code> (5.0).</li>
                        <li><strong>Rata-rata SF (NSF):</strong> (4+2+5+5+5) / 5 = <strong>4.2</strong></li>
                    </ul>
                    <p><strong>Skor Akhir:</strong> (60% × 4.5) + (40% × 4.2) = 2.7 + 1.68 = <strong>4.38</strong></p>
                    <p><strong>Konversi ke Skala 100:</strong> (4.38 / 5.0) × 100 = <strong>87.6% (Excellent Match)</strong></p>
                </div>
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
