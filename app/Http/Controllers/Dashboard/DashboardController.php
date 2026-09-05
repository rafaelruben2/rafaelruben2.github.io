<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $currentHour = now()->hour;
        $greeting = match (true) {
            $currentHour < 11 => 'Selamat pagi',
            $currentHour < 15 => 'Selamat siang',
            $currentHour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return view('dashboard', [
            'currentDate' => now()->locale('id')->translatedFormat('l, d F Y'),
            'greeting' => $greeting,
            'metrics' => [
                ['label' => 'SKU terdaftar', 'value' => '1.248', 'meta' => '+32 bulan ini', 'tone' => 'teal'],
                ['label' => 'Nilai stok sistem', 'value' => 'Rp 2,84 M', 'meta' => 'per 28 Agu 2026', 'tone' => 'gold'],
                ['label' => 'Selisih teridentifikasi', 'value' => 'Rp 18,6 jt', 'meta' => '0,65% dari stok', 'tone' => 'red'],
                ['label' => 'Sesi berjalan', 'value' => '03', 'meta' => '2 menunggu verifikasi', 'tone' => 'blue'],
            ],
            'products' => [
                ['sku' => 'TTJ-TEH-025', 'name' => 'Teh Celup Melati 25s', 'location' => 'Rak A-03', 'system' => '480', 'counted' => '476', 'status' => 'Selisih', 'statusClass' => 'warning'],
                ['sku' => 'TTJ-TBR-100', 'name' => 'Teh Tubruk Wangi 100g', 'location' => 'Rak B-07', 'system' => '312', 'counted' => '312', 'status' => 'Sesuai', 'statusClass' => 'success'],
                ['sku' => 'TTJ-PRM-050', 'name' => 'Premium Jasmine 50s', 'location' => 'Rak C-01', 'system' => '198', 'counted' => '—', 'status' => 'Belum dihitung', 'statusClass' => 'neutral'],
                ['sku' => 'TTJ-TEH-050', 'name' => 'Teh Celup Hitam 50s', 'location' => 'Rak A-05', 'system' => '264', 'counted' => '261', 'status' => 'Selisih', 'statusClass' => 'warning'],
            ],
        ]);
    }
}
