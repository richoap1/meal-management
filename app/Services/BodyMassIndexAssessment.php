<?php

namespace App\Services;

use RuntimeException;

class BodyMassIndexAssessment
{
    private array $adolescentCutoffs;

    public function __construct()
    {
        $this->adolescentCutoffs = json_decode(
            file_get_contents(resource_path('data/who-bmi-for-age-2007.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    public function assess(float $weight, float $age, float $height, string $sex, string $sport): array
    {
        $bmiValue = $weight / (($height / 100) ** 2);
        $bmi = round($bmiValue, 1);

        if ($sport === 'binaraga') {
            return [
                'value' => $bmi,
                'category' => 'exempt',
                'label' => 'Deteksi BMI dikecualikan untuk binaraga',
                'reference' => 'Pengecualian binaraga',
                'age_group' => $age < 19 ? 'adolescent' : 'adult',
                'automatic_diet' => false,
                'nutrition_mode' => 'sport_performance',
            ];
        }

        $isAdolescent = $age < 19;
        $category = $isAdolescent
            ? $this->adolescentCategory($bmiValue, $age, $sex)
            : $this->adultCategory($bmiValue);
        $nutritionMode = match ($category) {
            'underweight' => 'balanced_weight_support',
            'overweight', 'obesity' => $isAdolescent ? 'balanced_weight_management' : 'weight_management',
            default => 'balanced',
        };

        return [
            'value' => $bmi,
            'category' => $category,
            'label' => match ($category) {
                'underweight' => 'Berat badan di bawah rentang acuan',
                'healthy' => 'Dalam rentang acuan',
                'overweight' => 'Berat badan di atas rentang acuan',
                'obesity' => 'Obesitas menurut indikator BMI',
            },
            'reference' => $isAdolescent ? 'WHO 2007 BMI-for-age' : 'BMI dewasa',
            'age_group' => $isAdolescent ? 'adolescent' : 'adult',
            'automatic_diet' => in_array($category, ['overweight', 'obesity'], true),
            'nutrition_mode' => $nutritionMode,
        ];
    }

    private function adultCategory(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'underweight',
            $bmi < 25 => 'healthy',
            $bmi < 30 => 'overweight',
            default => 'obesity',
        };
    }

    private function adolescentCategory(float $bmi, float $age, string $sex): string
    {
        $ageInMonths = $age * 12;
        $lowerMonth = (int) floor($ageInMonths);
        $upperMonth = min(228, $lowerMonth + 1);
        $fraction = $ageInMonths - $lowerMonth;
        $sexCutoffs = $this->adolescentCutoffs[$sex] ?? null;
        $lowerCutoffs = $sexCutoffs[$lowerMonth] ?? null;
        $upperCutoffs = $sexCutoffs[$upperMonth] ?? null;

        if (! is_array($lowerCutoffs) || ! is_array($upperCutoffs)) {
            throw new RuntimeException('WHO BMI-for-age reference values are missing for this age.');
        }

        $cutoffs = array_map(
            fn (float $lower, float $upper): float => $lower + (($upper - $lower) * $fraction),
            $lowerCutoffs,
            $upperCutoffs,
        );

        return match (true) {
            $bmi < $cutoffs[0] => 'underweight',
            $bmi > $cutoffs[2] => 'obesity',
            $bmi > $cutoffs[1] => 'overweight',
            default => 'healthy',
        };
    }
}
