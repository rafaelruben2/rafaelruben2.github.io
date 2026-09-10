<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    // Import stok awal dari file CSV (kolom: product_code, quantity)
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle); // baris pertama = header kolom

        $imported = 0;
        $skipped  = [];

        DB::transaction(function () use ($handle, &$imported, &$skipped) {
            while (($row = fgetcsv($handle)) !== false) {
                // Sesuaikan index kolom dengan urutan di file CSV kamu
                $productCode = trim($row[0] ?? '');
                $quantity    = trim($row[1] ?? '');

                if ($productCode === '' || !is_numeric($quantity)) {
                    $skipped[] = $row;
                    continue;
                }

                $product = Product::where('code', $productCode)->first(); // sesuaikan nama kolom kode produk
                if (!$product) {
                    $skipped[] = $row;
                    continue;
                }

                $product->update(['stock_quantity' => $quantity]); // sesuaikan nama kolom stok

                $imported++;
            }
        });

        fclose($handle);

        return back()->with('success', "Berhasil import {$imported} produk." .
            (count($skipped) ? ' ' . count($skipped) . ' baris dilewati (produk tidak ditemukan/data tidak valid).' : ''));
    }

    // Export hasil sesi opname ke CSV
    public function export(StockOpnameSession $session): StreamedResponse
    {
        $items = $session->items()->with(['product', 'batch', 'location'])->get();

        $filename = "opname-{$session->code}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($items) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Produk', 'Batch', 'Lokasi', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Kondisi', 'Dihitung Oleh', 'Waktu Hitung']);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->product->name ?? '-',       // sesuaikan nama kolom nama produk
                    $item->batch->batch_number ?? '-',  // sesuaikan nama kolom nomor batch
                    $item->location->name ?? '-',       // sesuaikan nama kolom nama lokasi
                    $item->system_quantity,
                    $item->physical_quantity ?? 'Belum dihitung',
                    $item->difference_quantity ?? '-',
                    $item->condition ?? '-',
                    $item->counter->name ?? '-',
                    $item->counted_at?->format('Y-m-d H:i') ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}