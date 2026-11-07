<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_orders')) {
            return;
        }

        Schema::table('service_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('service_orders', 'order_type')) {
                $table->string('order_type')->default('catalog');
            }
            if (!Schema::hasColumn('service_orders', 'custom_request_text')) {
                $table->text('custom_request_text')->nullable();
            }
            if (!Schema::hasColumn('service_orders', 'custom_request_image')) {
                $table->string('custom_request_image')->nullable();
            }
            if (!Schema::hasColumn('service_orders', 'customer_contact_phone')) {
                $table->string('customer_contact_phone')->nullable();
            }
            if (!Schema::hasColumn('service_orders', 'merchant_offer_price')) {
                $table->decimal('merchant_offer_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('service_orders', 'merchant_offer_notes')) {
                $table->text('merchant_offer_notes')->nullable();
            }
            if (!Schema::hasColumn('service_orders', 'invited_driver_id')) {
                $table->unsignedBigInteger('invited_driver_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_orders')) {
            return;
        }

        Schema::table('service_orders', function (Blueprint $table) {
            foreach ([
                'invited_driver_id',
                'merchant_offer_notes',
                'merchant_offer_price',
                'customer_contact_phone',
                'custom_request_image',
                'custom_request_text',
                'order_type',
            ] as $col) {
                if (Schema::hasColumn('service_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
