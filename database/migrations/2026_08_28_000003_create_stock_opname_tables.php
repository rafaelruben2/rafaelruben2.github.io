<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('staff_gudang')->after('email');
            }
        });

        if (! Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
                $table->string('sku')->unique();
                $table->string('barcode')->unique();
                $table->string('name');
                $table->string('unit')->default('pcs');
                $table->unsignedInteger('unit_conversion')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_batches')) {
            Schema::create('product_batches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('batch_number');
                $table->date('expired_at')->nullable();
                $table->timestamps();
                $table->unique(['product_id', 'batch_number']);
            });
        }

        if (! Schema::hasTable('stock_balances')) {
            Schema::create('stock_balances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('warehouse_location_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 14, 3)->default(0);
                $table->timestamps();
                $table->unique(['product_id', 'product_batch_id', 'warehouse_location_id'], 'stock_balance_identity');
            });
        }

        if (! Schema::hasTable('stock_opname_sessions')) {
            Schema::create('stock_opname_sessions', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->date('opname_date');
                $table->enum('type', ['full', 'cycle_count']);
                $table->enum('status', ['draft', 'counting', 'verification', 'approved', 'cancelled'])->default('draft');
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('snapshot_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_opname_items')) {
            Schema::create('stock_opname_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('stock_opname_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('warehouse_location_id')->constrained()->restrictOnDelete();
                $table->decimal('system_quantity', 14, 3)->default(0);
                $table->decimal('physical_quantity', 14, 3)->nullable();
                $table->enum('condition', ['good', 'damaged', 'expired'])->nullable();
                $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('counted_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['stock_opname_session_id', 'product_id', 'product_batch_id', 'warehouse_location_id'], 'opname_item_identity');
            });
        }

        if (! Schema::hasTable('stock_imports')) {
            Schema::create('stock_imports', function (Blueprint $table): void {
                $table->id();
                $table->string('file_name');
                $table->enum('status', ['processing', 'validated', 'completed', 'failed'])->default('processing');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('invalid_rows')->default(0);
                $table->json('validation_errors')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_imports');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_sessions');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('product_categories');
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
