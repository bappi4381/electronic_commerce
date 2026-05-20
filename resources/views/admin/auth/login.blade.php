<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ONEMALL</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #20a7db;
            --primary-dark: #1890c0;
            --primary-light: #5ec5eb;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--slate-900);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            padding: 0 150px;
        }

        /* Animated background */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(32, 167, 219, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(32, 167, 219, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        /* Gradient orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            animation: orbFloat 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: var(--primary);
            top: -15%;
            right: -10%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #6366f1;
            bottom: -15%;
            left: -10%;
            animation-delay: -4s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: var(--primary-light);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -2s;
            opacity: 0.08;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }

        /* Login container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 0;
        }

        /* Brand header */
        .brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            margin-bottom: 14px;
            box-shadow: 0 16px 32px rgba(32, 167, 219, 0.3);
            animation: iconPulse 3s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 20px 40px rgba(32, 167, 219, 0.3); }
            50% { box-shadow: 0 25px 50px rgba(32, 167, 219, 0.45); }
        }

        .brand h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 900;
            color: white;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            font-style: italic;
        }

        .brand p {
            font-size: 11px;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            margin-top: 6px;
        }

        /* Card */
        .login-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .login-card h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: white;
            margin-bottom: 4px;
        }

        .login-card .subtitle {
            font-size: 12px;
            color: var(--slate-400);
            margin-bottom: 24px;
            font-weight: 500;
        }

        /* Error alert */
        .error-alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 24px;
            animation: shakeIn 0.4s ease-out;
        }

        @keyframes shakeIn {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(2px); }
        }

        .error-alert ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-alert li {
            font-size: 12px;
            color: #fca5a5;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-alert li::before {
            content: '\F33A';
            font-family: 'bootstrap-icons';
            font-size: 14px;
            color: #ef4444;
        }

        /* Form groups */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--slate-400);
            margin-bottom: 10px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: var(--slate-500);
            transition: color 0.3s;
            z-index: 2;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 18px 14px 46px;
            background: rgba(15, 23, 42, 0.5);
            border: 1.5px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            color: white;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-wrapper input::placeholder {
            color: var(--slate-600);
            font-weight: 500;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(32, 167, 219, 0.1);
        }

        .input-wrapper input:focus + i,
        .input-wrapper:focus-within i {
            color: var(--primary);
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--slate-500);
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            z-index: 2;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary-light);
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .checkbox-wrapper input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 7px;
            background: rgba(15, 23, 42, 0.5);
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .checkbox-wrapper input[type="checkbox"]:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .checkbox-wrapper input[type="checkbox"]:checked::after {
            content: '\F26E';
            font-family: 'bootstrap-icons';
            font-size: 12px;
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .checkbox-wrapper label {
            font-size: 13px;
            color: var(--slate-400);
            font-weight: 500;
            cursor: pointer;
            user-select: none;
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px rgba(32, 167, 219, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(32, 167, 219, 0.35);
        }

        .btn-login:hover::before {
            transform: translateX(100%);
        }

        .btn-login:active {
            transform: translateY(0) scale(0.98);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }

        .login-footer p {
            font-size: 12px;
            color: var(--slate-600);
            font-weight: 500;
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s;
        }

        .login-footer a:hover {
            color: var(--primary-light);
        }

        /* Security badge */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
            padding: 12px;
            background: rgba(32, 167, 219, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(32, 167, 219, 0.08);
        }

        .security-badge i {
            color: var(--primary);
            font-size: 14px;
        }

        .security-badge span {
            font-size: 10px;
            color: var(--slate-500);
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
                border-radius: 20px;
            }

            .brand-icon {
                width: 56px;
                height: 56px;
                font-size: 24px;
                border-radius: 16px;
            }

            .brand h1 {
                font-size: 24px;
            }
        }

        /* Loading state for button */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login.loading .btn-text {
            visibility: hidden;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- Background Effects -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="login-wrapper">
        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h1>ONEMALL</h1>
            <p>Admin Command Center</p>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to access the admin dashboard</p>

            @if ($errors->any())
                <div class="error-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" id="loginForm">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            placeholder="admin@example.com" required autofocus>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password"
                            placeholder="Enter your password" required>
                        <i class="bi bi-lock"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword()" id="toggleBtn">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">
                        <i class="bi bi-arrow-right-circle-fill"></i>
                        Sign In to Dashboard
                    </span>
                </button>
            </form>

            <div class="security-badge">
                <i class="bi bi-shield-lock-fill"></i>
                <span>256-bit SSL Encrypted Connection</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; {{ date('Y') }} ONEMALL Engine &middot; <a href="{{ url('/') }}">Back to Store</a></p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Add loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loginBtn').classList.add('loading');
        });
    </script>
</body>
</html>
