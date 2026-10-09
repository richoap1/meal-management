<?php

namespace App\Services;

class PlannerProductCategory
{
    public function resolve(string $catalogCategory, string $subcategory, string $productName, string $fallbackCategory): string
    {
        if ($catalogCategory === '' && $subcategory === '') {
            return $fallbackCategory;
        }

        $normalizedCategory = mb_strtolower($catalogCategory);
        $normalizedSubcategory = mb_strtolower($subcategory);
        $normalizedProductName = mb_strtolower($productName);
        $proteinSubcategories = ['ayam', 'bakso', 'daging ayam', 'daging sapi', 'ikan', 'nugget', 'olahan ayam', 'seafood', 'sosis', 'telur'];
        $isProteinCategory = preg_match('/daging|seafood/u', $normalizedCategory);
        $isProteinSubcategory = in_array($normalizedSubcategory, $proteinSubcategories, true);
        $isGenericPreparedProduct = preg_match('/makanan instan|makanan siap saji|bumbu kaldu|bumbu instan/u', $normalizedSubcategory);
        $isCarbohydrateCategoryOrSubcategory = preg_match('/beras|pasta|mie|sereal|oatmeal|roti|tepung|kentang beku/u', $normalizedCategory.' '.$normalizedSubcategory);
        $isNamedProtein = preg_match('/(?<![a-z])(daging|ayam|ikan|telur|nugget|sosis|bakso|kornet|sarden|cumi|udang|kerang|kepiting)(?![a-z])/u', $normalizedProductName);

        if ($isProteinCategory || $isProteinSubcategory || ($isNamedProtein && ! $isGenericPreparedProduct && ! $isCarbohydrateCategoryOrSubcategory)) {
            return 'Protein';
        }
        if ($isCarbohydrateCategoryOrSubcategory || preg_match('/beras|pasta|mie|sereal|oatmeal|roti|tepung|kentang beku/u', $normalizedProductName)) {
            return 'Karbohidrat';
        }
        if (str_contains($normalizedCategory, 'buah & sayur') || str_contains($normalizedCategory, 'buah dan sayur') || in_array($normalizedSubcategory, ['sayur', 'sayuran'], true)) {
            return 'Sayuran';
        }

        return 'Lainnya';
    }
}
