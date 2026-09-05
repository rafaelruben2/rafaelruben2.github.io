<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockOpnameSession;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $user = User::firstOrFail();
        $path = $validated['file']->getRealPath();
        $handle = fopen($path, 'r');
        $headers = array_map(fn ($header): string => strtolower(trim((string) $header)), fgetcsv($handle) ?: []);
        $requiredHeaders = ['sku', 'quantity', 'location_code'];
        abort_unless(count(array_intersect($requiredHeaders, $headers)) === count($requiredHeaders), 422, 'Header CSV wajib: sku, quantity, location_code.');
        $indexes = array_flip($headers);
        $validRows = 0;
        $errors = [];
        DB::transaction(function () use ($handle, $indexes, $user, &$validRows, &$errors): void {
            while (($row = fgetcsv($handle)) !== false) {
                $line = $validRows + count($errors) + 2;
                $product = Product::where('sku', trim((string) ($row[$indexes['sku']] ?? '')))->first();
                $location = WarehouseLocation::where('code', trim((string) ($row[$indexes['location_code']] ?? '')))->first();
                $quantity = $row[$indexes['quantity']] ?? null;
                if (! $product || ! $location || ! is_numeric($quantity) || (float) $quantity < 0) {
                    $errors[] = "Baris {$line}: SKU, lokasi, atau quantity tidak valid.";

                    continue;
                }
                StockBalance::updateOrCreate(
                    ['product_id' => $product->id, 'product_batch_id' => null, 'warehouse_location_id' => $location->id],
                    ['quantity' => $quantity]
                );
                $validRows++;
            }
            fclose($handle);
            DB::table('stock_imports')->insert(['file_name' => 'stok-awal.csv', 'status' => count($errors) ? 'validated' : 'completed', 'total_rows' => $validRows + count($errors), 'valid_rows' => $validRows, 'invalid_rows' => count($errors), 'validation_errors' => json_encode($errors), 'uploaded_by' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', "Import selesai: {$validRows} baris valid, ".count($errors).' baris invalid.');
    }

    public function export(StockOpnameSession $session): StreamedResponse
    {
        return response()->streamDownload(function () use ($session): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Stok Sistem', 'Fisik', 'Selisih', 'Alasan', 'Catatan']);
            $session->items()->each(function ($item) use ($handle): void {
                fputcsv($handle, [$item->product_id, $item->system_quantity, $item->physical_quantity, $item->difference_quantity, $item->difference_reason, $item->difference_note]);
            });
            fclose($handle);
        }, $session->code.'-hasil-opname.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
