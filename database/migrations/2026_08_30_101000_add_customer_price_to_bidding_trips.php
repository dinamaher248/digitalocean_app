<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bidding_trips') && !Schema::hasColumn('bidding_trips', 'customer_price')) {
            Schema::table('bidding_trips', function (Blueprint $table) {
                $table->decimal('customer_price', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bidding_trips') && Schema::hasColumn('bidding_trips', 'customer_price')) {
            Schema::table('bidding_trips', function (Blueprint $table) {
                $table->dropColumn('customer_price');
            });
        }
    }
};
