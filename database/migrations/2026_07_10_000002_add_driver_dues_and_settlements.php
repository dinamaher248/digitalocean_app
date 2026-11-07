<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (!Schema::hasColumn('users', 'dues_balance')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->decimal('dues_balance', 10, 2)->default(0)->after('coins');
                });
            }
            if (!Schema::hasColumn('users', 'commission_rate')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->decimal('commission_rate', 5, 2)->default(10)->after('dues_balance');
                });
            }
        }

        if (!Schema::hasTable('settlement_requests')) {
            Schema::create('settlement_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('account_type')->default('driver');
                $table->decimal('amount', 10, 2);
                $table->string('payment_proof');
                $table->string('status')->default('pending');
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_requests');
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'dues_balance')) {
                    $table->dropColumn('dues_balance');
                }
                if (Schema::hasColumn('users', 'commission_rate')) {
                    $table->dropColumn('commission_rate');
                }
            });
        }
    }
};
