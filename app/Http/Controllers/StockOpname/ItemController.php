<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function reconcile(Request $request, StockOpnameItem $item): RedirectResponse
    {
        $session = $item->session;
        $user = auth()->user();
        abort_unless($session->canBeCountedBy($user), 403, 'Sesi tidak dapat diubah pada tahap ini.');
        $validated = $request->validate([
            'physical_quantity' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'in:good,damaged,expired,not_found'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'image', 'max:5120'],
        ]);

        $difference = (float) $validated['physical_quantity'] - (float) $item->system_quantity;
        $hasDifference = abs($difference) > 0;

        if ($hasDifference) {
            $validated += $request->validate([
                'difference_reason' => ['required', 'in:missing,damaged,expired,entry_error'],
                'difference_note' => ['required', 'string', 'max:1000'],
            ]);
        }

        $item->update([
            'physical_quantity' => $validated['physical_quantity'],
            'difference_quantity' => $difference,
            'difference_reason' => $hasDifference ? ($validated['difference_reason'] ?? null) : null,
            'difference_note' => $hasDifference ? ($validated['difference_note'] ?? null) : null,
            'condition' => $validated['condition'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_significant' => abs($difference) >= max(5, (float) $item->system_quantity * ((float) $session->tolerance_percent / 100)),
            'counted_by' => $user->id,
            'counted_at' => now(),
            'verification_status' => 'pending',
            'recount_requested' => false,
            'reconciled_at' => now(),
        ]);

        if ($request->hasFile('evidence')) {
            $item->update(['evidence_path' => $request->file('evidence')->store('opname-evidence', 'private')]);
        }
        $session->auditLogs()->create(['user_id' => $user->id, 'action' => 'item_counted', 'new_values' => $item->fresh()->toArray(), 'ip_address' => $request->ip()]);

        return back()->with('success', 'Rekonsiliasi item berhasil dihitung.');
    }
}
