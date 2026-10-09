<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_library_includes_all_workbook_recipes_with_complete_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/recipes')->assertOk();
        $html = $response->getContent();

        $this->assertSame(50, substr_count($html, 'data-recipe-source="Super Indo &amp; Hokky workbook recipes"'));
        $this->assertSame(
            25,
            preg_match_all(
                '/data-mode="Diet"\s+data-recipe-source="Super Indo &amp; Hokky workbook recipes"/',
                $html,
            ),
        );
        $this->assertSame(
            25,
            preg_match_all(
                '/data-mode="Non-diet"\s+data-recipe-source="Super Indo &amp; Hokky workbook recipes"/',
                $html,
            ),
        );

        $visibleText = preg_replace(
            '/\s+/',
            ' ',
            html_entity_decode(strip_tags($html)),
        );

        foreach ([
            'Udang Segar Rebus dengan Kembang Kol',
            'Buntut Sapi Lumer Keju dengan Tepung Terigu Serbaguna',
            '347 kcal',
            'Karbohidrat: 20 g per porsi',
            '150g Udang Segar',
            'Panci / Teflon Anti Lengket',
            'Sajikan hangat dengan sedikit tetesan minyak wijen untuk aroma.',
            'Nilai gizi merupakan estimasi dari dokumen sumber.',
        ] as $text) {
            $this->assertStringContainsString($text, $visibleText);
        }
    }
}
