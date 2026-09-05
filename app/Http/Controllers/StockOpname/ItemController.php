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
        $validated = $request->validate(['physical_quantity' => ['required', 'numeric', 'min:0']]);

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
            'is_significant' => abs($difference) >= max(5, (float) $item->system_quantity * 0.05),
            'reconciled_at' => now(),
        ]);

        return back()->with('success', 'Rekonsiliasi item berhasil dihitung.');
    }
}
