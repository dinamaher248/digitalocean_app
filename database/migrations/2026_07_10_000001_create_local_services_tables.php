<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_categories')) {
            Schema::create('service_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('merchants')) {
            Schema::create('merchants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('service_categories')->cascadeOnDelete();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('image')->nullable();
                $table->text('address')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->boolean('is_open')->default(true);
                $table->boolean('pay_cash')->default(true);
                $table->boolean('pay_insta')->default(false);
                $table->boolean('pay_on_delivery')->default(true);
                $table->boolean('pay_wallet')->default(false);
                $table->string('wallet_number')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('merchant_products')) {
            Schema::create('merchant_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_orders')) {
            Schema::create('service_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('merchant_products')->nullOnDelete();
                $table->string('product_name');
                $table->decimal('product_price', 10, 2);
                $table->text('product_description')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_price', 10, 2);
                $table->text('notes')->nullable();
                $table->string('delivery_address');
                $table->text('delivery_details')->nullable();
                $table->decimal('delivery_lat', 10, 7)->nullable();
                $table->decimal('delivery_lng', 10, 7)->nullable();
                $table->string('payment_method');
                $table->string('payment_proof')->nullable();
                $table->string('status')->default('new');
                $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('accepted_delivery_offer_id')->nullable();
                $table->decimal('delivery_fee', 10, 2)->nullable();
                $table->decimal('commission_amount', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_delivery_offers')) {
            Schema::create('service_delivery_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
                $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->unique(['service_order_id', 'driver_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_delivery_offers');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('merchant_products');
        Schema::dropIfExists('merchants');
        Schema::dropIfExists('service_categories');
    }
};
