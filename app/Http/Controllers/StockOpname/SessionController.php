<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\StockOpnameSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index(): Response
    {
        return response()->view('opname.index', ['sessions' => StockOpnameSession::query()->latest()->get()]);
    }

    public function create(): Response
    {
        return response()->view('opname.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'type' => ['required', 'in:full,cycle_count'],
        ]);
        $user = User::firstOrFail();
        $session = DB::transaction(function () use ($validated, $user): StockOpnameSession {
            $session = StockOpnameSession::create([
                'code' => 'OPN-'.now()->format('YmdHis'),
                'opname_date' => $validated['opname_date'],
                'type' => $validated['type'],
                'status' => 'counting',
                'created_by' => $user->id,
                'snapshot_at' => now(),
            ]);

            StockBalance::query()->each(function (StockBalance $balance) use ($session): void {
                $session->items()->create([
                    'product_id' => $balance->product_id,
                    'product_batch_id' => $balance->product_batch_id,
                    'warehouse_location_id' => $balance->warehouse_location_id,
                    'system_quantity' => $balance->quantity,
                ]);
            });

            return $session;
        });

        return redirect()->route('opname.sessions.show', $session)->with('success', 'Sesi opname dibuat dengan snapshot stok.');
    }

    public function show(StockOpnameSession $session): Response
    {
        return response()->view('opname.show', ['session' => $session->load('items')]);
    }
}
