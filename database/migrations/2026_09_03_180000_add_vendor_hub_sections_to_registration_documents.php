<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registration_documents')
            && !Schema::hasColumn('registration_documents', 'vendor_hub_sections')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->json('vendor_hub_sections')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('registration_documents')
            && Schema::hasColumn('registration_documents', 'vendor_hub_sections')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->dropColumn('vendor_hub_sections');
            });
        }
    }
};
