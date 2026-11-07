<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bidding_trips')) {
            Schema::create('bidding_trips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('vehicle_type', 50);
                $table->string('pickup_address', 500);
                $table->decimal('pickup_lat', 10, 7)->nullable();
                $table->decimal('pickup_lng', 10, 7)->nullable();
                $table->string('dropoff_address', 500);
                $table->decimal('dropoff_lat', 10, 7)->nullable();
                $table->decimal('dropoff_lng', 10, 7)->nullable();
                $table->text('description');
                $table->string('phone', 30);
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('pending');
                $table->decimal('final_price', 10, 2)->nullable();
                $table->unsignedBigInteger('accepted_offer_id')->nullable();
                $table->unsignedBigInteger('accepted_driver_id')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('bidding_offers')) {
            Schema::create('bidding_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bidding_trip_id')->constrained('bidding_trips')->cascadeOnDelete();
                $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('proposed_price', 10, 2);
                $table->string('status', 30)->default('pending');
                $table->timestamps();

                $table->unique(['bidding_trip_id', 'driver_id']);
                $table->index('status');
            });
        }

        if (Schema::hasTable('bidding_trips') && !Schema::hasColumn('bidding_trips', 'accepted_offer_fk')) {
            // Soft FK via column only; avoid altering if already migrated elsewhere.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bidding_offers');
        Schema::dropIfExists('bidding_trips');
    }
};
