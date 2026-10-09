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
        $this->assertSame(25, substr_count($html, 'data-mode="Diet" data-recipe-source="Super Indo &amp; Hokky workbook recipes"'));
        $this->assertSame(25, substr_count($html, 'data-mode="Non-diet" data-recipe-source="Super Indo &amp; Hokky workbook recipes"'));
        $response->assertSee('Udang Segar Rebus dengan Kembang Kol')
            ->assertSee('Buntut Sapi Lumer Keju dengan Tepung Terigu Serbaguna')
            ->assertSee('347 kcal')
            ->assertSee('Karbohidrat: 20 g per porsi')
            ->assertSee('150g Udang Segar')
            ->assertSee('Panci / Teflon Anti Lengket')
            ->assertSee('Sajikan hangat dengan sedikit tetesan minyak wijen untuk aroma.')
            ->assertSee('Nilai gizi merupakan estimasi dari dokumen sumber.');
    }
}
