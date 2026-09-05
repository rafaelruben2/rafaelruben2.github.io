<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameSession;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function report(StockOpnameSession $session): Response
    {
        return response()->view('reports.stock-opname', ['session' => $session->load('items')]);
    }
}
