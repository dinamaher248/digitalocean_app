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
                if (!Schema::hasColumn('merchants', 'phone')) {
                    $table->string('phone', 30)->nullable()->after('address');
                }
                if (!Schema::hasColumn('merchants', 'working_hours')) {
                    $table->json('working_hours')->nullable()->after('phone');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('merchants')) {
            Schema::table('merchants', function (Blueprint $table) {
                if (Schema::hasColumn('merchants', 'working_hours')) {
                    $table->dropColumn('working_hours');
                }
                if (Schema::hasColumn('merchants', 'phone')) {
                    $table->dropColumn('phone');
                }
            });
        }
    }
};
