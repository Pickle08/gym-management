<?php

namespace Tests\Concerns;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipService;

trait CreatesFixtures
{
    protected function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $this->withToken($user->createToken('test')->plainTextToken);

        return $user;
    }

    protected function makePlan(int $days = 30, int $price = 250000): MembershipPlan
    {
        return MembershipPlan::create([
            'name' => "Plan {$days} hari",
            'duration_days' => $days,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    protected function memberWithActiveMembership(User $by, ?MembershipPlan $plan = null): Member
    {
        $plan ??= $this->makePlan();
        $member = Member::create(['name' => 'Member Test']);
        $service = app(MembershipService::class);

        $membership = $service->create($member, $plan);
        $service->recordPayment($membership, [
            'amount' => $plan->price,
            'method' => 'cash',
        ], $by);

        return $member;
    }

    protected function makeTrainer(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'trainer', 'is_active' => true], $attrs));
    }

    // Ganti pengguna di tengah test. forgetGuards() wajib: guard Sanctum menyimpan user
// dari request pertama, tanpa ini request berikutnya diam-diam masih jadi user lama.
    protected function actAs(User $user): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    protected function coachingPayload(Member $member, User $trainer, array $override = []): array
    {
        return array_merge([
            'member_id' => $member->id,
            'trainer_id' => $trainer->id,
            'goal_type' => 'cutting',
            'height_cm' => 172,
            'initial_weight_kg' => 82.5,
            'target_weight_kg' => 75,
            'activity_level' => 'moderate',
        ], $override);
    }
}
