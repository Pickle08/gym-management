<?php

namespace App\Services;

use Carbon\CarbonInterface;

class NutritionCalculator
{
    public const ACTIVITY_FACTORS = [
        'sedentary' => 1.2,
        'light' => 1.375,
        'moderate' => 1.55,
        'active' => 1.725,
        'very_active' => 1.9,
    ];

    /**
     * Mifflin-St Jeor. Hanya estimasi referensi untuk trainer, bukan saran medis.
     * Mengembalikan null kalau gender atau tanggal lahir belum diisi.
     */
    public static function estimate(
        ?string $gender,
        ?CarbonInterface $birthDate,
        float $weightKg,
        int $heightCm,
        string $activityLevel
    ): ?array {
        if (!in_array($gender, ['male', 'female'], true) || !$birthDate) {
            return null;
        }

        $age = $birthDate->age;
        $bmr = 10 * $weightKg + 6.25 * $heightCm - 5 * $age + ($gender === 'male' ? 5 : -161);
        $tdee = $bmr * (self::ACTIVITY_FACTORS[$activityLevel] ?? 1.2);

        return [
            'bmr' => (int) round($bmr),
            'tdee' => (int) round($tdee),
        ];
    }

    public static function macroCalories(int $proteinG, int $carbsG, int $fatsG): int
    {
        return $proteinG * 4 + $carbsG * 4 + $fatsG * 9;
    }
}
