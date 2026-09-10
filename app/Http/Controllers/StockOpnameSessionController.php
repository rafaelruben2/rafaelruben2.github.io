<?php

namespace App\Http\Controllers;

use App\Models\StockOpnameSession;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameApproval;
use App\Models\Product;
use App\Models\StockOpnameSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameSessionController extends Controller
{
    // Daftar semua sesi
    public function index()
    {
        $sessions = StockOpnameSessions::withCount([
                'items',
                'items as counted_items_count' => fn ($q) => $q->whereNotNull('physical_quantity'),
            ])
            ->latest()
            ->paginate(15);

        return view('stock-opname.sessions.index', compact('sessions'));
    }

    // Form mulai sesi baru
    public function create()
    {
        return view('stock-opname.sessions.create');
    }

    // Simpan sesi baru + generate item dari semua produk
    public function store(Request $request)
    {
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'type'        => 'required|in:full,cycle_count',
            'warehouse_location_id' => 'nullable|exists:warehouse_locations,id',
        ]);

        $session = DB::transaction(function () use ($validated) {
            $session = StockOpnameSession::create([
                'code'        => 'SO-' . now()->format('Ymd') . '-' . str_pad((string) (StockOpnameSession::count() + 1), 4, '0', STR_PAD_LEFT),
                'opname_date' => $validated['opname_date'],
                'type'        => $validated['type'],
                'status'      => 'counting',
                'created_by'  => Auth::id(),
                'snapshot_at' => now(),
            ]);

            // Ambil produk yang perlu dihitung (sesuaikan filter lokasi/kategori sesuai kebutuhan)
            $productsQuery = Product::query();
            if (!empty($validated['warehouse_location_id'])) {
                $productsQuery->where('warehouse_location_id', $validated['warehouse_location_id']);
            }

            $productsQuery->chunk(200, function ($products) use ($session) {
                foreach ($products as $product) {
                    StockOpnameItem::create([
                        'stock_opname_session_id' => $session->id,
                        'product_id'              => $product->id,
                        'product_batch_id'        => $product->product_batch_id ?? null,
                        'warehouse_location_id'   => $product->warehouse_location_id,
                        'system_quantity'         => $product->stock_quantity ?? 0, // sesuaikan nama kolom stok di tabel products
                    ]);
                }
            });

            return $session;
        });

        return redirect()
            ->route('stock-opname-sessions.show', $session)
            ->with('success', 'Sesi stock opname berhasil dibuat.');
    }

    // Halaman hitung fisik — otomatis jadi halaman "lanjutkan" kalau sesi sudah in_progress
    public function show(StockOpnameSession $session)
    {
        $items = $session->items()
            ->with(['product', 'batch', 'location'])
            ->orderByRaw('physical_quantity IS NOT NULL') // belum dihitung tampil duluan
            ->orderBy('id')
            ->paginate(50);

        return view('stock-opname.sessions.show', compact('session', 'items'));
    }

    // AJAX: simpan hasil hitung satu item (auto-save)
    public function updateItem(Request $request, StockOpnameSession $session, StockOpnameItem $item)
    {
        abort_unless($item->stock_opname_session_id === $session->id, 404);
        abort_if(!in_array($session->status, ['draft', 'counting']), 422, 'Sesi ini sudah tidak bisa diubah.');

        $validated = $request->validate([
            'physical_quantity' => 'required|numeric|min:0',
            'condition'         => 'nullable|in:good,damaged,expired',
            'difference_reason' => 'nullable|string',
            'difference_note'   => 'nullable|string',
        ]);

        $diff = $validated['physical_quantity'] - $item->system_quantity;

        $item->update([
            'physical_quantity'   => $validated['physical_quantity'],
            'difference_quantity' => $diff,
            'condition'           => $validated['condition'] ?? $item->condition,
            'difference_reason'   => $validated['difference_reason'] ?? $item->difference_reason,
            'difference_note'     => $validated['difference_note'] ?? $item->difference_note,
            'is_significant'      => abs($diff) > 0, // sesuaikan threshold signifikan sesuai kebutuhan
            'counted_by'          => Auth::id(),
            'counted_at'          => now(),
        ]);

        if ($session->status === 'draft') {
            $session->update(['status' => 'counting']);
        }

        return response()->json([
            'success'  => true,
            'progress' => $session->fresh()->progress_percent,
        ]);
    }

    // Submit sesi untuk verifikasi
    public function submit(StockOpnameSession $session)
    {
        abort_unless($session->is_fully_counted, 422, 'Masih ada produk yang belum dihitung.');

        DB::transaction(function () use ($session) {
            $session->update([
                'status'      => 'verification',
                'reviewed_at' => now(),
            ]);

            StockOpnameApproval::create([
                'stock_opname_session_id' => $session->id,
                'user_id'                 => Auth::id(),
                'action'                  => 'submitted',
            ]);
        });

        return redirect()
            ->route('stock-opname-sessions.show', $session)
            ->with('success', 'Sesi berhasil disubmit untuk verifikasi.');
    }

    // Approve / reject oleh verifikator atau approver
    public function decide(Request $request, StockOpnameSession $session)
    {
        $validated = $request->validate([
            'action' => 'required|in:reviewed,approved,rejected',
            'note'   => 'nullable|string',
        ]);

        DB::transaction(function () use ($session, $validated) {
            StockOpnameApproval::create([
                'stock_opname_session_id' => $session->id,
                'user_id'                 => Auth::id(),
                'action'                  => $validated['action'],
                'note'                    => $validated['note'] ?? null,
            ]);

            $statusMap = [
                'reviewed' => 'verification',
                'approved' => 'approved',
                'rejected' => 'counting', // dikembalikan untuk dihitung ulang
            ];

            $session->update([
                'status'         => $statusMap[$validated['action']],
                'approval_note'  => $validated['note'] ?? $session->approval_note,
                'verified_by'    => $validated['action'] === 'reviewed' ? Auth::id() : $session->verified_by,
                'approved_by'    => $validated['action'] === 'approved' ? Auth::id() : $session->approved_by,
                'approved_at'    => $validated['action'] === 'approved' ? now() : $session->approved_at,
            ]);
        });

        return redirect()
            ->route('stock-opname-sessions.show', $session)
            ->with('success', 'Keputusan berhasil disimpan.');
    }
}
