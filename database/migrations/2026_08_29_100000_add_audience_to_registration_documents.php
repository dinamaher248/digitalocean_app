<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registration_documents')) {
            return;
        }

        if (!Schema::hasColumn('registration_documents', 'user_type')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->string('user_type', 20)->default('both');
            });
        }

        if (!Schema::hasColumn('registration_documents', 'vehicle_type_keys')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->json('vehicle_type_keys')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('registration_documents')) {
            return;
        }

        if (Schema::hasColumn('registration_documents', 'vehicle_type_keys')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->dropColumn('vehicle_type_keys');
            });
        }

        if (Schema::hasColumn('registration_documents', 'user_type')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
        }
    }
};
