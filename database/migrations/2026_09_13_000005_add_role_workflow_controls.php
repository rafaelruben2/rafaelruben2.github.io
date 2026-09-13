<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_sessions', function (Blueprint $table): void {
            $table->string('status')->default('draft')->change();
            $table->foreignId('supervisor_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->decimal('tolerance_percent', 5, 2)->default(5)->after('snapshot_at');
            $table->decimal('approval_threshold', 14, 2)->default(0)->after('tolerance_percent');
            $table->timestamp('closed_at')->nullable()->after('approved_at');
        });

        Schema::create('stock_opname_session_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_role')->default('staff_gudang');
            $table->timestamps();
            $table->unique(['stock_opname_session_id', 'user_id']);
        });

        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->string('verification_status')->default('pending')->after('is_significant');
            $table->foreignId('verified_by')->nullable()->after('counted_by')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('reconciled_at');
            $table->text('verification_note')->nullable()->after('difference_note');
            $table->string('evidence_path')->nullable()->after('notes');
            $table->boolean('recount_requested')->default(false)->after('verification_status');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verification_status', 'verified_by', 'verified_at', 'verification_note', 'evidence_path', 'recount_requested']);
        });
        Schema::dropIfExists('stock_opname_session_user');
        Schema::table('stock_opname_sessions', function (Blueprint $table): void {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['supervisor_id', 'tolerance_percent', 'approval_threshold', 'closed_at']);
        });
    }
};
