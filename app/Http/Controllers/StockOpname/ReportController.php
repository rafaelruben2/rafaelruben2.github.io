<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameSession;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function report(StockOpnameSession $session): Response
    {
        $user = auth()->user();
        abort_unless($user->hasRole('admin', 'pimpinan') || $session->created_by === $user->id || $session->supervisor_id === $user->id || $session->assignedStaff()->whereKey($user->id)->exists(), 403);

        return response()->view('reports.stock-opname', ['session' => $session->load('items')]);
    }
}
