<?php

namespace Tests\Unit;

use App\Services\NutritionCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class NutritionCalculatorTest extends TestCase
{
    public function test_male_estimate_uses_mifflin_st_jeor(): void
    {
        $r = NutritionCalculator::estimate('male', Carbon::now()->subYears(30), 80, 180, 'moderate');

        $this->assertSame(['bmr' => 1780, 'tdee' => 2759], $r);
    }

    public function test_female_estimate_subtracts_161(): void
    {
        $r = NutritionCalculator::estimate('female', Carbon::now()->subYears(25), 60, 165, 'sedentary');

        $this->assertSame(['bmr' => 1345, 'tdee' => 1614], $r);
    }

    public function test_returns_null_when_gender_or_birth_date_missing(): void
    {
        $this->assertNull(NutritionCalculator::estimate(null, Carbon::now()->subYears(30), 80, 180, 'light'));
        $this->assertNull(NutritionCalculator::estimate('male', null, 80, 180, 'light'));
    }

    public function test_macro_calories(): void
    {
        $this->assertSame(1998, NutritionCalculator::macroCalories(160, 200, 62));
    }
}
