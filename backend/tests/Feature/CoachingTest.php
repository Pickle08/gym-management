<?php

namespace Tests\Feature;

use App\Models\CoachingRequest;
use App\Models\Member;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\CoachingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class CoachingTest extends TestCase
{
    use RefreshDatabase, CreatesFixtures;

    private User $staff;
    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-24 10:00:00');

        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->trainer = $this->makeTrainer();
    }

    private function member(): Member
    {
        return $this->memberWithActiveMembership($this->staff);
    }

    private function pending(Member $member, ?User $trainer = null): CoachingRequest
    {
        return app(CoachingService::class)->createRequest(
            $this->coachingPayload($member, $trainer ?? $this->trainer),
            $this->staff
        );
    }

    private function accepted(Member $member, ?User $trainer = null): TrainingProgram
    {
        $request = $this->pending($member, $trainer);
        app(CoachingService::class)->respond($request, ['action' => 'accept', 'duration_weeks' => 12]);

        return TrainingProgram::where('coaching_request_id', $request->id)->firstOrFail();
    }

    // ---------- pengajuan ----------

    public function test_staff_creates_request_and_gets_tdee_estimate(): void
    {
        $member = $this->member();
        $member->update(['gender' => 'male', 'birth_date' => '1996-01-15']); // 30 tahun

        $this->actAs($this->staff)
            ->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->trainer))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.estimate.bmr', 1755)
            ->assertJsonPath('data.estimate.tdee', 2720);
    }

    public function test_estimate_is_null_when_gender_is_missing(): void
    {
        $member = $this->member();

        $this->actAs($this->staff)
            ->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->trainer))
            ->assertCreated()
            ->assertJsonPath('data.estimate', null);
    }

    public function test_request_requires_active_membership(): void
    {
        $member = Member::create(['name' => 'Tanpa Membership']);

        $this->actAs($this->staff)
            ->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->trainer))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('coaching_requests', 0);
    }

    public function test_only_one_pending_request_per_member(): void
    {
        $member = $this->member();
        $payload = $this->coachingPayload($member, $this->trainer);

        $this->actAs($this->staff);
        $this->postJson('/api/coaching-requests', $payload)->assertCreated();
        $this->postJson('/api/coaching-requests', $payload)->assertStatus(422);

        $this->assertDatabaseCount('coaching_requests', 1);
    }

    public function test_trainer_must_be_an_active_trainer(): void
    {
        $member = $this->member();
        $inactive = $this->makeTrainer(['is_active' => false]);

        $this->actAs($this->staff);

        $this->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->staff))
            ->assertStatus(422)
            ->assertJsonValidationErrors('trainer_id');

        $this->postJson('/api/coaching-requests', $this->coachingPayload($member, $inactive))
            ->assertStatus(422)
            ->assertJsonValidationErrors('trainer_id');
    }

    public function test_trainer_cannot_create_requests(): void
    {
        $member = $this->member();

        $this->actAs($this->trainer)
            ->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->trainer))
            ->assertForbidden();
    }

    public function test_pending_request_can_be_cancelled_only_while_pending(): void
    {
        $request = $this->pending($this->member());

        $this->actAs($this->staff);
        $this->postJson("/api/coaching-requests/{$request->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->postJson("/api/coaching-requests/{$request->id}/cancel")->assertStatus(422);
    }

    // ---------- respons trainer ----------

    public function test_accept_creates_program_with_inclusive_end_date(): void
    {
        $request = $this->pending($this->member());

        $this->actAs($this->trainer)
            ->patchJson("/api/coaching-requests/{$request->id}/respond", [
                'action' => 'accept',
                'duration_weeks' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $program = TrainingProgram::firstOrFail();

        $this->assertSame('2026-11-24', $program->start_date->toDateString());
        $this->assertSame('2027-02-15', $program->end_date->toDateString()); // 12 minggu, inklusif
        $this->assertSame($this->trainer->id, $program->trainer_id);
        $this->assertSame('active', $program->state);
    }

    public function test_accept_requires_duration(): void
    {
        $request = $this->pending($this->member());

        $this->actAs($this->trainer)
            ->patchJson("/api/coaching-requests/{$request->id}/respond", ['action' => 'accept'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('duration_weeks');
    }

    public function test_accept_is_all_or_nothing_when_membership_expired_meanwhile(): void
    {
        $member = $this->member();
        $request = $this->pending($member);

        // membership habis sebelum trainer sempat merespons
        $member->memberships()->update(['start_date' => '2026-10-21', 'end_date' => '2026-11-20']);

        $this->actAs($this->trainer)
            ->patchJson("/api/coaching-requests/{$request->id}/respond", [
                'action' => 'accept',
                'duration_weeks' => 4,
            ])
            ->assertStatus(422);

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertDatabaseCount('training_programs', 0);
    }

    public function test_reject_needs_reason_and_request_cannot_be_answered_twice(): void
    {
        $request = $this->pending($this->member());
        $url = "/api/coaching-requests/{$request->id}/respond";

        $this->actAs($this->trainer);

        $this->patchJson($url, ['action' => 'reject'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reject_reason');

        $this->patchJson($url, ['action' => 'reject', 'reject_reason' => 'Jadwal penuh'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->patchJson($url, ['action' => 'accept', 'duration_weeks' => 4])->assertStatus(422);

        $this->assertDatabaseCount('training_programs', 0);
    }

    public function test_staff_cannot_respond(): void
    {
        $request = $this->pending($this->member());

        $this->actAs($this->staff)
            ->patchJson("/api/coaching-requests/{$request->id}/respond", ['action' => 'accept', 'duration_weeks' => 4])
            ->assertForbidden();
    }

    public function test_member_cannot_have_two_current_programs(): void
    {
        $member = $this->member();
        $this->accepted($member);

        $this->actAs($this->staff)
            ->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->trainer))
            ->assertStatus(422);
    }

    // ---------- akses silang antar trainer ----------

    public function test_other_trainer_cannot_see_or_touch_request_or_program(): void
    {
        $member = $this->member();
        $request = $this->pending($member);
        $other = $this->makeTrainer();

        // sebelum diterima
        $this->actAs($other);
        $this->getJson("/api/coaching-requests/{$request->id}")->assertNotFound();
        $this->patchJson("/api/coaching-requests/{$request->id}/respond", ['action' => 'accept', 'duration_weeks' => 4])
            ->assertNotFound();
        $this->getJson('/api/coaching-requests')->assertOk()->assertJsonPath('data.meta.total', 0);

        // setelah diterima oleh trainer pemilik
        $this->actAs($this->trainer)
            ->patchJson("/api/coaching-requests/{$request->id}/respond", ['action' => 'accept', 'duration_weeks' => 4])
            ->assertOk();
        $program = TrainingProgram::firstOrFail();

        $this->actAs($other);
        $this->getJson("/api/programs/{$program->id}")->assertNotFound();
        $this->putJson("/api/programs/{$program->id}/nutrition", [
            'daily_calories' => 2000,
            'protein_g' => 160,
            'carbs_g' => 200,
            'fats_g' => 62,
        ])->assertNotFound();
        $this->patchJson("/api/programs/{$program->id}/status", ['status' => 'cancelled'])->assertNotFound();
        $this->getJson('/api/programs')->assertOk()->assertJsonPath('data.meta.total', 0);

        $this->assertSame('active', $program->fresh()->status);
    }

    public function test_trainer_only_sees_own_current_clients(): void
    {
        $client = $this->member();
        $stranger = $this->memberWithActiveMembership($this->staff);
        $this->accepted($client);

        $this->actAs($this->trainer);
        $this->getJson('/api/members')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.id', $client->id);
        $this->getJson("/api/members/{$client->id}")->assertOk();
        $this->getJson("/api/members/{$stranger->id}")->assertNotFound();

        $this->actAs($this->staff);
        $this->getJson('/api/members')->assertOk()->assertJsonPath('data.meta.total', 2);
    }

    public function test_trainer_attendance_list_is_scoped_to_clients(): void
    {
        $client = $this->member();
        $stranger = $this->memberWithActiveMembership($this->staff);
        $this->accepted($client);

        $this->actAs($this->staff);
        $this->postJson('/api/attendances/check-in', ['member_id' => $client->id])->assertCreated();
        $this->postJson('/api/attendances/check-in', ['member_id' => $stranger->id])->assertCreated();

        $this->actAs($this->trainer)
            ->getJson('/api/attendances')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->actAs($this->staff)
            ->getJson('/api/attendances')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }

    // ---------- nutrisi dan siklus program ----------

    public function test_nutrition_must_match_macros(): void
    {
        $program = $this->accepted($this->member());
        $url = "/api/programs/{$program->id}/nutrition";

        $this->actAs($this->trainer);

        $this->putJson($url, ['daily_calories' => 2000, 'protein_g' => 160, 'carbs_g' => 200, 'fats_g' => 62])
            ->assertOk()
            ->assertJsonPath('data.nutrition.macro_calories', 1998);

        // makro = 1998 kkal, target 2500: selisih > 10%
        $this->putJson($url, ['daily_calories' => 2500, 'protein_g' => 160, 'carbs_g' => 200, 'fats_g' => 62])
            ->assertStatus(422)
            ->assertJsonValidationErrors('daily_calories');

        $this->putJson($url, ['daily_calories' => 500, 'protein_g' => 40, 'carbs_g' => 50, 'fats_g' => 15])
            ->assertStatus(422);

        $this->assertSame(2000, $program->fresh()->daily_calories); // nilai valid terakhir tetap
    }

    public function test_closing_program_releases_member_but_keeps_history_for_trainer(): void
    {
        $member = $this->member();
        $program = $this->accepted($member);
        $url = "/api/programs/{$program->id}/status";

        $this->actAs($this->trainer);
        $this->patchJson($url, ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.state', 'completed');
        $this->patchJson($url, ['status' => 'completed'])->assertStatus(422);

        // bukan klien aktif lagi, tapi program tetap terlihat sebagai riwayat
        $this->getJson('/api/members')->assertJsonPath('data.meta.total', 0);
        $this->getJson("/api/programs/{$program->id}")->assertOk();

        // nutrisi program yang sudah selesai nggak bisa diubah
        $this->putJson("/api/programs/{$program->id}/nutrition", [
            'daily_calories' => 2000,
            'protein_g' => 160,
            'carbs_g' => 200,
            'fats_g' => 62,
        ])->assertStatus(422);

        // member boleh mengajukan lagi
        $this->actAs($this->staff)
            ->postJson('/api/coaching-requests', $this->coachingPayload($member, $this->trainer))
            ->assertCreated();
    }
}
