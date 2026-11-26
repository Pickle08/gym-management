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
}
