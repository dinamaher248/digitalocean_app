<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'vendor_billing_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('vendor_billing_mode', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'vendor_billing_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('vendor_billing_mode');
            });
        }
    }
};
