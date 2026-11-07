<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class RatingService
{
    public function userRatingPayload(?User $user): array
    {
        if (!$user) {
            return [
                'avg_rating' => 0.0,
                'ratings_count' => 0,
            ];
        }

        return [
            'avg_rating' => round((float) ($user->avg_rating ?? 0), 1),
            'ratings_count' => (int) ($user->ratings_count ?? 0),
        ];
    }

    public function merchantRatingPayload(?Merchant $merchant): array
    {
        if (!$merchant) {
            return [
                'avg_rating' => 0.0,
                'ratings_count' => 0,
            ];
        }

        return [
            'avg_rating' => round((float) ($merchant->avg_rating ?? 0), 1),
            'ratings_count' => (int) ($merchant->ratings_count ?? 0),
        ];
    }

    public function recalculateUser(int $userId): void
    {
        if (!Schema::hasColumn('users', 'avg_rating') || !Schema::hasTable('ratings')) {
            return;
        }

        $avg = Rating::query()->where('ratee_user_id', $userId)->avg('score');
        $count = Rating::query()->where('ratee_user_id', $userId)->count();

        User::where('id', $userId)->update([
            'avg_rating' => round((float) ($avg ?? 0), 2),
            'ratings_count' => $count,
        ]);
    }

    public function recalculateMerchant(int $merchantId): void
    {
        if (!Schema::hasTable('merchants') || !Schema::hasColumn('merchants', 'avg_rating')) {
            return;
        }
        if (!Schema::hasTable('ratings')) {
            return;
        }

        $avg = Rating::query()->where('ratee_merchant_id', $merchantId)->avg('score');
        $count = Rating::query()->where('ratee_merchant_id', $merchantId)->count();

        Merchant::where('id', $merchantId)->update([
            'avg_rating' => round((float) ($avg ?? 0), 2),
            'ratings_count' => $count,
        ]);
    }
}
