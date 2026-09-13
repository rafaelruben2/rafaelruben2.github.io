<?php

namespace App\Http\Controllers\StockOpname;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
        $user = auth()->user();
        $query = StockOpnameSession::query()->latest();

        if (! $user->hasRole('admin')) {
            $query->where(function ($query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhere('supervisor_id', $user->id)
                    ->orWhereHas('assignedStaff', fn ($staff) => $staff->whereKey($user->id));
            });
        }

        return response()->view('opname.index', ['sessions' => $query->get()]);
    }

    public function audit(): Response
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return response()->view('opname.audit', [
            'logs' => AuditLog::query()->with('user')->latest()->paginate(50),
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth()->user()->hasRole('admin', 'supervisor') || auth()->user()->role === 'manager', 403);

        return response()->view('opname.create', [
            'staff' => User::query()->where('role', 'staff_gudang')->where('status', 'aktif')->orderBy('name')->get(),
            'supervisors' => User::query()->where('role', 'supervisor')->where('status', 'aktif')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'type' => ['required', 'in:full,cycle_count'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['exists:users,id'],
            'tolerance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'approval_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);
        $user = auth()->user();
        abort_unless($user->hasRole('admin', 'supervisor') || $user->role === 'manager', 403);
        $validated['tolerance_percent'] = $validated['tolerance_percent'] ?? 5;
        $validated['approval_threshold'] = $validated['approval_threshold'] ?? 0;
        if ($user->hasRole('supervisor')) {
            $validated['supervisor_id'] = $user->id;
        }
        $session = DB::transaction(function () use ($validated, $user): StockOpnameSession {
            $session = StockOpnameSession::create([
                'code' => 'OPN-'.now()->format('YmdHis'),
                'opname_date' => $validated['opname_date'],
                'type' => $validated['type'],
                'status' => 'counting',
                'created_by' => $user->id,
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'snapshot_at' => now(),
                'tolerance_percent' => $validated['tolerance_percent'],
                'approval_threshold' => $validated['approval_threshold'],
            ]);

            $session->assignedStaff()->sync(collect($validated['staff_ids'] ?? [])->mapWithKeys(fn (int $id): array => [$id => ['assignment_role' => 'staff_gudang']])->all());

            StockBalance::query()->each(function (StockBalance $balance) use ($session): void {
                $session->items()->create([
                    'product_id' => $balance->product_id,
                    'product_batch_id' => $balance->product_batch_id,
                    'warehouse_location_id' => $balance->warehouse_location_id,
                    'system_quantity' => $balance->quantity,
                ]);
            });

            $session->auditLogs()->create(['user_id' => $user->id, 'action' => 'created', 'new_values' => $session->fresh()->toArray(), 'ip_address' => request()->ip()]);

            return $session;
        });

        return redirect()->route('opname.sessions.show', $session)->with('success', 'Sesi opname dibuat dengan snapshot stok.');
    }

    public function show(StockOpnameSession $session): Response
    {
        $user = auth()->user();
        abort_unless($user->hasRole('admin') || $session->created_by === $user->id || $session->supervisor_id === $user->id || $session->assignedStaff()->whereKey($user->id)->exists(), 403);

        return response()->view('opname.show', ['session' => $session->load(['items.product', 'items.location', 'assignedStaff', 'supervisor', 'approvals.user'])]);
    }

    public function update(Request $request, StockOpnameSession $session): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['exists:users,id'],
            'tolerance_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'approval_threshold' => ['required', 'numeric', 'min:0'],
        ]);
        $session->update(collect($validated)->except('staff_ids')->all());
        $session->assignedStaff()->sync(collect($validated['staff_ids'] ?? [])->mapWithKeys(fn (int $id): array => [$id => ['assignment_role' => 'staff_gudang']])->all());
        $session->auditLogs()->create(['user_id' => auth()->id(), 'action' => 'updated', 'new_values' => $validated, 'ip_address' => $request->ip()]);

        return back()->with('success', 'Sesi opname berhasil diperbarui.');
    }

    public function cancel(StockOpnameSession $session): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($session->status !== 'closed', 422, 'Sesi yang sudah ditutup tidak dapat dibatalkan.');
        $session->update(['status' => 'cancelled']);
        $session->auditLogs()->create(['user_id' => auth()->id(), 'action' => 'cancelled', 'new_values' => ['status' => 'cancelled'], 'ip_address' => request()->ip()]);

        return back()->with('success', 'Sesi opname dibatalkan.');
    }

    public function close(StockOpnameSession $session): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin', 'pimpinan') || auth()->user()->role === 'manager', 403);
        abort_unless($session->status === 'approved', 422, 'Sesi belum disetujui.');
        $session->update(['status' => 'closed', 'closed_at' => now()]);
        $session->auditLogs()->create(['user_id' => auth()->id(), 'action' => 'closed', 'new_values' => ['status' => 'closed'], 'ip_address' => request()->ip()]);

        return back()->with('success', 'Sesi opname ditutup.');
    }
}
