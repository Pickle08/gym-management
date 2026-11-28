<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\CoachingService;
use Illuminate\Database\Seeder;

class CoachingDemoSeeder extends Seeder
{
    public function run(CoachingService $coaching): void
    {
        $staff = User::where('email', 'staff@gym.test')->firstOrFail();
        $t1 = User::where('email', 'trainer@gym.test')->firstOrFail();
        $t2 = User::where('email', 'trainer2@gym.test')->firstOrFail();

        $members = Member::whereHas('memberships', fn($q) => $q->active())->orderBy('id')->take(6)->get();

        if ($members->count() < 6) {
            $this->command?->warn('Member aktif kurang dari 6, coaching demo dilewati.');
            return;
        }

        $scenarios = [
            [
                'trainer' => $t1,
                'goal' => 'cutting',
                'height' => 172,
                'weight' => 82.5,
                'target' => 75,
                'activity' => 'moderate',
                'days' => ['mon', 'wed', 'fri'],
                'notes' => 'Cedera bahu kanan, sudah pulih',
                'outcome' => 'accept',
                'weeks' => 12,
                'nutrition' => [2000, 160, 200, 62]
            ],
            [
                'trainer' => $t1,
                'goal' => 'bulking',
                'height' => 178,
                'weight' => 64,
                'target' => 72,
                'activity' => 'light',
                'days' => ['tue', 'thu', 'sat'],
                'notes' => null,
                'outcome' => 'accept',
                'weeks' => 16,
                'nutrition' => [3000, 180, 380, 90]
            ],
            [
                'trainer' => $t2,
                'goal' => 'recomposition',
                'height' => 165,
                'weight' => 70,
                'target' => 66,
                'activity' => 'active',
                'days' => ['mon', 'tue', 'thu'],
                'notes' => 'Nyeri lutut kiri saat squat dalam',
                'outcome' => 'accept',
                'weeks' => 8,
                'nutrition' => [2400, 190, 250, 70]
            ],
            [
                'trainer' => $t1,
                'goal' => 'endurance',
                'height' => 170,
                'weight' => 68,
                'target' => null,
                'activity' => 'moderate',
                'days' => ['sat', 'sun'],
                'notes' => null,
                'outcome' => 'pending'
            ],
            [
                'trainer' => $t2,
                'goal' => 'cutting',
                'height' => 160,
                'weight' => 74,
                'target' => 65,
                'activity' => 'sedentary',
                'days' => ['wed'],
                'notes' => null,
                'outcome' => 'reject',
                'reason' => 'Jadwal penuh sampai akhir bulan'
            ],
            [
                'trainer' => $t2,
                'goal' => 'bulking',
                'height' => 181,
                'weight' => 70,
                'target' => 78,
                'activity' => 'moderate',
                'days' => ['fri'],
                'notes' => null,
                'outcome' => 'accept',
                'weeks' => 4,
                'nutrition' => [2800, 170, 350, 85],
                'close' => true
            ],
        ];

        foreach ($scenarios as $i => $s) {
            $request = $coaching->createRequest([
                'member_id' => $members[$i]->id,
                'trainer_id' => $s['trainer']->id,
                'goal_type' => $s['goal'],
                'height_cm' => $s['height'],
                'initial_weight_kg' => $s['weight'],
                'target_weight_kg' => $s['target'],
                'activity_level' => $s['activity'],
                'medical_notes' => $s['notes'],
                'preferred_days' => $s['days'],
            ], $staff);

            if ($s['outcome'] === 'pending') {
                continue;
            }

            if ($s['outcome'] === 'reject') {
                $coaching->respond($request, ['action' => 'reject', 'reject_reason' => $s['reason']]);
                continue;
            }

            $coaching->respond($request, ['action' => 'accept', 'duration_weeks' => $s['weeks']]);
            $program = TrainingProgram::where('coaching_request_id', $request->id)->firstOrFail();

            [$kcal, $protein, $carbs, $fats] = $s['nutrition'];
            $coaching->updateNutrition($program, [
                'daily_calories' => $kcal,
                'protein_g' => $protein,
                'carbs_g' => $carbs,
                'fats_g' => $fats,
                'diet_guidelines' => 'Protein di setiap makan, air minimal 2,5 liter per hari.',
            ]);

            if (!empty($s['close'])) {
                $coaching->closeProgram($program, TrainingProgram::STATUS_COMPLETED);
            }
        }

        $this->command?->info('Coaching demo: 4 program (3 berjalan, 1 selesai), 1 pending, 1 ditolak.');
    }
}
