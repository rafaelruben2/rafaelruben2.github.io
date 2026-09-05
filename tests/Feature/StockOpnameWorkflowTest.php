<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockBalance;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_a_registered_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'andi@tongtji.com',
            'password' => 'password',
        ]);

        $this->get(route('login'));
        $this->post(route('login.store'), [
            '_token' => csrf_token(),
            'email' => 'andi@tongtji.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))
            ->assertSee($user->name)
            ->assertSee(strtoupper(now()->locale('id')->translatedFormat('l, d F Y')))
            ->assertSee(now()->format('d M Y, H:i').' WIB');
    }

    public function test_a_session_can_be_counted_reviewed_approved_and_posted_to_stock(): void
    {
        $user = User::factory()->create();
        $user->update(['role' => 'manager']);
        $this->actingAs($user);
        $this->get(route('login'));
        $csrfToken = csrf_token();
        $category = ProductCategory::create(['name' => 'Teh celup', 'slug' => 'teh-celup']);
        $location = WarehouseLocation::create(['code' => 'A-01', 'name' => 'Rak A-01']);
        $product = Product::create(['product_category_id' => $category->id, 'sku' => 'TTJ-001', 'barcode' => '899001', 'name' => 'Teh Celup Melati']);
        StockBalance::create(['product_id' => $product->id, 'warehouse_location_id' => $location->id, 'quantity' => 100]);

        $this->post(route('opname.sessions.store'), ['_token' => $csrfToken, 'opname_date' => '2026-08-28', 'type' => 'full'])
            ->assertRedirect();
        $session = StockOpnameSession::firstOrFail();
        $item = StockOpnameItem::firstOrFail();

        $this->post(route('opname.items.reconcile', $item), ['_token' => $csrfToken, 'physical_quantity' => 94, 'difference_reason' => 'missing', 'difference_note' => 'Selisih hasil hitung rak'])
            ->assertSessionHas('success');
        $this->post(route('opname.sessions.review', $session), ['_token' => $csrfToken])->assertSessionHas('success');
        $this->post(route('opname.sessions.approve', $session), ['_token' => $csrfToken, 'approval_note' => 'Disetujui setelah review'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stock_opname_sessions', ['id' => $session->id, 'status' => 'approved']);
        $this->assertDatabaseHas('stock_balances', ['product_id' => $product->id, 'quantity' => 94]);
        $this->assertDatabaseHas('stock_opname_approvals', ['stock_opname_session_id' => $session->id, 'action' => 'approved']);
    }
}
