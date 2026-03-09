<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_products_list_returns_only_authenticated_vendor_products_with_envelope(): void
    {
        $vendor = $this->createVendorUser('vendor1@example.com');
        $otherVendor = $this->createVendorUser('vendor2@example.com');
        $category = $this->createCategory('Phones');

        $ownA = $this->createProduct($vendor, $category, 'A');
        $ownB = $this->createProduct($vendor, $category, 'B');
        $this->createProduct($otherVendor, $category, 'X');

        Sanctum::actingAs($vendor);

        $response = $this->getJson('/api/vendor/products');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data',
                    'links',
                    'meta',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.meta.last_page', 1)
            ->assertJsonPath('data.meta.per_page', 15)
            ->assertJsonPath('data.meta.total', 2);

        $items = collect($response->json('data.data'));
        $productIds = $items->pluck('id')->all();
        $vendorIds = $items->pluck('vendor.id')->unique()->values()->all();

        $this->assertContains($ownA->id, $productIds);
        $this->assertContains($ownB->id, $productIds);
        $this->assertCount(2, $productIds);
        $this->assertSame([$vendor->id], $vendorIds);
    }

    public function test_vendor_products_requires_authentication_and_returns_standard_envelope(): void
    {
        $response = $this->getJson('/api/vendor/products');

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.')
            ->assertJsonPath('data', null);
    }

    private function createVendorUser(string $email): User
    {
        $user = User::factory()->create([
            'name' => 'Vendor '.Str::before($email, '@'),
            'email' => $email,
        ]);

        Role::findOrCreate('vendor', 'web');
        $user->assignRole('vendor');

        return $user;
    }

    private function createCategory(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'name_en' => $name.' EN',
            'slug' => Str::slug($name.'-'.Str::random(5)),
            'description' => 'Category description',
            'description_en' => 'Category description EN',
            'is_active' => true,
        ]);
    }

    private function createProduct(User $vendor, Category $category, string $suffix): Product
    {
        return Product::create([
            'name' => 'Product '.$suffix,
            'name_en' => 'Product EN '.$suffix,
            'description' => 'Description '.$suffix,
            'description_en' => 'Description EN '.$suffix,
            'price' => 99.99,
            'old_price' => 120.00,
            'cost_price' => 50.00,
            'sku' => 'SKU-'.$suffix.'-'.Str::random(5),
            'quantity' => 10,
            'is_active' => true,
            'is_featured' => false,
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'slug' => Str::slug('product-'.$suffix.'-'.Str::random(5)),
        ]);
    }
}
