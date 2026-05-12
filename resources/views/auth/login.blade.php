<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Krakatau CRM</title>
    <style>
        :root {
            --bg: #f8f7fa;
            --surface: #fff;
            --text: #2f2b3d;
            --muted: #6d6777;
            --line: #dbdade;
            --primary: #7367f0;
            --primary-hover: #685dd8;
            --danger: #ff4c51;
            --shadow: 0 4px 18px rgba(47, 43, 61, .10);
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Public Sans", "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        .auth-wrapper {
            display: grid;
            min-height: 100vh;
            grid-template-columns: minmax(0, 1fr) 460px;
        }

        .auth-illustration {
            position: relative;
            display: grid;
            overflow: hidden;
            place-items: center;
            padding: 48px;
        }

        .auth-illustration::before {
            position: absolute;
            inset: auto 8% 0;
            height: 44%;
            border-radius: 50% 50% 0 0;
            background: #f0efff;
            content: "";
        }

        .auth-illustration img {
            position: relative;
            z-index: 1;
            width: min(660px, 82%);
            max-height: 78vh;
            object-fit: contain;
        }

        .auth-panel {
            display: grid;
            align-content: center;
            min-height: 100vh;
            background: var(--surface);
            box-shadow: -1px 0 0 rgba(47, 43, 61, .08);
            padding: 48px;
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
            margin-inline: auto;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            color: var(--text);
            font-size: 24px;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .brand img {
            width: 35px;
            height: 24px;
            filter: invert(46%) sepia(79%) saturate(1777%) hue-rotate(220deg) brightness(97%) contrast(92%);
        }

        h1 {
            margin: 0 0 8px;
            color: var(--text);
            font-size: 24px;
            font-weight: 600;
            line-height: 1.35;
        }

        .subtitle {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.55;
        }

        .demo-note {
            margin: 0 0 22px;
            border-radius: 6px;
            background: rgba(115, 103, 240, .12);
            color: #5e55d6;
            font-size: 14px;
            line-height: 1.55;
            padding: 12px 14px;
        }

        .auth-form {
            display: grid;
            gap: 16px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        label,
        .label {
            color: #5d586c;
            font-size: 13px;
            font-weight: 500;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            min-height: 38px;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: var(--surface);
            color: var(--text);
            outline: 0;
            padding: 8px 14px;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(115, 103, 240, .14);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 2px;
        }

        .checkbox {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #5d586c;
            font-size: 15px;
        }

        .checkbox input {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 38px;
            border: 0;
            border-radius: 6px;
            background: var(--primary);
            box-shadow: 0 2px 6px rgba(115, 103, 240, .36);
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            margin-top: 6px;
            padding: 8px 18px;
            transition: background .18s ease, transform .18s ease;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .create-account {
            margin: 24px 0 0;
            color: var(--muted);
            font-size: 15px;
            text-align: center;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: #a8a3b5;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            height: 1px;
            flex: 1;
            background: var(--line);
            content: "";
        }

        .socials {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .socials a {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 50%;
            color: var(--primary);
            font-weight: 700;
        }

        .error {
            color: var(--danger);
            font-size: 13px;
        }

        @media (max-width: 960px) {
            .auth-wrapper {
                grid-template-columns: 1fr;
            }

            .auth-illustration {
                display: none;
            }

            .auth-panel {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <main class="auth-wrapper">
        <section class="auth-illustration" aria-hidden="true">
            <img src="{{ asset('build/assets/auth-v2-login-illustration-light-C4sKfRS1.png') }}" alt="">
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <a href="{{ route('login') }}" class="brand" aria-label="Krakatau CRM login">
                    <img src="{{ asset('assets/vuexy/logo.svg') }}" alt="">
                    <span>Vuexy</span>
                </a>

                <h1>Welcome to Krakatau CRM! 👋🏻</h1>
                <p class="subtitle">Please sign-in to your account and start the adventure</p>

                <p class="demo-note">Admin Email: <strong>test@example.com</strong> / Pass: <strong>password</strong></p>

                <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" autocomplete="email" required autofocus>
                        @error('email')<small class="error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field">
                        <div class="field-row">
                            <label for="password">Password</label>
                            <a href="#" tabindex="-1">Forgot Password?</a>
                        </div>
                        <input id="password" type="password" name="password" placeholder="············" autocomplete="current-password" required>
                        @error('password')<small class="error">{{ $message }}</small>@enderror
                    </div>

                    <div class="remember-row">
                        <label class="checkbox">
                            <input type="checkbox" name="remember" value="1">
                            <span>Remember Me</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>

                <p class="create-account">New on our platform? <a href="#">Create an account</a></p>

                <div class="divider">or</div>

                <div class="socials" aria-label="Social login options">
                    <a href="#" aria-label="Facebook">f</a>
                    <a href="#" aria-label="Twitter">x</a>
                    <a href="#" aria-label="GitHub">◎</a>
                    <a href="#" aria-label="Google">G</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
