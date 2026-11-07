<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'avg_rating')) {
                    $table->decimal('avg_rating', 3, 2)->default(0);
                }
                if (!Schema::hasColumn('users', 'ratings_count')) {
                    $table->unsignedInteger('ratings_count')->default(0);
                }
            });
        }

        if (Schema::hasTable('merchants')) {
            Schema::table('merchants', function (Blueprint $table) {
                if (!Schema::hasColumn('merchants', 'avg_rating')) {
                    $table->decimal('avg_rating', 3, 2)->default(0);
                }
                if (!Schema::hasColumn('merchants', 'ratings_count')) {
                    $table->unsignedInteger('ratings_count')->default(0);
                }
            });
        }

        if (!Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('ratee_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('ratee_merchant_id')->nullable();
                $table->unsignedTinyInteger('score'); // 1-5
                $table->string('comment', 500)->nullable();
                $table->string('context_type', 40); // bidding_trip, trip_request, service_order
                $table->unsignedBigInteger('context_id');
                $table->timestamps();

                $table->unique(
                    ['rater_id', 'context_type', 'context_id', 'ratee_user_id', 'ratee_merchant_id'],
                    'ratings_unique_per_context'
                );
                $table->index(['ratee_user_id']);
                $table->index(['ratee_merchant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'ratings_count')) {
                    $table->dropColumn('ratings_count');
                }
                if (Schema::hasColumn('users', 'avg_rating')) {
                    $table->dropColumn('avg_rating');
                }
            });
        }

        if (Schema::hasTable('merchants')) {
            Schema::table('merchants', function (Blueprint $table) {
                if (Schema::hasColumn('merchants', 'ratings_count')) {
                    $table->dropColumn('ratings_count');
                }
                if (Schema::hasColumn('merchants', 'avg_rating')) {
                    $table->dropColumn('avg_rating');
                }
            });
        }
    }
};
