<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tong Tji') }} · Pengguna &amp; Role</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .user-management-page { max-width: 1180px; margin: 0 auto; padding: 42px 0 32px; }
        .user-notice { border: 1px solid #dce5fb; border-radius: 14px; color: #3b4c9e; background: #f0f3ff; line-height: 1.6; margin-bottom: 25px; padding: 13px 18px; }
        .user-search-row { align-items: center; display: flex; gap: 12px; margin-bottom: 13px; }
        .user-search { position: relative; width: min(100%, 440px); }
        .user-search-icon { color: #80918c; font-size: 20px; left: 14px; line-height: 1; pointer-events: none; position: absolute; top: 50%; transform: translateY(-50%); }
        .user-search input { background: #fff; border: 1px solid #dfe7e3; border-radius: 11px; box-shadow: 0 2px 8px rgba(23, 51, 47, .04); color: #17332f; outline: 0; padding: 12px 14px 12px 42px; width: 100%; }
        .user-search input:focus { border-color: #0b6559; box-shadow: 0 0 0 3px rgba(11, 101, 89, .1); }
        .user-count { color: #72817d; font-size: 12px; white-space: nowrap; }
        .user-table-card { background: #fff; border: 1px solid #e0e8e4; border-radius: 15px; box-shadow: 0 5px 18px rgba(23, 51, 47, .045); overflow: hidden; }
        .user-table-card table { margin: 0; min-width: 680px; }
        .user-table-card thead tr { background: #f7f7f3 !important; }
        .user-table-card th { color: #72817d; font-size: 10px; padding: 13px 22px; }
        .user-table-card td { color: #667873; font-size: 13px; padding: 18px 22px; vertical-align: middle; }
        .user-table-card td strong { color: #17332f; font-size: 14px; }
        .user-table-card td small { color: #8a9994; font-size: 12px; }
        .user-table-card tbody tr { transition: background .15s ease; }
        .user-table-card tbody tr:hover { background: #fbfdfb; }
        @media (max-width: 640px) { .user-management-page { padding: 28px 0 22px; } .user-search-row { align-items: stretch; flex-direction: column; } .user-search { width: 100%; } .user-count { padding-left: 2px; } }
    </style>
</head>
<body class="user-page-body">
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content user-main-content">
            @include('partials.header')
            <div x-data="userManagement()" x-init="showForm = @js($errors->any())" class="user-management-page">

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded-xl text-sm" style="background:#eaf3ef;color:#1b6b54;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 px-4 py-3 rounded-xl text-sm" style="background:#fbeae7;color:#c1594a;">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-xl text-sm" style="background:#fbeae7;color:#a33b2e;">
            <strong>Data belum disimpan:</strong>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="user-intro flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <p class="text-xs font-medium tracking-wide mb-2" style="color:#6b7a73;">MANAJEMEN</p>
            <h1 class="text-3xl mb-1.5" style="color:#182620;font-family:Georgia,'Times New Roman',serif;">
                Pengguna &amp; role
            </h1>
            <p class="text-sm" style="color:#6b7a73;">
                Kelola siapa saja yang punya akses ke sistem, dan apa yang boleh mereka lakukan.
            </p>
        </div>

        @if ($isAdmin)
            <button @click="openCreate()"
                class="user-add-button flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-white shrink-0"
                style="background:#0b3b2e;">
                + Tambah pengguna
            </button>
        @endif
    </div>

    <section class="user-preview-panel mb-8 rounded-2xl border p-4 sm:p-5" style="border-color:#f0dfb4;background:#fffaf0;">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full" style="background:#fff1c9;color:#a67818;">◉</span>
                <span class="text-sm font-medium" style="color:#9a731e;">Pratinjau tampilan sebagai:</span>
            </div>
            <div class="flex items-center gap-1 rounded-xl border bg-white p-1" style="border-color:#efe4ca;">
                <template x-for="role in previewRoles" :key="role.value">
                    <button type="button" @click="previewRole = role.value"
                        class="rounded-lg px-4 py-2 text-sm transition"
                        :class="previewRole === role.value ? 'font-semibold text-white' : 'font-medium'"
                        :style="previewRole === role.value ? 'background:#0b4639' : 'color:#71817d'"
                        x-text="role.label"></button>
                </template>
            </div>
        </div>
    </section>

    <section class="user-role-grid mb-8 grid gap-4 lg:grid-cols-3">
        <template x-for="role in previewRoles" :key="role.value">
            <article class="user-role-card rounded-2xl border bg-white p-5 transition" :class="previewRole === role.value ? 'shadow-sm' : ''"
                :style="previewRole === role.value ? 'border-color:#d9e7e1' : 'border-color:#e9e5db'">
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold"
                    :style="role.badgeStyle" x-text="role.label"></span>
                <p class="mt-4 text-sm leading-6" style="color:#70827d;" x-text="role.description"></p>
            </article>
        </template>
    </section>

    {{-- Banner untuk non-admin --}}
    @unless ($isAdmin)
        <div class="user-notice text-sm">
            <span>
                Anda login sebagai <strong>{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</strong>.
                Hanya pengguna dengan role <strong>Admin</strong> yang bisa menambah, mengubah, atau menghapus data pengguna.
            </span>
        </div>
    @endunless

    {{-- Pencarian --}}
    <div class="user-search-row">
        <label class="user-search">
            <span class="user-search-icon">⌕</span>
            <input x-model="query" type="text" placeholder="Cari nama atau email..." aria-label="Cari pengguna">
        </label>
        <span class="user-count" x-text="filteredUsers().length + ' pengguna'"></span>
    </div>

    {{-- Tabel --}}
    <div class="user-table-card">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f6f4ee;">
                    <th class="text-left font-medium px-5 py-3" style="color:#6b7a73;">Pengguna</th>
                    <th class="text-left font-medium px-5 py-3" style="color:#6b7a73;">Role</th>
                    <th class="text-left font-medium px-5 py-3" style="color:#6b7a73;">Status</th>
                    @if ($isAdmin)
                        <th class="text-right font-medium px-5 py-3" style="color:#6b7a73;">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr x-show="matchesQuery(@js($user->name), @js($user->email))" style="border-top:1px solid #e6e2d6;">
                        <td class="px-5 py-3.5">
                            <div class="font-medium" style="color:#182620;">{{ $user->name }}</div>
                            <div class="text-xs" style="color:#6b7a73;">{{ $user->email }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium"
                                style="background:#eaf3ef;color:#1b6b54;">
                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 text-sm"
                                style="color: {{ $user->status === 'aktif' ? '#1b6b54' : '#6b7a73' }};">
                                <span class="w-1.5 h-1.5 rounded-full"
                                    style="background: {{ $user->status === 'aktif' ? '#1b6b54' : '#b5b0a2' }};"></span>
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        @if ($isAdmin)
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-1">
                                    <button
                                        @click="openEdit({{ $user->id }}, @js($user->name), @js($user->email), @js($user->role), @js($user->status))"
                                        class="p-2 rounded-lg hover:bg-black/5" title="Ubah">
                                        ✎
                                    </button>
                                    <button @click="openDelete({{ $user->id }}, @js($user->name))"
                                        class="p-2 rounded-lg hover:bg-black/5" style="color:#c1594a;" title="Hapus">
                                        🗑
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal Tambah / Ubah --}}
    <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4"
        style="background:rgba(8,20,16,0.45);">
        <div @click.outside="showForm = false" class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden">
            <form x-ref="userForm" :action="formAction" method="POST" autocomplete="off">
                @csrf
                <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

                <div class="flex items-center justify-between px-6 py-5 border-b" style="border-color:#e6e2d6;">
                    <h3 class="text-lg font-semibold" style="color:#182620;"
                        x-text="editingId ? 'Ubah pengguna' : 'Tambah pengguna'"></h3>
                    <button type="button" @click="showForm = false">✕</button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Nama lengkap</label>
                        <input name="name" x-model="form.name" required
                            class="w-full px-3 py-2.5 rounded-lg border text-sm" style="border-color:#e6e2d6;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Email</label>
                        <input name="email" type="email" x-model="form.email" required
                            class="w-full px-3 py-2.5 rounded-lg border text-sm" style="border-color:#e6e2d6;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Role</label>
                        <select name="role" x-model="form.role" required
                            class="w-full px-3 py-2.5 rounded-lg border text-sm" style="border-color:#e6e2d6;">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Status</label>
                        <select name="status" x-model="form.status" required
                            class="w-full px-3 py-2.5 rounded-lg border text-sm" style="border-color:#e6e2d6;">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">
                            <span x-text="editingId ? 'Kata sandi baru (kosongkan jika tidak diubah)' : 'Kata sandi'"></span>
                        </label>
                        <input name="password" type="password" autocomplete="new-password"
                            class="w-full px-3 py-2.5 rounded-lg border text-sm" style="border-color:#e6e2d6;"
                            :required="!editingId">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Konfirmasi kata sandi</label>
                        <input name="password_confirmation" type="password" autocomplete="new-password"
                            class="w-full px-3 py-2.5 rounded-lg border text-sm" style="border-color:#e6e2d6;"
                            :required="!editingId">
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t" style="border-color:#e6e2d6;background:#f6f4ee;">
                    <button type="button" @click="showForm = false" class="px-4 py-2 rounded-lg text-sm font-medium">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background:#0b3b2e;">
                        <span x-text="editingId ? 'Simpan perubahan' : 'Tambah pengguna'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Hapus --}}
    <div x-show="showDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4"
        style="background:rgba(8,20,16,0.45);">
        <div @click.outside="showDelete = false" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden">
            <div class="px-6 pt-6 pb-4">
                <h3 class="text-lg font-semibold mb-1" style="color:#182620;">Hapus pengguna?</h3>
                <p class="text-sm" style="color:#6b7a73;">
                    <strong x-text="deleteName"></strong> akan kehilangan akses ke sistem. Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <form :action="deleteAction" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex justify-end gap-2 px-6 py-4 border-t" style="border-color:#e6e2d6;background:#f6f4ee;">
                    <button type="button" @click="showDelete = false" class="px-4 py-2 rounded-lg text-sm font-medium">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background:#c1594a;">
                        Ya, hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
        </main>
    </div>

<script>
function userManagement() {
    return {
        query: '',
        showForm: false,
        showDelete: false,
        editingId: null,
        deleteName: '',
        previewRole: @js(auth()->user()->role),
        previewRoles: [
            @if (auth()->user()->role === 'admin')
            { value: 'admin', label: 'Admin', description: 'Akses penuh: tambah, ubah, dan hapus pengguna serta data master.', badgeStyle: 'background:#eaf3ef;color:#167263;' },
            @elseif (auth()->user()->role === 'supervisor')
            { value: 'supervisor', label: 'Supervisor', description: 'Memverifikasi sesi opname dan laporan, tidak bisa mengelola pengguna.', badgeStyle: 'background:#fff3df;color:#a87317;' },
            @else
            { value: 'staff_gudang', label: 'Staff', description: 'Input hitungan fisik pada sesi aktif, tidak bisa mengelola pengguna.', badgeStyle: 'background:#eef1ff;color:#5269b4;' },
            @endif
        ],
        formAction: @js(route('users.store')),
        deleteAction: '',
        form: {
            name: @js(old('name', '')),
            email: @js(old('email', '')),
            role: @js(old('role', 'staff_gudang')),
            status: @js(old('status', 'aktif')),
        },

        openCreate() {
            this.editingId = null;
            this.form = { name: '', email: '', role: 'staff_gudang', status: 'aktif' };
            this.formAction = @js(route('users.store'));
            this.$refs.userForm?.reset();
            this.showForm = true;
        },
        openEdit(id, name, email, role, status) {
            this.editingId = id;
            this.form = { name, email, role, status };
            this.formAction = `/pengguna/${id}`;
            this.$refs.userForm?.reset();
            this.showForm = true;
        },
        openDelete(id, name) {
            this.deleteName = name;
            this.deleteAction = `/pengguna/${id}`;
            this.showDelete = true;
        },
        matchesQuery(name, email) {
            if (!this.query.trim()) return true;
            const q = this.query.toLowerCase();
            return name.toLowerCase().includes(q) || email.toLowerCase().includes(q);
        },
        filteredUsers() {
            return @js($users->map(fn($u) => ['name' => $u->name, 'email' => $u->email]))
                .filter(u => this.matchesQuery(u.name, u.email));
        },
    };
}
</script>
</body>
</html>
