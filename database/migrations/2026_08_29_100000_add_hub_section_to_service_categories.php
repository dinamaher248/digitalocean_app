<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_categories') && !Schema::hasColumn('service_categories', 'hub_section')) {
            Schema::table('service_categories', function (Blueprint $table) {
                $table->string('hub_section', 32)->default('orders')->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_categories') && Schema::hasColumn('service_categories', 'hub_section')) {
            Schema::table('service_categories', function (Blueprint $table) {
                $table->dropColumn('hub_section');
            });
        }
    }
};
