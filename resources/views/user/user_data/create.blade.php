<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tong Tji') }} · Tambah Pengguna</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content">
            @include('partials.header')

            <div class="form-page">
                <h1>Tambah pengguna</h1>
                <p class="intro-copy">Buat akun baru dan tentukan role akses untuk pengguna ini.</p>

                @if ($errors->any())
                    <div class="login-error" style="margin-top:18px;">
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-card">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <label>Nama lengkap
                            <input type="text" name="name" value="{{ old('name') }}" required autofocus>
                        </label>

                        <label>Email
                            <input type="email" name="email" value="{{ old('email') }}" required>
                        </label>

                        <label>Role
                            <select name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(old('role') === $role)>
                                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>Status
                            <select name="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status') === $status)>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>Kata sandi
                            <input type="password" name="password" required minlength="8">
                        </label>

                        <label>Konfirmasi kata sandi
                            <input type="password" name="password_confirmation" required minlength="8">
                        </label>

                        <button type="submit" class="primary-button">Simpan pengguna</button>
                    </form>
                </div>

                <p style="margin-top:16px;">
                    <a href="{{ route('users.index') }}" class="text-link">&larr; Kembali ke daftar pengguna</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
