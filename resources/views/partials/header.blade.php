<header class="topbar"><button class="mobile-menu" aria-label="Buka menu">☰</button>
    <div class="breadcrumb">Workspace <span>/</span> <strong>Stock Opname</strong></div>
    <div class="top-actions"><button class="icon-button" aria-label="Notifikasi">♧<i></i></button><span
            class="profile-button"><span
                class="avatar small">{{ collect(explode(' ', auth()->user()->name))->map(fn($name) => strtoupper(substr($name, 0, 1)))->take(2)->implode('') }}</span><span>{{ auth()->user()->name }}</span></span>
    </div>
</header>