<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password — ConnectX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #6BAC64;
            --primary-dark: #5A9A53;
            --primary-light: #e8f5e6;
            --accent: #3F3D56;
            --accent-light: #67657e;
            --bg: #f0f4ef;
            --card-bg: #ffffff;
            --error: #ef4444;
            --error-bg: #fef2f2;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --text: #1a1a2e;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 20px 60px rgba(63, 61, 86, 0.12);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Background decoration */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -30%;
            width: 80vw;
            height: 80vw;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(107,172,100,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -40%;
            left: -20%;
            width: 60vw;
            height: 60vw;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(63,61,86,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
        }

        /* Logo section */
        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-section img {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            margin-bottom: 12px;
        }
        .logo-section h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: -0.5px;
        }

        /* Card */
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 44px 36px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.8);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            text-align: center;
            margin-bottom: 36px;
        }
        .card-header .icon-wrapper {
            width: 56px;
            height: 56px;
            background: var(--primary-light);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .card-header .icon-wrapper svg {
            width: 28px;
            height: 28px;
            color: var(--primary);
        }
        .card-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .card-header p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper svg.input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .input-wrapper input {
            width: 100%;
            padding: 14px 48px 14px 42px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            color: var(--text);
            background: #fafafa;
            outline: none;
            transition: all 0.25s ease;
        }
        .input-wrapper input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(107,172,100,0.12);
        }
        .input-wrapper input:focus ~ svg.input-icon {
            color: var(--primary);
        }
        .input-wrapper input.error {
            border-color: var(--error);
            box-shadow: 0 0 0 4px rgba(239,68,68,0.08);
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .toggle-password:hover { color: var(--accent); }
        .toggle-password svg { width: 18px; height: 18px; }

        /* Password strength */
        .password-rules {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 16px;
        }
        .rule {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .rule.valid { color: var(--success); }
        .rule svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* Email input (hidden but used) */
        .email-group {
            margin-bottom: 24px;
        }
        .email-group input {
            width: 100%;
            padding: 14px 14px 14px 42px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            color: var(--text);
            background: #fafafa;
            outline: none;
            transition: all 0.25s ease;
        }
        .email-group input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(107,172,100,0.12);
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 8px;
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(107,172,100,0.35);
        }
        .btn-submit:active:not(:disabled) { transform: translateY(0); }
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .btn-submit .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2.5px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin: 0 auto;
        }
        .btn-submit.loading .btn-text { display: none; }
        .btn-submit.loading .spinner { display: block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Alert messages */
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 24px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .alert.show { display: flex; align-items: flex-start; gap: 10px; }
        .alert svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
        .alert-error { background: var(--error-bg); color: var(--error); border: 1px solid rgba(239,68,68,0.15); }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(16,185,129,0.15); }

        /* Success state */
        .success-view {
            display: none;
            text-align: center;
            padding: 20px 0;
            animation: fadeIn 0.5s ease;
        }
        .success-view .check-circle {
            width: 72px;
            height: 72px;
            background: var(--success-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-view .check-circle svg { width: 36px; height: 36px; color: var(--success); }
        .success-view h3 { font-size: 20px; font-weight: 700; color: var(--accent); margin-bottom: 8px; }
        .success-view p { color: var(--text-muted); font-size: 14px; line-height: 1.6; }
        .success-view .redirect-text {
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 24px;
            color: var(--text-muted);
            font-size: 12px;
        }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .footer a:hover { text-decoration: underline; }

        /* Responsive */
        @media (max-width: 480px) {
            .card { padding: 32px 24px; border-radius: 16px; }
            .card-header h2 { font-size: 20px; }
            .password-rules { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <img src="https://img.mailinblue.com/10204270/images/content_library/original/69de02f3640b7bcf923bee8f.jpeg" alt="ConnectX Logo">
            <h1>ConnectX</h1>
        </div>

        <div class="card">
            <!-- Form View -->
            <div id="formView">
                <div class="card-header">
                    <div class="icon-wrapper">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                    <h2>Buat Password Baru</h2>
                    <p>Masukkan password baru untuk akun ConnectX Anda</p>
                </div>

                <div id="alertBox" class="alert" role="alert">
                    <svg id="alertIcon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"></svg>
                    <span id="alertText"></span>
                </div>

                <form id="resetForm" novalidate>
                    <div class="email-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                            <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                            <input type="password" id="password" name="password" placeholder="Min. 8 karakter" required autocomplete="new-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                                <svg id="eyeIcon1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                        </div>
                        <div class="password-rules" id="passwordRules">
                            <div class="rule" id="rule-length">
                                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <span>Min. 8 karakter</span>
                            </div>
                            <div class="rule" id="rule-upper">
                                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <span>Huruf besar</span>
                            </div>
                            <div class="rule" id="rule-lower">
                                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <span>Huruf kecil</span>
                            </div>
                            <div class="rule" id="rule-number">
                                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <span>Angka</span>
                            </div>
                            <div class="rule" id="rule-symbol">
                                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <span>Simbol (!@#$)</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" required autocomplete="new-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation')" aria-label="Toggle password visibility">
                                <svg id="eyeIcon2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                        </div>
                        <p id="matchError" style="display:none; color: var(--error); font-size: 12px; margin-top: 6px;">Password tidak cocok</p>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <span class="btn-text">Reset Password</span>
                        <div class="spinner"></div>
                    </button>
                </form>
            </div>

            <!-- Success View -->
            <div class="success-view" id="successView">
                <div class="check-circle">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3>Password Berhasil Diubah!</h3>
                <p>Password akun ConnectX Anda telah diperbarui.<br>Silakan login menggunakan password baru.</p>
                <p class="redirect-text">Anda bisa menutup halaman ini dan kembali ke aplikasi.</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} <a href="https://www.getconnectx.app" target="_blank">ConnectX</a> — PT Koneksix Digital Nusantara</p>
        </div>
    </div>

    <script>
        const API_BASE = '{{ url("/api/v1/auth") }}';
        const TOKEN = '{{ $token }}';

        const form = document.getElementById('resetForm');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const submitBtn = document.getElementById('submitBtn');
        const alertBox = document.getElementById('alertBox');
        const alertText = document.getElementById('alertText');
        const alertIcon = document.getElementById('alertIcon');
        const matchError = document.getElementById('matchError');

        // SVG icons for alerts
        const errorIconPath = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>';
        const checkIconPath = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';

        // Password validation rules
        const rules = {
            length: { el: document.getElementById('rule-length'), test: v => v.length >= 8 },
            upper:  { el: document.getElementById('rule-upper'),  test: v => /[A-Z]/.test(v) },
            lower:  { el: document.getElementById('rule-lower'),  test: v => /[a-z]/.test(v) },
            number: { el: document.getElementById('rule-number'), test: v => /[0-9]/.test(v) },
            symbol: { el: document.getElementById('rule-symbol'), test: v => /[^A-Za-z0-9]/.test(v) },
        };

        const validCheckSvg = '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        const invalidCheckSvg = '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';

        function validatePassword() {
            const val = passwordInput.value;
            let allValid = true;

            for (const [key, rule] of Object.entries(rules)) {
                const valid = rule.test(val);
                rule.el.classList.toggle('valid', valid);
                rule.el.querySelector('svg').outerHTML = valid ? validCheckSvg : invalidCheckSvg;
                if (!valid) allValid = false;
            }

            // Check match
            const match = val && confirmInput.value && val === confirmInput.value;
            matchError.style.display = (confirmInput.value && !match) ? 'block' : 'none';
            confirmInput.classList.toggle('error', confirmInput.value && !match);

            submitBtn.disabled = !(allValid && match && document.getElementById('email').value);
        }

        passwordInput.addEventListener('input', validatePassword);
        confirmInput.addEventListener('input', validatePassword);
        document.getElementById('email').addEventListener('input', validatePassword);

        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function showAlert(type, message) {
            alertBox.className = `alert alert-${type} show`;
            alertText.textContent = message;
            alertIcon.innerHTML = type === 'error' ? errorIconPath : checkIconPath;
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            alertBox.classList.remove('show');

            try {
                const response = await fetch(`${API_BASE}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        token: TOKEN,
                        password: passwordInput.value,
                        password_confirmation: confirmInput.value,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    document.getElementById('formView').style.display = 'none';
                    document.getElementById('successView').style.display = 'block';
                } else {
                    // Extract error messages
                    let msg = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
                    if (data.errors) {
                        const firstError = Object.values(data.errors)[0];
                        msg = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                    showAlert('error', msg);
                    submitBtn.classList.remove('loading');
                    validatePassword();
                }
            } catch (err) {
                showAlert('error', 'Gagal menghubungi server. Periksa koneksi Anda.');
                submitBtn.classList.remove('loading');
                validatePassword();
            }
        });
    </script>
</body>
</html>
