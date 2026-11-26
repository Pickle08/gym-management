<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MembershipPlan;

class MembershipPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['name' => 'Harian', 'duration_days' => 1, 'price' => 30000],
            ['name' => 'Bulanan', 'duration_days' => 30, 'price' => 250000],
            ['name' => '3 Bulan', 'duration_days' => 90, 'price' => 650000],
            ['name' => 'Tahunan', 'duration_days' => 365, 'price' => 2200000],
        ];

        foreach ($plans as $p) {
            MembershipPlan::updateOrCreate(['name' => $p['name']], $p + ['is_active' => true]);
        }
    }
}
