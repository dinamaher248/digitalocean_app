<?php

namespace Database\Seeders;

use App\Models\KoshyApp;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DefaultAppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (Plan::query()->count() === 0) {
            Plan::insert([
                [
                    'name' => 'باقة شهرية',
                    'price' => 200,
                    'offer' => 0,
                    'days' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'باقة 3 أشهر',
                    'price' => 500,
                    'offer' => 50,
                    'days' => 90,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (!KoshyApp::query()->exists()) {
            KoshyApp::create([
                'phone' => env('WALLET_TRANSFER_NUMBER', '01000000000'),
            ]);
        }

        if (Schema::hasTable('settings') && empty(Setting::getValue('wallet_number'))) {
            Setting::setValue(
                'wallet_number',
                env('WALLET_TRANSFER_NUMBER', '01000000000')
            );
        }

        if (Schema::hasTable('settings')) {
            \App\Services\WelcomeScreenService::ensureDefaults();
        }
    }
}
