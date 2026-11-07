<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_orders')) {
            return;
        }

        if (!Schema::hasColumn('service_orders', 'commission_applied')) {
            Schema::table('service_orders', function (Blueprint $table) {
                $table->boolean('commission_applied')->default(false);
            });
        }

        if (!Schema::hasColumn('service_orders', 'commission_amount')) {
            return;
        }

        $alreadyAccepted = [
            'awaiting_driver',
            'driver_invited',
            'driver_assigned',
            'handed_to_driver',
            'in_delivery',
            'in_progress',
            'completed',
            'offer_accepted',
        ];

        DB::table('service_orders')
            ->whereIn('status', $alreadyAccepted)
            ->where('commission_amount', '>', 0)
            ->update(['commission_applied' => true]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_orders')) {
            return;
        }

        if (Schema::hasColumn('service_orders', 'commission_applied')) {
            Schema::table('service_orders', function (Blueprint $table) {
                $table->dropColumn('commission_applied');
            });
        }
    }
};
