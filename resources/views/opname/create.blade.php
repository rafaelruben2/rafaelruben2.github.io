<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Buat Sesi Opname</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="form-page"><a class="text-link" href="{{ route('dashboard') }}">← Kembali ke dashboard</a>
        <div class="form-card">
            <p class="eyebrow">OPERASIONAL</p>
            <h1>Buat sesi stock opname</h1>
            <p class="intro-copy">Stok saat ini akan di-freeze sebagai snapshot ketika sesi dibuat.</p>
            <form method="POST" action="{{ route('opname.sessions.store') }}">@csrf<label>Tanggal opname<input
                        type="date" name="opname_date" value="{{ old('opname_date', now()->toDateString()) }}"
                        required></label><label>Jenis opname<select name="type" required>
                        <option value="full">Full count</option>
                        <option value="cycle_count">Cycle count</option>
                    </select></label><label>Supervisor<select name="supervisor_id"><option value="">Pilih supervisor</option>@foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>@endforeach</select></label><label>Staff gudang</label><div>@foreach($staff as $person)<label><input type="checkbox" name="staff_ids[]" value="{{ $person->id }}"> {{ $person->name }}</label>@endforeach</div><label>Toleransi selisih (%)<input type="number" name="tolerance_percent" min="0" max="100" step="0.01" value="5" required></label><label>Threshold approval pimpinan<input type="number" name="approval_threshold" min="0" step="0.01" value="0" required></label><button class="primary-button" type="submit">Buat dan mulai hitung</button></form>
        </div>
    </main>
</body>

</html>