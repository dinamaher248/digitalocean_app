<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bidding_offers')) {
            return;
        }

        if (!Schema::hasColumn('bidding_offers', 'price')) {
            Schema::table('bidding_offers', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable();
            });
        }

        if (!Schema::hasColumn('bidding_offers', 'proposed_price')) {
            Schema::table('bidding_offers', function (Blueprint $table) {
                $table->decimal('proposed_price', 10, 2)->nullable();
            });
        }

        if (Schema::hasColumn('bidding_offers', 'price') && Schema::hasColumn('bidding_offers', 'proposed_price')) {
            DB::table('bidding_offers')->whereNull('price')->whereNotNull('proposed_price')
                ->update(['price' => DB::raw('proposed_price')]);
            DB::table('bidding_offers')->whereNull('proposed_price')->whereNotNull('price')
                ->update(['proposed_price' => DB::raw('price')]);
        }
    }

    public function down(): void
    {
        // لا نحذف أعمدة مستخدمة على السيرفر.
    }
};
