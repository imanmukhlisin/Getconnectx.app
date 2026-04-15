<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password — ConnectX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html {
            height: 100%;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8faf8;
            min-height: 100%;
            color: #1e293b;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
        }
        .brand img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
        }
        .brand span {
            font-size: 18px;
            font-weight: 700;
            color: #3F3D56;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px 36px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 32px rgba(0,0,0,0.06);
            animation: enter 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes enter {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .card-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
        }

        /* Alert */
        .alert {
            display: none;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 20px;
            align-items: center;
            gap: 8px;
        }
        .alert.show { display: flex; }
        .alert-error { background: #fef2f2; color: #dc2626; }
        .alert-success { background: #f0fdf4; color: #16a34a; }

        /* Form */
        .field {
            margin-bottom: 20px;
        }
        .field:last-of-type {
            margin-bottom: 24px;
        }
        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-box input {
            width: 100%;
            height: 48px;
            padding: 0 44px 0 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            color: #1e293b;
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-box input:focus {
            border-color: #6BAC64;
            box-shadow: 0 0 0 3px rgba(107,172,100,0.15);
        }
        .input-box input.has-error {
            border-color: #ef4444;
        }
        .input-box input[readonly] {
            background: #f8fafc;
            color: #64748b;
            cursor: default;
        }
        .input-box .toggle-vis {
            position: absolute;
            right: 12px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            border-radius: 6px;
            transition: color 0.15s, background 0.15s;
        }
        .toggle-vis:hover { color: #475569; background: #f1f5f9; }
        .toggle-vis svg { width: 18px; height: 18px; }

        /* Password checklist */
        .checklist {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 0;
            margin-top: 10px;
        }
        .check-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #94a3b8;
            width: 50%;
            transition: color 0.2s;
        }
        .check-item.pass { color: #16a34a; }
        .check-item svg { width: 14px; height: 14px; flex-shrink: 0; }

        .match-msg {
            font-size: 12px;
            color: #ef4444;
            margin-top: 6px;
            display: none;
        }

        /* Button */
        .btn {
            width: 100%;
            height: 48px;
            background: #6BAC64;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn:hover:not(:disabled) { background: #5d9d56; }
        .btn:active:not(:disabled) { transform: scale(0.98); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn .loader {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        .btn.loading .btn-label { display: none; }
        .btn.loading .loader { display: block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Success */
        .done {
            display: none;
            text-align: center;
            padding: 12px 0 0;
            animation: enter 0.4s ease;
        }
        .done-icon {
            width: 60px;
            height: 60px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .done-icon svg { width: 28px; height: 28px; color: #16a34a; }
        .done h3 { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .done p { font-size: 14px; color: #64748b; line-height: 1.6; }
        .done .hint { margin-top: 20px; font-size: 12px; color: #94a3b8; }

        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer a { color: #6BAC64; text-decoration: none; }

        /* Mobile */
        @media (max-width: 480px) {
            .page { padding: 24px 16px; justify-content: flex-start; padding-top: 48px; }
            .card { padding: 28px 22px 24px; }
            .card-title { font-size: 20px; }
            .check-item { width: 50%; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="brand">
        <img src="https://img.mailinblue.com/10204270/images/content_library/original/69de02f3640b7bcf923bee8f.jpeg" alt="ConnectX">
        <span>ConnectX</span>
    </div>

    <div class="card">
        <div id="formView">
            <h1 class="card-title">Reset Password</h1>
            <p class="card-subtitle">Buat password baru untuk akun Anda.</p>

            <div id="alert" class="alert" role="alert">
                <span id="alertMsg"></span>
            </div>

            <form id="form" novalidate>
                <div class="field">
                    <label for="email">Email</label>
                    <div class="input-box">
                        <input type="email" id="email" value="{{ $email }}" readonly>
                    </div>
                </div>

                <div class="field">
                    <label for="pw">Password Baru</label>
                    <div class="input-box">
                        <input type="password" id="pw" placeholder="Masukkan password baru" autocomplete="new-password">
                        <button type="button" class="toggle-vis" data-target="pw" aria-label="Tampilkan password">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    </div>
                    <div class="checklist" id="checks">
                        <div class="check-item" data-rule="len"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>Min. 8 karakter</span></div>
                        <div class="check-item" data-rule="upper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>Huruf besar</span></div>
                        <div class="check-item" data-rule="lower"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>Huruf kecil</span></div>
                        <div class="check-item" data-rule="num"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>Angka</span></div>
                        <div class="check-item" data-rule="sym"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>Simbol</span></div>
                    </div>
                </div>

                <div class="field">
                    <label for="pw2">Konfirmasi Password</label>
                    <div class="input-box">
                        <input type="password" id="pw2" placeholder="Ulangi password baru" autocomplete="new-password">
                        <button type="button" class="toggle-vis" data-target="pw2" aria-label="Tampilkan password">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    </div>
                    <p class="match-msg" id="matchErr">Password tidak cocok</p>
                </div>

                <button type="submit" class="btn" id="submit" disabled>
                    <span class="btn-label">Reset Password</span>
                    <div class="loader"></div>
                </button>
            </form>
        </div>

        <div class="done" id="doneView">
            <div class="done-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h3>Password Diperbarui</h3>
            <p>Password baru Anda telah tersimpan. Silakan buka kembali aplikasi ConnectX dan login.</p>
            <p class="hint">Halaman ini bisa ditutup.</p>
        </div>
    </div>

    <div class="footer">&copy; {{ date('Y') }} <a href="https://www.getconnectx.app">ConnectX</a></div>
</div>

<script>
    const API = '{{ url("/api/v1/auth") }}';
    const TOKEN = '{{ $token }}';

    const pw = document.getElementById('pw');
    const pw2 = document.getElementById('pw2');
    const btn = document.getElementById('submit');
    const alertEl = document.getElementById('alert');
    const alertMsg = document.getElementById('alertMsg');
    const matchErr = document.getElementById('matchErr');

    const ok = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    const no = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';

    const rules = {
        len: v => v.length >= 8,
        upper: v => /[A-Z]/.test(v),
        lower: v => /[a-z]/.test(v),
        num: v => /\d/.test(v),
        sym: v => /[^A-Za-z0-9]/.test(v),
    };

    function validate() {
        const v = pw.value;
        let allOk = true;
        document.querySelectorAll('.check-item').forEach(el => {
            const r = el.dataset.rule;
            const pass = rules[r](v);
            el.classList.toggle('pass', pass);
            el.querySelector('svg').outerHTML = pass ? ok : no;
            if (!pass) allOk = false;
        });
        const match = v && pw2.value && v === pw2.value;
        matchErr.style.display = pw2.value && !match ? 'block' : 'none';
        pw2.classList.toggle('has-error', pw2.value && !match);
        btn.disabled = !(allOk && match);
    }

    pw.addEventListener('input', validate);
    pw2.addEventListener('input', validate);

    document.querySelectorAll('.toggle-vis').forEach(b => {
        b.addEventListener('click', () => {
            const t = document.getElementById(b.dataset.target);
            t.type = t.type === 'password' ? 'text' : 'password';
        });
    });

    function showAlert(type, msg) {
        alertEl.className = 'alert alert-' + type + ' show';
        alertMsg.textContent = msg;
    }

    document.getElementById('form').addEventListener('submit', async e => {
        e.preventDefault();
        btn.classList.add('loading');
        btn.disabled = true;
        alertEl.classList.remove('show');

        try {
            const res = await fetch(API + '/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    token: TOKEN,
                    password: pw.value,
                    password_confirmation: pw2.value,
                }),
            });
            const data = await res.json();

            if (res.ok) {
                document.getElementById('formView').style.display = 'none';
                document.getElementById('doneView').style.display = 'block';
            } else {
                let msg = data.message || 'Terjadi kesalahan.';
                if (data.errors) {
                    const first = Object.values(data.errors)[0];
                    msg = Array.isArray(first) ? first[0] : first;
                }
                showAlert('error', msg);
                btn.classList.remove('loading');
                validate();
            }
        } catch {
            showAlert('error', 'Gagal menghubungi server.');
            btn.classList.remove('loading');
            validate();
        }
    });
</script>
</body>
</html>
