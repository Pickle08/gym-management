<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\ProgressLog;
use App\Models\TrainingProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProgressService
{
    private const FIELDS = ['log_date', 'weight_kg', 'body_fat_pct', 'notes'];
    private const DUPLICATE = 'Sudah ada pencatatan untuk tanggal ini. Edit pencatatan yang ada.';

    public function create(TrainingProgram $program, array $data, User $by): ProgressLog
    {
        $this->assertWritable($program, $data['log_date']);

        try {
            return $program->progressLogs()->create([
                ...Arr::only($data, self::FIELDS),
                'recorded_by' => $by->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // dua request bersamaan lolos cek aplikasi, unique constraint DB yang menang
            throw new BusinessRuleException(self::DUPLICATE);
        }
    }

    public function update(ProgressLog $log, array $data): ProgressLog
    {
        $this->assertWritable($log->program, $data['log_date']);

        try {
            $log->update(Arr::only($data, self::FIELDS));
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException(self::DUPLICATE);
        }

        return $log;
    }

    public function delete(ProgressLog $log): void
    {
        $this->assertProgramOpen($log->program);

        $log->delete();
    }

    /**
     * $logs harus terurut naik berdasarkan tanggal.
     * Progres = seberapa jauh berat bergerak dari berat awal menuju target.
     */
    public function summary(TrainingProgram $program, Collection $logs): array
    {
        $request = $program->coachingRequest;
        $initial = $request->initial_weight_kg;
        $target = $request->target_weight_kg;
        $latest = $logs->last();

        $progress = null;
        if ($latest && $target !== null && (float) $target !== (float) $initial) {
            $progress = (int) round((($latest->weight_kg - $initial) / ($target - $initial)) * 100);
        }

        return [
            'initial_weight_kg' => $initial,
            'target_weight_kg' => $target,
            'latest_weight_kg' => $latest?->weight_kg,
            'latest_date' => $latest?->log_date->toDateString(),
            'latest_body_fat_pct' => $logs->whereNotNull('body_fat_pct')->last()?->body_fat_pct,
            'change_kg' => $latest ? round($latest->weight_kg - $initial, 1) : null,
            'remaining_kg' => ($latest && $target !== null) ? round($target - $latest->weight_kg, 1) : null,
            'progress_pct' => $progress,
            'log_count' => $logs->count(),
        ];
    }

    private function assertProgramOpen(TrainingProgram $program): void
    {
        if ($program->status !== TrainingProgram::STATUS_ACTIVE) {
            throw new BusinessRuleException('Program sudah selesai atau dibatalkan, pencatatan tidak bisa diubah.');
        }
    }

    private function assertWritable(TrainingProgram $program, string $logDate): void
    {
        $this->assertProgramOpen($program);

        $date = Carbon::parse($logDate)->startOfDay();

        if ($date->lt($program->start_date)) {
            throw new BusinessRuleException(
                'Tanggal pencatatan sebelum program dimulai (' . $program->start_date->format('d M Y') . ').'
            );
        }

        if ($date->gt($program->end_date)) {
            throw new BusinessRuleException(
                'Tanggal pencatatan melewati akhir program (' . $program->end_date->format('d M Y') . ').'
            );
        }
    }
}
