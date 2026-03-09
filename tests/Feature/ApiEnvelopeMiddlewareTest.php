<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiEnvelopeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_show_response_is_wrapped_by_standard_envelope(): void
    {
        $category = Category::create([
            'name' => 'Electronics',
            'name_en' => 'Electronics EN',
            'slug' => Str::slug('electronics-'.Str::random(5)),
            'description' => 'Category description',
            'description_en' => 'Category description EN',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/categories/'.$category->slug);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.slug', $category->slug);
    }
}
