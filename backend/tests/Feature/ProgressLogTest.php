<?php

namespace Tests\Feature;

use App\Models\ProgressLog;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\CoachingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class ProgressLogTest extends TestCase
{
    use RefreshDatabase, CreatesFixtures;

    private User $staff;
    private User $trainer;
    private TrainingProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-03 10:00:00');

        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->trainer = $this->makeTrainer();

        // program 4 minggu: 3 Nov - 30 Nov, berat awal 82.5, target 75
        $member = $this->memberWithActiveMembership($this->staff);
        $service = app(CoachingService::class);
        $request = $service->createRequest($this->coachingPayload($member, $this->trainer), $this->staff);
        $service->respond($request, ['action' => 'accept', 'duration_weeks' => 4]);

        $this->program = TrainingProgram::firstOrFail();
    }

    private function url(): string
    {
        return "/api/programs/{$this->program->id}/logs";
    }

    private function payload(array $override = []): array
    {
        return array_merge(['log_date' => '2026-11-10', 'weight_kg' => 80, 'body_fat_pct' => 22.5], $override);
    }

    private function makeLog(array $override = []): ProgressLog
    {
        return ProgressLog::create(array_merge([
            'training_program_id' => $this->program->id,
            'log_date' => '2026-11-10',
            'weight_kg' => 80,
            'recorded_by' => $this->trainer->id,
        ], $override));
    }

    public function test_trainer_records_log_and_summary_is_computed(): void
    {
        $this->travelTo('2026-11-10 10:00:00');

        $this->actAs($this->trainer);
        $this->postJson($this->url(), $this->payload(['notes' => 'Minggu 1']))
            ->assertCreated()
            ->assertJsonPath('data.log_date', '2026-11-10')
            ->assertJsonPath('data.recorded_by', $this->trainer->name);

        $s = $this->getJson($this->url())->assertOk()->json('data.summary');

        $this->assertEquals(82.5, $s['initial_weight_kg']);
        $this->assertEquals(75, $s['target_weight_kg']);
        $this->assertEquals(80, $s['latest_weight_kg']);
        $this->assertEquals(-2.5, $s['change_kg']);
        $this->assertEquals(-5, $s['remaining_kg']);
        $this->assertSame(33, $s['progress_pct']); // (80-82.5)/(75-82.5)
        $this->assertEquals(22.5, $s['latest_body_fat_pct']);
        $this->assertSame(1, $s['log_count']);
    }

    public function test_summary_is_empty_without_logs(): void
    {
        $s = $this->actAs($this->trainer)->getJson($this->url())->assertOk()->json('data.summary');

        $this->assertNull($s['latest_weight_kg']);
        $this->assertNull($s['progress_pct']);
        $this->assertSame(0, $s['log_count']);
    }

    public function test_admin_can_record_a_log(): void
    {
        $this->travelTo('2026-11-10 10:00:00');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actAs($admin)->postJson($this->url(), $this->payload())->assertCreated();
    }

    public function test_only_one_log_per_date(): void
    {
        $this->travelTo('2026-11-10 10:00:00');

        $this->actAs($this->trainer);
        $this->postJson($this->url(), $this->payload())->assertCreated();
        $this->postJson($this->url(), $this->payload(['weight_kg' => 79]))->assertStatus(422);

        $this->assertDatabaseCount('progress_logs', 1);
    }

    public function test_date_must_be_inside_program_period_and_not_in_the_future(): void
    {
        $this->travelTo('2026-11-10 10:00:00');
        $this->actAs($this->trainer);

        $this->postJson($this->url(), $this->payload(['log_date' => '2026-11-02']))->assertStatus(422);

        $this->postJson($this->url(), $this->payload(['log_date' => '2026-11-11']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('log_date');

        // program sudah lewat masa berlaku: backdate dalam periode boleh, di luar periode tidak
        $this->travelTo('2026-12-05 10:00:00');
        $this->postJson($this->url(), $this->payload(['log_date' => '2026-12-01']))->assertStatus(422);
        $this->postJson($this->url(), $this->payload(['log_date' => '2026-11-30']))->assertCreated();
    }

    public function test_values_are_validated(): void
    {
        $this->travelTo('2026-11-10 10:00:00');
        $this->actAs($this->trainer);

        $this->postJson($this->url(), $this->payload(['weight_kg' => 20]))
            ->assertStatus(422)->assertJsonValidationErrors('weight_kg');
        $this->postJson($this->url(), $this->payload(['weight_kg' => 400]))
            ->assertStatus(422)->assertJsonValidationErrors('weight_kg');
        $this->postJson($this->url(), $this->payload(['body_fat_pct' => 70]))
            ->assertStatus(422)->assertJsonValidationErrors('body_fat_pct');

        $this->assertDatabaseCount('progress_logs', 0);
    }

    public function test_staff_has_no_access_to_body_metrics(): void
    {
        $this->travelTo('2026-11-10 10:00:00');
        $log = $this->makeLog();

        $this->actAs($this->staff);
        $this->getJson($this->url())->assertForbidden();
        $this->postJson($this->url(), $this->payload(['log_date' => '2026-11-09']))->assertForbidden();
        $this->putJson("/api/progress-logs/{$log->id}", $this->payload())->assertForbidden();
        $this->deleteJson("/api/progress-logs/{$log->id}")->assertForbidden();
    }

    public function test_other_trainer_cannot_see_or_touch_logs(): void
    {
        $this->travelTo('2026-11-10 10:00:00');
        $log = $this->makeLog();
        $other = $this->makeTrainer();

        $this->actAs($other);
        $this->getJson($this->url())->assertNotFound();
        $this->postJson($this->url(), $this->payload(['log_date' => '2026-11-09']))->assertNotFound();
        $this->putJson("/api/progress-logs/{$log->id}", $this->payload(['weight_kg' => 70]))->assertNotFound();
        $this->deleteJson("/api/progress-logs/{$log->id}")->assertNotFound();

        $this->assertEquals(80, $log->fresh()->weight_kg);
        $this->assertDatabaseCount('progress_logs', 1);
    }

    public function test_owner_can_update_and_delete_but_not_collide_dates(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $first = $this->makeLog(['log_date' => '2026-11-10']);
        $second = $this->makeLog(['log_date' => '2026-11-17', 'weight_kg' => 79]);

        $this->actAs($this->trainer);

        $this->putJson("/api/progress-logs/{$second->id}", $this->payload(['log_date' => '2026-11-10']))
            ->assertStatus(422);

        $this->putJson("/api/progress-logs/{$second->id}", $this->payload(['log_date' => '2026-11-17', 'weight_kg' => 78.5]))
            ->assertOk();
        $this->assertEquals(78.5, $second->fresh()->weight_kg);

        $this->deleteJson("/api/progress-logs/{$first->id}")->assertOk();
        $this->assertDatabaseCount('progress_logs', 1);
    }

    public function test_logs_are_listed_in_chronological_order(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $this->makeLog(['log_date' => '2026-11-17']);
        $this->makeLog(['log_date' => '2026-11-10']);

        $dates = collect($this->actAs($this->trainer)->getJson($this->url())->json('data.items'))
            ->pluck('log_date')->all();

        $this->assertSame(['2026-11-10', '2026-11-17'], $dates);
    }

    public function test_closed_program_freezes_its_history(): void
    {
        $this->travelTo('2026-11-12 10:00:00');
        $log = $this->makeLog();

        app(CoachingService::class)->closeProgram($this->program, TrainingProgram::STATUS_COMPLETED);

        $this->actAs($this->trainer);
        $this->postJson($this->url(), $this->payload(['log_date' => '2026-11-11']))->assertStatus(422);
        $this->putJson("/api/progress-logs/{$log->id}", $this->payload(['weight_kg' => 70]))->assertStatus(422);
        $this->deleteJson("/api/progress-logs/{$log->id}")->assertStatus(422);

        $this->getJson($this->url())->assertOk()->assertJsonCount(1, 'data.items');
        $this->assertDatabaseCount('progress_logs', 1);
    }
}
