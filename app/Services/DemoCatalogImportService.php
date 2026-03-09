<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class DemoCatalogImportService
{
    public function import(string $provider = 'dummyjson', int $limit = 30): array
    {
        return match (strtolower($provider)) {
            'dummyjson' => $this->importFromDummyJson(max(1, min($limit, 100))),
            default => throw new RuntimeException("Unsupported provider [{$provider}]."),
        };
    }

    private function importFromDummyJson(int $limit): array
    {
        $payload = Http::timeout(30)
            ->acceptJson()
            ->get('https://dummyjson.com/products', ['limit' => $limit])
            ->throw()
            ->json();

        $products = $payload['products'] ?? [];

        $guardName = config('auth.defaults.guard', 'web');
        Role::findOrCreate('vendor', $guardName);
        Role::findOrCreate('customer', $guardName);

        $vendor = User::firstOrCreate(
            ['email' => env('DEMO_VENDOR_EMAIL', 'demo-vendor@example.com')],
            [
                'name' => env('DEMO_VENDOR_NAME', 'Demo Vendor'),
                'phone' => null,
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        $vendor->syncRoles(['vendor']);

        $stats = [
            'categories' => 0,
            'products' => 0,
            'reviews' => 0,
            'images' => 0,
            'videos' => 0,
            'provider' => 'dummyjson',
        ];

        foreach ($products as $entry) {
            $categoryName = (string) ($entry['category'] ?? 'General');
            $categorySlug = Str::slug($categoryName);

            $category = Category::firstOrCreate(
                ['slug' => $categorySlug],
                [
                    'name' => Str::title(str_replace('-', ' ', $categoryName)),
                    'name_en' => Str::title(str_replace('-', ' ', $categoryName)),
                    'description' => "Imported category: {$categoryName}",
                    'description_en' => "Imported category: {$categoryName}",
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            if ($category->wasRecentlyCreated) {
                $stats['categories']++;
            }

            $title = trim((string) ($entry['title'] ?? 'Imported Product'));
            $description = trim((string) ($entry['description'] ?? 'Imported from dummy provider'));
            $sku = trim((string) ($entry['sku'] ?? 'SKU-' . ($entry['id'] ?? Str::uuid()->toString())));
            $rawPrice = (float) ($entry['price'] ?? 0);
            $discount = (float) ($entry['discountPercentage'] ?? 0);
            $oldPrice = $discount > 0 ? round($rawPrice * (1 + ($discount / 100)), 2) : null;

            $existingProduct = Product::where('sku', $sku)->first();
            $slug = $existingProduct?->slug ?? $this->resolveUniqueProductSlug(Str::slug($title));

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $title,
                    'name_en' => $title,
                    'slug' => $slug,
                    'description' => $description,
                    'description_en' => $description,
                    'price' => max($rawPrice, 0),
                    'old_price' => $oldPrice,
                    'cost_price' => $rawPrice > 0 ? round($rawPrice * 0.7, 2) : null,
                    'quantity' => (int) ($entry['stock'] ?? 0),
                    'is_active' => true,
                    'is_featured' => ($entry['rating'] ?? 0) >= 4.5,
                    'category_id' => $category->id,
                    'vendor_id' => $vendor->id,
                ]
            );

            if ($product->wasRecentlyCreated) {
                $stats['products']++;
            }

            $this->syncProductMediaFromEntry($product, $entry, $stats);

            foreach (($entry['reviews'] ?? []) as $reviewEntry) {
                $email = strtolower(trim((string) ($reviewEntry['reviewerEmail'] ?? '')));

                if ($email === '') {
                    continue;
                }

                $customer = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => (string) ($reviewEntry['reviewerName'] ?? Str::before($email, '@')),
                        'phone' => null,
                        'password' => Hash::make('password1234'),
                        'is_active' => true,
                    ]
                );

                if (! $customer->hasRole('vendor') && ! $customer->hasRole('admin')) {
                    $customer->syncRoles(['customer']);
                }

                $comment = trim((string) ($reviewEntry['comment'] ?? 'Imported review'));
                $rating = (int) round((float) ($reviewEntry['rating'] ?? 5));
                $rating = max(1, min(5, $rating));

                $review = Review::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'comment' => $comment,
                        'rating' => $rating,
                        'is_approved' => true,
                    ]
                );

                if ($review->wasRecentlyCreated) {
                    $stats['reviews']++;
                }
            }
        }

        return $stats;
    }

    private function syncProductMediaFromEntry(Product $product, array $entry, array &$stats): void
    {
        $imageUrls = [];

        $thumbnail = trim((string) ($entry['thumbnail'] ?? ''));
        if ($thumbnail !== '') {
            $imageUrls[] = $thumbnail;
        }

        foreach (($entry['images'] ?? []) as $url) {
            $url = trim((string) $url);
            if ($url !== '') {
                $imageUrls[] = $url;
            }
        }

        $imageUrls = array_values(array_unique($imageUrls));

        if (count($imageUrls) > 0) {
            $product->images()->delete();

            foreach ($imageUrls as $index => $url) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $url,
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);

                $stats['images']++;
            }
        }

        $videoUrls = [];
        foreach (($entry['videos'] ?? []) as $url) {
            $url = trim((string) $url);
            if ($url !== '') {
                $videoUrls[] = $url;
            }
        }

        $videoUrls = array_values(array_unique($videoUrls));

        // Optional fallback to make product media UI show at least one demo video.
        $fallbackVideoUrl = trim((string) env('DEMO_FALLBACK_VIDEO_URL', ''));
        if (count($videoUrls) === 0 && $fallbackVideoUrl !== '') {
            $videoUrls[] = $fallbackVideoUrl;
        }

        if (count($videoUrls) > 0) {
            $product->videos()->delete();

            foreach ($videoUrls as $index => $url) {
                ProductVideo::create([
                    'product_id' => $product->id,
                    'video' => $url,
                    'title' => $index === 0 ? 'Demo product video' : null,
                    'sort_order' => $index,
                ]);

                $stats['videos']++;
            }
        }
    }

    private function resolveUniqueProductSlug(string $baseSlug): string
    {
        $base = $baseSlug !== '' ? $baseSlug : 'imported-product';
        $slug = $base;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $counter++;
            $slug = $base . '-' . $counter;
        }

        return $slug;
    }
}
