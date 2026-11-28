<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\CoachingRequest;
use App\Models\Member;
use App\Models\TrainingProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CoachingService
{
    public function createRequest(array $data, User $by): CoachingRequest
    {
        return DB::transaction(function () use ($data, $by) {
            // lock baris member supaya dua request bersamaan nggak lolos cek "pending"
            $member = Member::whereKey($data['member_id'])->lockForUpdate()->firstOrFail();

            if (!$member->activeMembership()) {
                throw new BusinessRuleException('Member harus punya membership aktif untuk mengajukan coaching.');
            }

            if ($member->coachingRequests()->where('status', CoachingRequest::STATUS_PENDING)->exists()) {
                throw new BusinessRuleException('Member masih punya pengajuan coaching yang menunggu respons.');
            }

            if ($member->trainingPrograms()->current()->exists()) {
                throw new BusinessRuleException('Member masih punya program coaching yang berjalan.');
            }

            return CoachingRequest::create([
                ...Arr::only($data, [
                    'member_id',
                    'trainer_id',
                    'goal_type',
                    'height_cm',
                    'initial_weight_kg',
                    'target_weight_kg',
                    'activity_level',
                    'medical_notes',
                    'preferred_days',
                ]),
                'status' => CoachingRequest::STATUS_PENDING,
                'requested_by' => $by->id,
            ]);
        });
    }

    /**
     * Accept = ubah status request dan buat program dalam SATU transaction.
     * Reject = ubah status saja.
     */
    public function respond(CoachingRequest $request, array $data): CoachingRequest
    {
        return DB::transaction(function () use ($request, $data) {
            $request = CoachingRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status !== CoachingRequest::STATUS_PENDING) {
                throw new BusinessRuleException('Pengajuan ini sudah direspons atau dibatalkan.');
            }

            if ($data['action'] === 'reject') {
                $request->update([
                    'status' => CoachingRequest::STATUS_REJECTED,
                    'reject_reason' => $data['reject_reason'],
                    'responded_at' => now(),
                ]);

                return $request;
            }

            $member = Member::whereKey($request->member_id)->lockForUpdate()->firstOrFail();

            if (!$member->activeMembership()) {
                throw new BusinessRuleException('Membership member sudah tidak aktif, program tidak bisa dimulai.');
            }

            if ($member->trainingPrograms()->current()->exists()) {
                throw new BusinessRuleException('Member sudah punya program coaching yang berjalan.');
            }

            $start = isset($data['start_date']) ? Carbon::parse($data['start_date'])->startOfDay() : today();
            $end = $start->copy()->addWeeks((int) $data['duration_weeks'])->subDay(); // inklusif

            $request->update([
                'status' => CoachingRequest::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            TrainingProgram::create([
                'coaching_request_id' => $request->id,
                'member_id' => $request->member_id,
                'trainer_id' => $request->trainer_id,
                'start_date' => $start,
                'end_date' => $end,
                'status' => TrainingProgram::STATUS_ACTIVE,
            ]);

            return $request;
        });
    }

    public function cancelRequest(CoachingRequest $request): CoachingRequest
    {
        $affected = CoachingRequest::whereKey($request->id)
            ->where('status', CoachingRequest::STATUS_PENDING)
            ->update(['status' => CoachingRequest::STATUS_CANCELLED]);

        if (!$affected) {
            throw new BusinessRuleException('Hanya pengajuan yang masih menunggu yang bisa dibatalkan.');
        }

        return $request->refresh();
    }

    public function updateNutrition(TrainingProgram $program, array $data): TrainingProgram
    {
        if ($program->status !== TrainingProgram::STATUS_ACTIVE) {
            throw new BusinessRuleException('Program sudah selesai atau dibatalkan.');
        }

        $program->update(Arr::only($data, [
            'daily_calories',
            'protein_g',
            'carbs_g',
            'fats_g',
            'diet_guidelines',
        ]));

        return $program;
    }

    public function closeProgram(TrainingProgram $program, string $status): TrainingProgram
    {
        // update bersyarat = atomic, dua klik bersamaan nggak bisa dua-duanya sukses
        $affected = TrainingProgram::whereKey($program->id)
            ->where('status', TrainingProgram::STATUS_ACTIVE)
            ->update(['status' => $status]);

        if (!$affected) {
            throw new BusinessRuleException('Program sudah selesai atau dibatalkan.');
        }

        return $program->refresh();
    }
}
