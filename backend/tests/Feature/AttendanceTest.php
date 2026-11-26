<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase, CreatesFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-24 10:00:00');
    }

    public function test_active_member_can_check_in(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);

        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])
            ->assertCreated()
            ->assertJsonPath('data.attendance_date', '2026-11-24');
    }

    public function test_double_check_in_same_day_is_rejected(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);

        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertCreated();
        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertStatus(422);

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_database_unique_constraint_blocks_duplicate_even_without_app_check(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);
        $membership = $member->memberships()->first();

        $row = [
            'member_id' => $member->id,
            'membership_id' => $membership->id,
            'attendance_date' => today(),
            'checked_in_at' => now(),
        ];

        Attendance::create($row);

        $this->expectException(UniqueConstraintViolationException::class);
        Attendance::create($row);
    }

    public function test_can_check_in_again_next_day(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);

        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertCreated();

        $this->travelTo('2026-11-25 09:00:00');
        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertCreated();

        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_member_without_membership_is_rejected(): void
    {
        $this->actingAsRole('staff');
        $member = Member::create(['name' => 'Kosong']);

        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertStatus(422);
    }

    public function test_expired_membership_is_rejected(): void
    {
        $this->actingAsRole('staff');
        $plan = $this->makePlan();
        $member = Member::create(['name' => 'Expired']);

        Membership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'price' => $plan->price,
            'status' => Membership::STATUS_ACTIVE,
        ]);

        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_membership_ending_today_is_still_valid(): void
    {
        $this->actingAsRole('staff');
        $plan = $this->makePlan();
        $member = Member::create(['name' => 'Hari Terakhir']);

        Membership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2026-10-26',
            'end_date' => '2026-11-24',
            'price' => $plan->price,
            'status' => Membership::STATUS_ACTIVE,
        ]);

        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertCreated();
    }

    public function test_check_in_by_qr_token(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);

        $this->postJson('/api/attendances/check-in', ['qr_token' => $member->qr_token])->assertCreated();
        $this->postJson('/api/attendances/check-in', ['qr_token' => str_repeat('0', 40)])->assertNotFound();
    }

    public function test_member_id_and_qr_token_cannot_be_sent_together(): void
    {
        $staff = $this->actingAsRole('staff');
        $member = $this->memberWithActiveMembership($staff);

        $this->postJson('/api/attendances/check-in', [
            'member_id' => $member->id,
            'qr_token' => $member->qr_token,
        ])->assertStatus(422);
    }

    public function test_trainer_can_view_but_not_check_in(): void
    {
        $this->actingAsRole('trainer');
        $member = Member::create(['name' => 'X']);

        $this->getJson('/api/attendances')->assertOk();
        $this->postJson('/api/attendances/check-in', ['member_id' => $member->id])->assertForbidden();
    }
}
