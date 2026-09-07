<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tong Tji') }} · Pengguna & Role</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content">
            @include('partials.header')

            <section class="page-intro">
                <div>
                    <p class="eyebrow">MANAJEMEN</p>
                    <h1>Pengguna &amp; role</h1>
                    <p class="intro-copy">Kelola siapa saja yang punya akses ke sistem, dan apa yang boleh mereka lakukan.</p>
                </div>
                @if ($isAdmin)
                    <a href="{{ route('users.create') }}" class="primary-button"><span>+</span> Tambah pengguna</a>
                @endif
            </section>

            @if (session('success'))
                <div class="success-message" style="border-radius:4px;margin-bottom:14px;">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="login-error" style="margin-bottom:14px;">{{ session('error') }}</div>
            @endif

            @unless ($isAdmin)
                <div class="login-error" style="margin-bottom:14px;">
                    Anda login sebagai <strong>{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</strong>.
                    Hanya pengguna dengan role <strong>Admin</strong> yang bisa menambah, mengubah, atau menghapus pengguna.
                </div>
            @endunless

            <section class="panel table-panel" id="pengguna">
                <div class="panel-heading">
                    <h2>Daftar pengguna</h2>
                    <div class="table-actions">
                        <span class="filter-button">{{ $users->count() }} pengguna</span>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Status</th>
                                @if ($isAdmin)
                                    <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        <small>{{ $user->email }}</small>
                                    </td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                                    <td>
                                        <span class="status-pill {{ $user->status === 'aktif' ? 'success' : 'neutral' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    @if ($isAdmin)
                                        <td>
                                            <a href="{{ route('users.edit', $user) }}" class="text-link">Ubah</a>
                                            @if ($user->id !== auth()->id())
                                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                    style="display:inline;margin-left:12px;"
                                                    onsubmit="return confirm('Hapus {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="row-more" style="color:#d7543f;font-size:12px;">Hapus</button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach

                            @if ($users->isEmpty())
                                <tr>
                                    <td colspan="{{ $isAdmin ? 4 : 3 }}" style="text-align:center;color:var(--muted);padding:24px 0;">
                                        Belum ada pengguna.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
