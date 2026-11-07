<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_categories') && !Schema::hasColumn('service_categories', 'max_images')) {
            Schema::table('service_categories', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_images')->default(10);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_categories') && Schema::hasColumn('service_categories', 'max_images')) {
            Schema::table('service_categories', function (Blueprint $table) {
                $table->dropColumn('max_images');
            });
        }
    }
};
