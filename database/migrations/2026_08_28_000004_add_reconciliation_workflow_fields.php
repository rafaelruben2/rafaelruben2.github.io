<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->decimal('difference_quantity', 14, 3)->nullable()->after('physical_quantity');
            $table->enum('difference_reason', ['missing', 'damaged', 'expired', 'entry_error'])->nullable()->after('condition');
            $table->text('difference_note')->nullable()->after('difference_reason');
            $table->boolean('is_significant')->default(false)->after('difference_note');
            $table->timestamp('reconciled_at')->nullable()->after('counted_at');
            $table->timestamp('posted_at')->nullable()->after('reconciled_at');
        });

        Schema::table('stock_opname_sessions', function (Blueprint $table): void {
            $table->timestamp('reviewed_at')->nullable()->after('snapshot_at');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->text('approval_note')->nullable()->after('approved_at');
        });

        Schema::create('stock_opname_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('action', ['submitted', 'reviewed', 'approved', 'rejected']);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_approvals');
        Schema::table('stock_opname_sessions', function (Blueprint $table): void {
            $table->dropColumn(['reviewed_at', 'approved_at', 'approval_note']);
        });
        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->dropColumn(['difference_quantity', 'difference_reason', 'difference_note', 'is_significant', 'reconciled_at', 'posted_at']);
        });
    }
};
