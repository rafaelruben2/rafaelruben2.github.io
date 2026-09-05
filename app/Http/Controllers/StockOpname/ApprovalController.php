<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\StockOpnameApproval;
use App\Models\StockOpnameSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function review(StockOpnameSession $session): RedirectResponse
    {
        abort_unless($session->status === 'counting', 422, 'Sesi belum siap direview.');

        $user = User::query()->where('role', 'supervisor')->first() ?? User::firstOrFail();
        DB::transaction(function () use ($session, $user): void {
            $session->update(['status' => 'verification', 'verified_by' => $user->id, 'reviewed_at' => now()]);
            StockOpnameApproval::create(['stock_opname_session_id' => $session->id, 'user_id' => $user->id, 'action' => 'reviewed']);
        });

        return back()->with('success', 'Sesi dikirim ke Manager untuk approval final.');
    }

    public function approve(Request $request, StockOpnameSession $session): RedirectResponse
    {
        $validated = $request->validate(['approval_note' => ['nullable', 'string', 'max:1000']]);
        abort_unless($session->status === 'verification', 422, 'Sesi belum direview Supervisor.');

        $user = User::query()->where('role', 'manager')->first() ?? User::firstOrFail();
        DB::transaction(function () use ($session, $user, $validated): void {
            foreach ($session->items()->whereNotNull('physical_quantity')->lockForUpdate()->get() as $item) {
                StockBalance::query()->updateOrCreate(
                    ['product_id' => $item->product_id, 'product_batch_id' => $item->product_batch_id, 'warehouse_location_id' => $item->warehouse_location_id],
                    ['quantity' => $item->physical_quantity, 'updated_at' => now()]
                );
                $item->update(['posted_at' => now()]);
            }

            $session->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now(), 'approval_note' => $validated['approval_note'] ?? null]);
            StockOpnameApproval::create(['stock_opname_session_id' => $session->id, 'user_id' => $user->id, 'action' => 'approved', 'note' => $validated['approval_note'] ?? null]);
        });

        return back()->with('success', 'Sesi disetujui dan stok sistem telah diperbarui.');
    }
}
