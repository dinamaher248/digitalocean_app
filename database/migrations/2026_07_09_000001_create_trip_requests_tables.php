<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trip_requests')) {
            Schema::create('trip_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('service_type'); // quick_trip | goods
                $table->string('vehicle_type'); // motorcycle | tricycle | quarter_truck | half_truck
                $table->string('pick_loc');
                $table->string('target_loc');
                $table->decimal('pick_lat', 10, 7)->nullable();
                $table->decimal('pick_lng', 10, 7)->nullable();
                $table->decimal('target_lat', 10, 7)->nullable();
                $table->decimal('target_lng', 10, 7)->nullable();
                $table->text('description');
                $table->string('contact_phone');
                $table->text('notes')->nullable();
                $table->decimal('distance_km', 8, 2)->nullable();
                $table->string('status')->default('awaiting_offers');
                $table->foreignId('accepted_offer_id')->nullable();
                $table->foreignId('ride_book_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('trip_offers')) {
            Schema::create('trip_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_request_id')->constrained('trip_requests')->cascadeOnDelete();
                $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamps();

                $table->unique(['trip_request_id', 'driver_id']);
            });
        }

        if (Schema::hasTable('ride_book') && !Schema::hasColumn('ride_book', 'trip_request_id')) {
            Schema::table('ride_book', function (Blueprint $table) {
                $table->unsignedBigInteger('trip_request_id')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_book') && Schema::hasColumn('ride_book', 'trip_request_id')) {
            Schema::table('ride_book', function (Blueprint $table) {
                $table->dropColumn('trip_request_id');
            });
        }

        Schema::dropIfExists('trip_offers');
        Schema::dropIfExists('trip_requests');
    }
};
