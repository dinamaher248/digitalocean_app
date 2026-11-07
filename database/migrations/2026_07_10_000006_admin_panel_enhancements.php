<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'is_suspended')) {
                    $table->boolean('is_suspended')->default(false)->after('is_approved');
                }
                if (!Schema::hasColumn('users', 'admin_permissions')) {
                    $table->json('admin_permissions')->nullable()->after('is_admin');
                }
            });
        }

        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table) {
                if (!Schema::hasColumn('plans', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('days');
                }
                if (!Schema::hasColumn('plans', 'for_drivers')) {
                    $table->boolean('for_drivers')->default(true)->after('is_active');
                }
                if (!Schema::hasColumn('plans', 'for_merchants')) {
                    $table->boolean('for_merchants')->default(false)->after('for_drivers');
                }
            });
        }

        if (!Schema::hasTable('registration_documents')) {
            Schema::create('registration_documents', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label');
                $table->string('field_type')->default('image');
                $table->boolean('is_required')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('registration_documents')->insert([
                [
                    'key' => 'nid_front',
                    'label' => 'صورة البطاقة — الوجه',
                    'field_type' => 'image',
                    'is_required' => true,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'nid_back',
                    'label' => 'صورة البطاقة — الظهر',
                    'field_type' => 'image',
                    'is_required' => true,
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_documents');

        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table) {
                foreach (['for_merchants', 'for_drivers', 'is_active'] as $col) {
                    if (Schema::hasColumn('plans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['admin_permissions', 'is_suspended'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
