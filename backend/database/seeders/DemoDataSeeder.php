<?php

namespace Database\Seeders;

use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\MembershipService;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private const DAYS = 90;

    public function run(MembershipService $memberships, AttendanceService $attendances): void
    {
        mt_srand(42); // hasil sama tiap kali seed
        $faker = Faker::create('id_ID');
        $faker->seed(42);

        $staff = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->where('is_active', true)->get()->values();

        if ($staff->isEmpty()) {
            throw new \RuntimeException('Jalankan UserSeeder dulu.');
        }

        $plans = MembershipPlan::all()->keyBy('name');
        $weights = ['Bulanan' => 60, '3 Bulan' => 20, 'Harian' => 12, 'Tahunan' => 8];
        $methods = ['cash', 'transfer', 'qris'];

        $realNow = now();
        $base = today();

        $pickUser = fn() => $staff[mt_rand(0, $staff->count() - 1)];
        $pickMethod = fn() => $methods[mt_rand(0, 2)];
        $pickPlan = function () use ($plans, $weights) {
            $roll = mt_rand(1, array_sum($weights));
            foreach ($weights as $name => $w) {
                if (($roll -= $w) <= 0) {
                    return $plans[$name];
                }
            }
            return $plans['Bulanan'];
        };

        // waktu acak di hari tertentu, nggak boleh melewati "sekarang" yang asli
        $stamp = function (Carbon $day, int $fromHour = 7, int $toHour = 20) use ($realNow) {
            $t = $day->copy()->setTime(mt_rand($fromHour, $toHour), mt_rand(0, 59));
            return $t->gt($realNow) ? $realNow->copy() : $t;
        };

        $pay = function (Membership $m) use ($memberships, $pickUser, $pickMethod) {
            $memberships->recordPayment($m, [
                'amount' => $m->price,
                'method' => $pickMethod(),
                'paid_at' => now(),
            ], $pickUser());
        };

        try {
            for ($i = self::DAYS; $i >= 0; $i--) {
                $day = $base->copy()->subDays($i);
                Carbon::setTestNow($stamp($day));

                // 1. member baru (hari pertama: batch awal 10 orang)
                $newCount = $i === self::DAYS ? 10 : (mt_rand(1, 100) <= 55 ? mt_rand(1, 2) : 0);

                for ($n = 0; $n < $newCount; $n++) {
                    $member = Member::create([
                        'name' => $faker->name(),
                        'phone' => '08' . mt_rand(1, 9) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                        'email' => mt_rand(1, 100) <= 70 ? $faker->unique()->safeEmail() : null,
                        'birth_date' => $faker->dateTimeBetween('1965-01-01', '2007-12-31')->format('Y-m-d'),
                        'address' => $faker->streetAddress(),
                    ]);

                    $membership = $memberships->create($member, $pickPlan());

                    $leavePending = $i <= 2 && mt_rand(1, 100) <= 30;
                    if (!$leavePending) {
                        $pay($membership);
                    }
                }

                // 2. perpanjangan: yang mau habis ≤3 hari dan belum renew
                Membership::expiringWithin(3)->with(['member', 'plan'])->get()->each(
                    function (Membership $m) use ($memberships, $pay) {
                        if ($m->plan->duration_days < 30 || mt_rand(1, 100) > 30) {
                            return;
                        }

                        try {
                            $pay($memberships->create($m->member, $m->plan));
                        } catch (BusinessRuleException) {
                            // sudah ada pending, lewati
                        }
                    }
                );

                // 3. check-in member aktif
                $rate = $day->isSunday() ? 20 : 50;

                Membership::active()->with('member')->get()->each(
                    function (Membership $m) use ($attendances, $rate, $stamp, $day, $pickUser) {
                        if (mt_rand(1, 100) > $rate) {
                            return;
                        }

                        Carbon::setTestNow($stamp($day, 6, 21));

                        try {
                            $attendances->checkIn($m->member, $pickUser());
                        } catch (BusinessRuleException) {
                            // sudah check-in hari ini
                        }
                    }
                );
            }
        } finally {
            Carbon::setTestNow(); // WAJIB reset, jangan sampai waktu "nyangkut"
        }

        $this->command?->info(sprintf(
            'Demo data: %d member, %d membership, %d payment, %d attendance.',
            Member::count(),
            Membership::count(),
            Payment::count(),
            Attendance::count()
        ));
    }
}
