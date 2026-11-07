<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addNullableUnsignedBigInt('bidding_trips', 'ride_book_id');
        $this->addNullableUnsignedBigInt('service_orders', 'ride_book_id');
        $this->addNullableUnsignedBigInt('ride_book', 'trip_request_id');
        $this->addNullableUnsignedBigInt('ride_book', 'bidding_trip_id');
        $this->addNullableUnsignedBigInt('ride_book', 'service_order_id');
    }

    public function down(): void
    {
        $this->dropIfExists('bidding_trips', 'ride_book_id');
        $this->dropIfExists('service_orders', 'ride_book_id');
        $this->dropIfExists('ride_book', 'trip_request_id');
        $this->dropIfExists('ride_book', 'bidding_trip_id');
        $this->dropIfExists('ride_book', 'service_order_id');
    }

    private function addNullableUnsignedBigInt(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->unsignedBigInteger($column)->nullable();
        });
    }

    private function dropIfExists(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });
    }
};
