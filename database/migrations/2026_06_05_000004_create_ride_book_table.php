<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ride_book')) {
            Schema::create('ride_book', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('ride_id')->nullable();
                $table->unsignedInteger('seat_count')->nullable();
                $table->string('amount')->nullable();
                $table->text('msg')->nullable();
                $table->string('pick_loc')->nullable();
                $table->string('target_loc')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_book');
    }
};
