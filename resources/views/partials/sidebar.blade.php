<aside class="sidebar">
    <div class="logo-header" data-background-color="dark">
        <a href="{{ route('dashboard') }}" class="logo">
            <img src="{{ asset('template/assets/img/kaiadmin/Logo_Tongtji.png') }}" alt="Tong Tji" class="navbar-brand" height="130">
        </a>
    </div>
    <nav class="nav-list" aria-label="Navigasi utama">
        <a class="nav-item active" href="{{ route('dashboard') }}"><span class="nav-icon">▦</span> Ikhtisar</a>
        <p class="nav-label">OPERASIONAL</p>
        <a class="nav-item" href="{{ route('opname.sessions.index') }}"><span class="nav-icon">◷</span> Sesi stock opname <b>3</b></a>
        <a class="nav-item" href="{{ route('opname.sessions.index') }}#hitung"><span class="nav-icon">⌁</span> Hitung fisik</a>
        <a class="nav-item" href="{{ route('opname.sessions.index') }}#import"><span class="nav-icon">↥</span> Import stok awal</a>
        <p class="nav-label">MASTER DATA</p>
        <a class="nav-item" href="{{ route('dashboard') }}#produk"><span class="nav-icon">□</span> Produk &amp; batch</a>
        <a class="nav-item" href="{{ route('dashboard') }}#produk"><span class="nav-icon">⌂</span> Lokasi &amp; rak</a>
        <a class="nav-item" href="{{ route('dashboard') }}#produk"><span class="nav-icon">◈</span> Kategori produk</a>
        <p class="nav-label">MANAJEMEN</p>
        <a class="nav-item" href="#laporan"><span class="nav-icon">▤</span> Laporan</a>
        <a class="nav-item" href="{{ route('users.index') }}"><span class="nav-icon">◎</span> Pengguna &amp; role</a>
    </nav>
    <div class="sidebar-footer"><span class="avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($name) => strtoupper(substr($name, 0, 1)))->take(2)->implode('') }}</span><span><strong>{{ auth()->user()->name }}</strong><small>Administrator</small></span><form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Apakah Anda yakin ingin keluar?');"><button class="more" type="submit" aria-label="Keluar">↪</button></form></div>
</aside>