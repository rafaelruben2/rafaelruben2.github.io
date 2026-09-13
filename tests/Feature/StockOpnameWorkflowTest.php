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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->post(route('opname.items.reconcile', $item), ['_token' => $csrfToken, 'physical_quantity' => 94, 'condition' => 'good', 'difference_reason' => 'missing', 'difference_note' => 'Selisih hasil hitung rak'])
            ->assertSessionHas('success');
        $this->post(route('opname.sessions.review', $session), ['_token' => $csrfToken])->assertSessionHas('success');
        $this->post(route('opname.sessions.approve', $session), ['_token' => $csrfToken, 'approval_note' => 'Disetujui setelah review'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stock_opname_sessions', ['id' => $session->id, 'status' => 'approved']);
        $this->assertDatabaseHas('stock_balances', ['product_id' => $product->id, 'quantity' => 94]);
        $this->assertDatabaseHas('stock_opname_approvals', ['stock_opname_session_id' => $session->id, 'action' => 'approved']);
    }

    public function test_staff_can_only_count_an_assigned_session_and_supervisor_verification_requires_final_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff_gudang']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $pimpinan = User::factory()->create(['role' => 'pimpinan']);
        $category = ProductCategory::create(['name' => 'Teh', 'slug' => 'teh']);
        $location = WarehouseLocation::create(['code' => 'B-01', 'name' => 'Rak B-01']);
        $product = Product::create(['product_category_id' => $category->id, 'sku' => 'TTJ-002', 'barcode' => '899002', 'name' => 'Teh Hitam']);
        StockBalance::create(['product_id' => $product->id, 'warehouse_location_id' => $location->id, 'quantity' => 50]);

        $this->actingAs($admin)->post(route('opname.sessions.store'), [
            'opname_date' => '2026-09-13', 'type' => 'full', 'supervisor_id' => $supervisor->id,
            'staff_ids' => [$staff->id], 'tolerance_percent' => 5, 'approval_threshold' => 1000,
        ]);
        $session = StockOpnameSession::firstOrFail();
        $item = StockOpnameItem::firstOrFail();

        $this->actingAs($pimpinan)->post(route('opname.items.reconcile', $item), ['physical_quantity' => 49])
            ->assertForbidden();
        $this->actingAs($staff)->post(route('opname.items.reconcile', $item), ['physical_quantity' => 49, 'condition' => 'good', 'difference_reason' => 'missing', 'difference_note' => 'Tidak ditemukan'])
            ->assertSessionHas('success');
        $this->actingAs($supervisor)->post(route('opname.sessions.review', $session))->assertSessionHas('success');
        $this->actingAs($supervisor)->post(route('opname.items.verify', $item), ['action' => 'approved'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stock_opname_sessions', ['id' => $session->id, 'status' => 'pending_approval']);
        $this->actingAs($pimpinan)->post(route('opname.sessions.approve', $session), ['approval_note' => 'Sign-off final'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('stock_opname_sessions', ['id' => $session->id, 'status' => 'approved']);
    }

    public function test_session_creation_rejects_missing_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('opname.sessions.store'), ['opname_date' => '', 'type' => ''])
            ->assertSessionHasErrors(['opname_date', 'type']);

        $this->assertDatabaseCount('stock_opname_sessions', 0);
    }

    public function test_item_counting_rejects_a_missing_condition(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ProductCategory::create(['name' => 'Teh', 'slug' => 'teh']);
        $location = WarehouseLocation::create(['code' => 'C-01', 'name' => 'Rak C-01']);
        $product = Product::create(['product_category_id' => $category->id, 'sku' => 'TTJ-005', 'barcode' => '899005', 'name' => 'Teh Hijau']);
        StockBalance::create(['product_id' => $product->id, 'warehouse_location_id' => $location->id, 'quantity' => 20]);

        $this->actingAs($admin)->post(route('opname.sessions.store'), [
            'opname_date' => '2026-09-13',
            'type' => 'full',
        ]);
        $item = StockOpnameItem::firstOrFail();

        $this->post(route('opname.items.reconcile', $item), ['physical_quantity' => 20])
            ->assertSessionHasErrors('condition');
    }

    public function test_admin_can_create_a_product_but_staff_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff_gudang']);
        $category = ProductCategory::create(['name' => 'Minuman', 'slug' => 'minuman']);

        $this->actingAs($staff)->get(route('products.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('products.create'))->assertOk();
        $this->actingAs($admin)->post(route('products.store'), [
            'product_category_id' => $category->id,
            'sku' => 'TTJ-003',
            'barcode' => '899003',
            'name' => 'Teh Melati',
            'unit' => 'pcs',
            'unit_conversion' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'TTJ-003', 'name' => 'Teh Melati']);
    }

    public function test_admin_can_upload_a_product_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ProductCategory::create(['name' => 'Makanan', 'slug' => 'makanan']);

        $this->actingAs($admin)->post(route('products.store'), [
            'product_category_id' => $category->id,
            'sku' => 'TTJ-004',
            'barcode' => '899004',
            'name' => 'Biskuit Teh',
            'unit' => 'pcs',
            'unit_conversion' => 1,
            'is_active' => 1,
            'image' => UploadedFile::fake()->image('biskuit-teh.jpg'),
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'TTJ-004')->firstOrFail();
        Storage::disk('public')->assertExists($product->image_path);
    }
}
