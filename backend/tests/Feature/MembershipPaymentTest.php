<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class MembershipPaymentTest extends TestCase
{
    use RefreshDatabase, CreatesFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-24 10:00:00');
    }

    public function test_new_membership_starts_as_pending_payment(): void
    {
        $this->actingAsRole('staff');
        $plan = $this->makePlan();
        $member = Member::create(['name' => 'Budi']);

        $this->postJson("/api/members/{$member->id}/memberships", ['membership_plan_id' => $plan->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment');
    }

    public function test_only_one_pending_membership_per_member(): void
    {
        $this->actingAsRole('staff');
        $plan = $this->makePlan();
        $member = Member::create(['name' => 'Budi']);

        $this->postJson("/api/members/{$member->id}/memberships", ['membership_plan_id' => $plan->id])->assertCreated();
        $this->postJson("/api/members/{$member->id}/memberships", ['membership_plan_id' => $plan->id])->assertStatus(422);
    }

    public function test_payment_with_wrong_amount_is_rejected(): void
    {
        $this->actingAsRole('staff');
        $plan = $this->makePlan(30, 250000);
        $member = Member::create(['name' => 'Budi']);
        $membership = app(\App\Services\MembershipService::class)->create($member, $plan);

        $this->postJson("/api/memberships/{$membership->id}/payments", ['amount' => 100000, 'method' => 'cash'])
            ->assertStatus(422);

        $this->assertSame(Membership::STATUS_PENDING, $membership->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_exact_payment_activates_membership_with_correct_dates(): void
    {
        $this->actingAsRole('staff');
        $plan = $this->makePlan(30, 250000);
        $member = Member::create(['name' => 'Budi']);
        $membership = app(\App\Services\MembershipService::class)->create($member, $plan);

        $this->postJson("/api/memberships/{$membership->id}/payments", ['amount' => 250000, 'method' => 'qris'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.start_date', '2026-11-24')
            ->assertJsonPath('data.end_date', '2026-12-23'); // inklusif: 30 hari

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cannot_pay_the_same_membership_twice(): void
    {
        $staff = $this->actingAsRole('staff');
        $plan = $this->makePlan();
        $member = $this->memberWithActiveMembership($staff, $plan);
        $membership = $member->memberships()->first();

        $this->postJson("/api/memberships/{$membership->id}/payments", ['amount' => $plan->price, 'method' => 'cash'])
            ->assertStatus(422);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_renewal_starts_the_day_after_current_membership_ends(): void
    {
        $staff = $this->actingAsRole('staff');
        $plan = $this->makePlan(30, 250000);
        $member = $this->memberWithActiveMembership($staff, $plan); // 24 Nov - 23 Des

        $renewal = app(\App\Services\MembershipService::class)->create($member, $plan);

        $this->postJson("/api/memberships/{$renewal->id}/payments", ['amount' => 250000, 'method' => 'cash'])
            ->assertCreated()
            ->assertJsonPath('data.start_date', '2026-12-24')
            ->assertJsonPath('data.end_date', '2027-01-22')
            ->assertJsonPath('data.state', 'upcoming');
    }

    public function test_only_pending_membership_can_be_cancelled(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);
        $active = $member->memberships()->first();

        $this->postJson("/api/memberships/{$active->id}/cancel")->assertStatus(422);
    }

    public function test_trainer_cannot_create_membership(): void
    {
        $this->actingAsRole('trainer');
        $plan = $this->makePlan();
        $member = Member::create(['name' => 'Budi']);

        $this->postJson("/api/members/{$member->id}/memberships", ['membership_plan_id' => $plan->id])
            ->assertForbidden();
    }

    public function test_member_with_active_membership_cannot_be_deleted(): void
    {
        $admin = $this->actingAsRole('admin');
        $member = $this->memberWithActiveMembership($admin);

        $this->deleteJson("/api/members/{$member->id}")->assertStatus(422);
        $this->assertNotSoftDeleted($member);
    }
}
