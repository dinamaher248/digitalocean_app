<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_types')) {
            return;
        }

        if (!Schema::hasColumn('vehicle_types', 'category')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                $table->string('category', 32)->default('quick_trip');
            });
        }

        DB::table('vehicle_types')
            ->whereIn('key', ['tricycle', 'toktok', 'quarter_truck', 'half_truck'])
            ->update(['category' => 'goods']);

        DB::table('vehicle_types')
            ->whereIn('key', ['motorcycle', 'scooter'])
            ->update(['category' => 'quick_trip']);

        DB::table('vehicle_types')
            ->where(function ($q) {
                $q->whereNull('category')->orWhere('category', '');
            })
            ->update(['category' => 'quick_trip']);
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicle_types') && Schema::hasColumn('vehicle_types', 'category')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
