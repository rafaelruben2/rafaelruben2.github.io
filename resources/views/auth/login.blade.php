<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · {{ config('app.name', 'Tong Tji') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('services.recaptcha.site_key'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-brand-panel">
            <div class="login-brand-logo">
                <img src="{{ asset('template/assets/img/kaiadmin/Logo_Tongtji.png') }}" alt="Tong Tji">
            </div>
            <p class="login-kicker">PT TONG TJI TEA INDONESIA</p>
            <h1>Teh Paling Nikmat Titik.</h1>
            <p class="login-caption">Kelola stok, pantau opname, dan jaga setiap proses tetap terkendali.</p>
            <span class="login-tea-line" aria-hidden="true"></span>
        </section>

        <section class="login-form-panel">
            <div class="login-form-wrap">
                <p class="eyebrow">STOCK OPERASIONAL</p>
                <h2>Selamat Datang Kembali</h2>
                <p class="login-intro">Masuk untuk melanjutkan ke Stock Control.</p>

                @if ($errors->any())
                    <div class="login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@tongtji.com" autocomplete="email" required autofocus>

                    <div class="login-password-label"><label for="password">Password</label><a href="#">Lupa kata sandi?</a></div>
                    <input id="password" name="password" type="password" placeholder="Masukkan Password" autocomplete="current-password" required>

                    @if (config('services.recaptcha.site_key'))
                        <div class="login-captcha"><div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div></div>
                    @endif

                    <label class="remember-me"><input type="checkbox" name="remember" value="1"> <span>Ingat saya di perangkat ini</span></label>
                    <button class="login-button" type="submit">Masuk ke Dashboard <span>→</span></button>
                </form>
                <p class="login-footer">Akses hanya untuk pengguna terdaftar <span>·</span> PT Tong Tji Stock Control</p>
            </div>
        </section>
    </main>
</body>
</html>