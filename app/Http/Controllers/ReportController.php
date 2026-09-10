<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameSession;

class ReportController extends Controller
{
    public function report(StockOpnameSession $session)
    {
        $session->load(['creator', 'verifier', 'approver', 'approvals.user']);

        $items = $session->items()->with(['product', 'batch', 'location'])->get();

        $summary = [
            'total_items'       => $items->count(),
            'counted_items'     => $items->whereNotNull('physical_quantity')->count(),
            'uncounted_items'   => $items->whereNull('physical_quantity')->count(),
            'items_with_diff'   => $items->where('difference_quantity', '!=', 0)->count(),
            'significant_items' => $items->where('is_significant', true)->count(),
            'total_diff_qty'    => $items->sum('difference_quantity'),
        ];

        $discrepancies = $items->filter(fn ($item) => $item->difference_quantity != 0)
            ->sortByDesc(fn ($item) => abs($item->difference_quantity));

        return view('stock-opname.sessions.report', compact('session', 'items', 'summary', 'discrepancies'));
    }
}