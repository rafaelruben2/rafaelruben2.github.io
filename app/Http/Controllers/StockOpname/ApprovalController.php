<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\StockOpnameApproval;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function review(StockOpnameSession $session): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->hasRole('admin', 'supervisor') || $user->role === 'manager', 403);
        abort_unless($session->status === 'counting', 422, 'Sesi belum siap direview.');
        abort_unless($session->is_fully_counted, 422, 'Semua item harus dihitung sebelum verifikasi.');

        DB::transaction(function () use ($session, $user): void {
            $session->update(['status' => 'verification', 'verified_by' => $user->id, 'reviewed_at' => now()]);
            StockOpnameApproval::create(['stock_opname_session_id' => $session->id, 'user_id' => $user->id, 'action' => 'reviewed']);
            $session->auditLogs()->create(['user_id' => $user->id, 'action' => 'submitted_for_verification', 'new_values' => ['status' => 'verification'], 'ip_address' => request()->ip()]);
        });

        return back()->with('success', 'Sesi dikirim ke Manager untuk approval final.');
    }

    public function verifyItem(Request $request, StockOpnameItem $item): RedirectResponse
    {
        $session = $item->session;
        $user = auth()->user();
        abort_unless($user->hasRole('admin', 'supervisor'), 403);
        abort_unless($session->status === 'verification', 422, 'Sesi belum masuk tahap verifikasi.');
        $validated = $request->validate(['action' => ['required', 'in:approved,recount_requested'], 'verification_note' => ['required_if:action,recount_requested', 'nullable', 'string', 'max:1000']]);
        $item->update([
            'verification_status' => $validated['action'] === 'approved' ? 'approved' : 'recount_requested',
            'recount_requested' => $validated['action'] === 'recount_requested',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_note' => $validated['verification_note'] ?? null,
        ]);
        $session->auditLogs()->create(['user_id' => $user->id, 'action' => 'item_'.$validated['action'], 'new_values' => $item->fresh()->toArray(), 'ip_address' => $request->ip()]);
        if (! $session->items()->where('verification_status', '!=', 'approved')->exists()) {
            $session->update(['status' => 'pending_approval']);
        }

        return back()->with('success', 'Status verifikasi item berhasil diperbarui.');
    }

    public function approve(Request $request, StockOpnameSession $session): RedirectResponse
    {
        $validated = $request->validate(['approval_note' => ['nullable', 'string', 'max:1000']]);
        $user = auth()->user();
        abort_unless($user->hasRole('admin', 'pimpinan') || $user->role === 'manager', 403);
        abort_unless(in_array($session->status, ['verification', 'pending_approval'], true), 422, 'Sesi belum siap untuk approval final.');

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
            $session->auditLogs()->create(['user_id' => $user->id, 'action' => 'final_approved', 'new_values' => ['status' => 'approved'], 'ip_address' => request()->ip()]);
        });

        return back()->with('success', 'Sesi disetujui dan stok sistem telah diperbarui.');
    }
}
