<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tong Tji') }} · Ubah Pengguna</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content">
            @include('partials.header')

            <div class="form-page">
                <h1>Ubah pengguna</h1>
                <p class="intro-copy">Perbarui data untuk <strong>{{ $user->name }}</strong>.</p>

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
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <label>Nama lengkap
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                        </label>

                        <label>Email
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </label>

                        <label>Role
                            <select name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>Status
                            <select name="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', $user->status) === $status)>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>Kata sandi baru <span style="color:var(--muted);font-weight:400;">(kosongkan jika tidak diubah)</span>
                            <input type="password" name="password" minlength="8">
                        </label>

                        <label>Konfirmasi kata sandi baru
                            <input type="password" name="password_confirmation" minlength="8">
                        </label>

                        <button type="submit" class="primary-button">Simpan perubahan</button>
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
