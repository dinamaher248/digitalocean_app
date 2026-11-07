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
            Schema::create('vehicle_types', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('category', 32)->default('quick_trip');
                $table->string('name')->nullable();
                $table->string('name_ar')->nullable();
                $table->string('name_en')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->decimal('service_radius_km', 8, 2)->default(7);
                $table->timestamps();
            });

            $now = now();
            DB::table('vehicle_types')->insert([
                ['key' => 'motorcycle', 'category' => 'quick_trip', 'name' => 'موتوسيكل', 'name_ar' => 'موتوسيكل', 'name_en' => 'Motorcycle', 'sort_order' => 1, 'is_active' => 1, 'service_radius_km' => 7, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'scooter', 'category' => 'quick_trip', 'name' => 'سكوتر', 'name_ar' => 'سكوتر', 'name_en' => 'Scooter', 'sort_order' => 2, 'is_active' => 1, 'service_radius_km' => 7, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'tricycle', 'category' => 'goods', 'name' => 'تروسيكل', 'name_ar' => 'تروسيكل', 'name_en' => 'Tricycle', 'sort_order' => 3, 'is_active' => 1, 'service_radius_km' => 10, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'quarter_truck', 'category' => 'goods', 'name' => 'ربع نقل', 'name_ar' => 'ربع نقل', 'name_en' => 'Quarter truck', 'sort_order' => 4, 'is_active' => 1, 'service_radius_km' => 15, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'half_truck', 'category' => 'goods', 'name' => 'نصف نقل', 'name_ar' => 'نصف نقل', 'name_en' => 'Half truck', 'sort_order' => 5, 'is_active' => 1, 'service_radius_km' => 25, 'created_at' => $now, 'updated_at' => $now],
            ]);
        } elseif (!Schema::hasColumn('vehicle_types', 'service_radius_km')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                $table->decimal('service_radius_km', 8, 2)->default(7);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'current_lat')) {
                    $table->decimal('current_lat', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('users', 'current_lng')) {
                    $table->decimal('current_lng', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('users', 'location_updated_at')) {
                    $table->timestamp('location_updated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'location_updated_at')) {
                    $table->dropColumn('location_updated_at');
                }
                if (Schema::hasColumn('users', 'current_lng')) {
                    $table->dropColumn('current_lng');
                }
                if (Schema::hasColumn('users', 'current_lat')) {
                    $table->dropColumn('current_lat');
                }
            });
        }

        if (Schema::hasTable('vehicle_types') && Schema::hasColumn('vehicle_types', 'service_radius_km')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                $table->dropColumn('service_radius_km');
            });
        }
    }
};
