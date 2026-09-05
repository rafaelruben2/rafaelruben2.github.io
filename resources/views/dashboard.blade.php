<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tong Tji') }} · Stock Control</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content">
            @include('partials.header')
            <section class="page-intro"><div><p class="eyebrow">{{ strtoupper($currentDate) }}</p><h1>{{ $greeting }}, {{ auth()->user()->name }}.</h1><p class="intro-copy">Pantau aktivitas stok dan pastikan setiap hitungan tercatat akurat.</p></div><a class="primary-button" href="{{ route('opname.create') }}"><span>＋</span> Buat sesi opname</a></section>

            <section class="metric-grid" aria-label="Ringkasan stok">
                @foreach ($metrics as $metric)<article class="metric-card {{ $metric['tone'] }}"><div class="metric-top"><span>{{ $metric['label'] }}</span><span class="metric-dot"></span></div><strong>{{ $metric['value'] }}</strong><small>{{ $metric['meta'] }}</small></article>@endforeach
            </section>

            <section class="content-grid">
                <article class="panel session-panel" id="sesi"><div class="panel-heading"><div><p class="eyebrow">SESI AKTIF</p><h2>Opname Gudang Pusat · Agustus</h2></div><span class="status-pill live"><i></i> Sedang berjalan</span></div><div class="session-meta"><span><b>Full count</b> · Dibuat 26 Agu 2026</span><span>Oleh <b>Rina Sari</b></span></div><div class="progress-row"><div class="progress-label"><strong>68%</strong><span>850 dari 1.248 SKU selesai dihitung</span></div><span>3 hari tersisa</span></div><div class="progress-track"><span style="width: 68%"></span></div><div class="session-bottom"><div class="people"><span class="avatar teal">RS</span><span class="avatar gold">DW</span><span class="avatar blue">+4</span><span>6 petugas aktif</span></div><a href="#hitung" class="text-link">Lanjutkan hitung <span>→</span></a></div></article>
                <article class="panel quick-panel" id="hitung"><div class="panel-heading"><div><p class="eyebrow">AKSI CEPAT</p><h2>Mulai dari sini</h2></div><span class="spark">✦</span></div><a class="quick-action" href="#sesi"><span class="action-icon scan">⌁</span><span><strong>Hitung fisik</strong><small>Buka sesi aktif dan input jumlah</small></span><b>→</b></a><a class="quick-action" href="#import"><span class="action-icon upload">↥</span><span><strong>Import stok awal</strong><small>Gunakan CSV dari Excel</small></span><b>→</b></a><a class="quick-action" href="#laporan"><span class="action-icon report">▤</span><span><strong>Lihat laporan</strong><small>Analisis selisih dan approval</small></span><b>→</b></a></article>
            </section>

            <section class="panel table-panel" id="produk"><div class="panel-heading"><div><p class="eyebrow">MONITORING HITUNGAN</p><h2>Produk yang perlu perhatian</h2></div><div class="table-actions"><button class="filter-button">Semua status⌄</button><a href="#laporan" class="text-link">Lihat semua <span>→</span></a></div></div><div class="table-wrap"><table><thead><tr><th>PRODUK</th><th>LOKASI</th><th>STOK SISTEM</th><th>FISIK</th><th>STATUS</th><th></th></tr></thead><tbody>@foreach ($products as $product)<tr><td><strong>{{ $product['name'] }}</strong><small>{{ $product['sku'] }}</small></td><td>{{ $product['location'] }}</td><td>{{ $product['system'] }} <small>pcs</small></td><td class="{{ $product['statusClass'] }}">{{ $product['counted'] }}</td><td><span class="status-pill {{ $product['statusClass'] }}">{{ $product['status'] }}</span></td><td><button class="row-more" aria-label="Opsi {{ $product['name'] }}">···</button></td></tr>@endforeach</tbody></table></div></section>
            @include('partials.footer')
        </main>
    </div>
</body>
</html>