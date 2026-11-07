<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchants')) {
            Schema::table('merchants', function (Blueprint $table) {
                if (!Schema::hasColumn('merchants', 'wallet_owner_name')) {
                    $table->string('wallet_owner_name')->nullable()->after('wallet_number');
                }
                if (!Schema::hasColumn('merchants', 'wallet_type')) {
                    $table->string('wallet_type')->nullable()->after('wallet_owner_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('merchants')) {
            Schema::table('merchants', function (Blueprint $table) {
                if (Schema::hasColumn('merchants', 'wallet_type')) {
                    $table->dropColumn('wallet_type');
                }
                if (Schema::hasColumn('merchants', 'wallet_owner_name')) {
                    $table->dropColumn('wallet_owner_name');
                }
            });
        }
    }
};
