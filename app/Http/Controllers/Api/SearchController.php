<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function index(SearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = trim((string) $validated['q']);
        $baseLimit = (int) ($validated['limit'] ?? 12);
        $productsLimit = (int) ($validated['products_limit'] ?? $baseLimit);
        $categoriesLimit = (int) ($validated['categories_limit'] ?? $baseLimit);

        $normalizedQuery = $this->normalizeForSearch($query);
        $cacheKey = 'search:v3:' . md5($normalizedQuery . '|' . $productsLimit . '|' . $categoriesLimit);

        $payload = Cache::remember($cacheKey, now()->addSeconds(45), function () use ($query, $normalizedQuery, $productsLimit, $categoriesLimit) {
            $terms = $this->tokenize($query);

            $categories = Category::query()
                ->active()
                ->select(['id', 'name', 'name_en', 'slug', 'parent_id', 'image', 'sort_order'])
                ->where(function ($q) use ($terms, $query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%");

                    foreach ($terms as $term) {
                        $q->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('name_en', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%");
                    }
                })
                ->selectRaw(
                    "(CASE WHEN name = ? OR name_en = ? OR slug = ? THEN 100 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? OR slug LIKE ? THEN 60 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? OR slug LIKE ? THEN 25 ELSE 0 END)
                     AS relevance",
                    [
                        $query,
                        $query,
                        $query,
                        $query . '%',
                        $query . '%',
                        $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                    ]
                )
                ->orderByDesc('relevance')
                ->orderBy('sort_order')
                ->limit($categoriesLimit)
                ->get();

            $products = Product::query()
                ->active()
                ->with([
                    'category:id,name,name_en,slug,parent_id',
                    'primaryImage:id,product_id,image,is_primary,sort_order',
                ])
                ->select([
                    'id',
                    'name',
                    'name_en',
                    'slug',
                    'price',
                    'old_price',
                    'sku',
                    'product_type',
                    'category_id',
                    'is_featured',
                    'created_at',
                ])
                ->where(function ($q) use ($terms, $query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhere('description_en', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($query) {
                            $categoryQuery->where('name', 'like', "%{$query}%")
                                ->orWhere('name_en', 'like', "%{$query}%")
                                ->orWhere('slug', 'like', "%{$query}%");
                        });

                    foreach ($terms as $term) {
                        $q->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('name_en', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('description_en', 'like', "%{$term}%")
                            ->orWhere('sku', 'like', "%{$term}%")
                            ->orWhereHas('category', function ($categoryQuery) use ($term) {
                                $categoryQuery->where('name', 'like', "%{$term}%")
                                    ->orWhere('name_en', 'like', "%{$term}%")
                                    ->orWhere('slug', 'like', "%{$term}%");
                            });
                    }
                })
                ->selectRaw(
                    "(CASE WHEN sku = ? THEN 240 ELSE 0 END) +
                     (CASE WHEN sku LIKE ? THEN 140 ELSE 0 END) +
                     (CASE WHEN sku LIKE ? THEN 80 ELSE 0 END) +
                     (CASE WHEN name = ? OR name_en = ? THEN 180 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? THEN 100 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? THEN 50 ELSE 0 END) +
                     (CASE WHEN description LIKE ? OR description_en LIKE ? THEN 20 ELSE 0 END) +
                     (CASE WHEN EXISTS (
                        SELECT 1 FROM categories c
                        WHERE c.id = products.category_id
                          AND (c.name LIKE ? OR c.name_en LIKE ? OR c.slug LIKE ?)
                     ) THEN 35 ELSE 0 END)
                     AS relevance",
                    [
                        $query,
                        $query . '%',
                        '%' . $query . '%',
                        $query,
                        $query,
                        $query . '%',
                        $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                    ]
                )
                ->orderByDesc('relevance')
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit($productsLimit)
                ->get();

            if ($categories->count() < $categoriesLimit) {
                $missing = $categoriesLimit - $categories->count();
                $typoCategories = $this->fetchTypoCategories($normalizedQuery, $missing, $categories->pluck('id')->all());
                $categories = $categories->concat($typoCategories)->unique('id')->take($categoriesLimit)->values();
            }

            if ($products->count() < $productsLimit) {
                $missing = $productsLimit - $products->count();
                $typoProducts = $this->fetchTypoProducts($normalizedQuery, $missing, $products->pluck('id')->all());
                $products = $products->concat($typoProducts)->unique('id')->take($productsLimit)->values();
            }

            return [
                'query' => $query,
                'normalized_query' => $normalizedQuery,
                'categories' => $categories,
                'products' => $products,
                'total' => [
                    'categories' => $categories->count(),
                    'products' => $products->count(),
                ],
                'limits' => [
                    'categories_limit' => $categoriesLimit,
                    'products_limit' => $productsLimit,
                ],
            ];
        });

        return response()->json($payload);
    }

    public function suggestions(SearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = trim((string) $validated['q']);
        $baseLimit = min((int) ($validated['limit'] ?? 8), 15);
        $productsLimit = min((int) ($validated['products_limit'] ?? $baseLimit), 25);
        $categoriesLimit = min((int) ($validated['categories_limit'] ?? $baseLimit), 25);

        $normalizedQuery = $this->normalizeForSearch($query);
        $cacheKey = 'search:suggestions:v3:' . md5($normalizedQuery . '|' . $productsLimit . '|' . $categoriesLimit);

        $payload = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($query, $normalizedQuery, $productsLimit, $categoriesLimit) {
            $categorySuggestions = Category::query()
                ->active()
                ->select(['id', 'name', 'name_en', 'slug'])
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%");
                })
                ->selectRaw(
                    "(CASE WHEN name = ? OR name_en = ? OR slug = ? THEN 100 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? OR slug LIKE ? THEN 50 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? OR slug LIKE ? THEN 20 ELSE 0 END)
                     AS relevance",
                    [
                        $query,
                        $query,
                        $query,
                        $query . '%',
                        $query . '%',
                        $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                    ]
                )
                ->orderByDesc('relevance')
                ->orderBy('sort_order')
                ->limit($categoriesLimit)
                ->get()
                ->map(function (Category $category): array {
                    return [
                        'type' => 'category',
                        'id' => $category->id,
                        'slug' => $category->slug,
                        'label' => $category->name,
                        'label_en' => $category->name_en,
                    ];
                });

            if ($categorySuggestions->count() < $categoriesLimit) {
                $missing = $categoriesLimit - $categorySuggestions->count();
                $existingIds = $categorySuggestions->pluck('id')->all();

                $typoCategorySuggestions = $this->fetchTypoCategories($normalizedQuery, $missing, $existingIds)
                    ->map(function (Category $category): array {
                        return [
                            'type' => 'category',
                            'id' => $category->id,
                            'slug' => $category->slug,
                            'label' => $category->name,
                            'label_en' => $category->name_en,
                        ];
                    });

                $categorySuggestions = $categorySuggestions
                    ->concat($typoCategorySuggestions)
                    ->unique('id')
                    ->take($categoriesLimit)
                    ->values();
            }

            $productSuggestions = Product::query()
                ->active()
                ->select(['id', 'name', 'name_en', 'slug', 'sku'])
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                })
                ->selectRaw(
                    "(CASE WHEN sku = ? THEN 220 ELSE 0 END) +
                     (CASE WHEN sku LIKE ? THEN 130 ELSE 0 END) +
                     (CASE WHEN sku LIKE ? THEN 70 ELSE 0 END) +
                     (CASE WHEN name = ? OR name_en = ? THEN 160 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? THEN 90 ELSE 0 END) +
                     (CASE WHEN name LIKE ? OR name_en LIKE ? THEN 40 ELSE 0 END)
                     AS relevance",
                    [
                        $query,
                        $query . '%',
                        '%' . $query . '%',
                        $query,
                        $query,
                        $query . '%',
                        $query . '%',
                        '%' . $query . '%',
                        '%' . $query . '%',
                    ]
                )
                ->orderByDesc('relevance')
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit($productsLimit)
                ->get()
                ->map(function (Product $product): array {
                    return [
                        'type' => 'product',
                        'id' => $product->id,
                        'slug' => $product->slug,
                        'label' => $product->name,
                        'label_en' => $product->name_en,
                        'sku' => $product->sku,
                    ];
                });

            if ($productSuggestions->count() < $productsLimit) {
                $missing = $productsLimit - $productSuggestions->count();
                $existingIds = $productSuggestions->pluck('id')->all();

                $typoProductSuggestions = $this->fetchTypoProducts($normalizedQuery, $missing, $existingIds)
                    ->map(function (Product $product): array {
                        return [
                            'type' => 'product',
                            'id' => $product->id,
                            'slug' => $product->slug,
                            'label' => $product->name,
                            'label_en' => $product->name_en,
                            'sku' => $product->sku,
                        ];
                    });

                $productSuggestions = $productSuggestions
                    ->concat($typoProductSuggestions)
                    ->unique('id')
                    ->take($productsLimit)
                    ->values();
            }

            $items = $categorySuggestions
                ->concat($productSuggestions)
                ->take($categoriesLimit + $productsLimit)
                ->values();

            return [
                'query' => $query,
                'normalized_query' => $normalizedQuery,
                'items' => $items,
                'total' => $items->count(),
                'total_by_type' => [
                    'categories' => $categorySuggestions->count(),
                    'products' => $productSuggestions->count(),
                ],
                'limits' => [
                    'categories_limit' => $categoriesLimit,
                    'products_limit' => $productsLimit,
                ],
            ];
        });

        return response()->json($payload);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $query): array
    {
        $parts = preg_split('/\s+/u', trim($query)) ?: [];
        $parts = array_filter($parts, static fn (string $v): bool => $v !== '');

        return array_values(array_unique(array_slice($parts, 0, 6)));
    }

    private function normalizeForSearch(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        // Arabic normalization to improve matching with common keyboard/input variations.
        $value = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $value);
        $value = str_replace(['ى'], 'ي', $value);
        $value = str_replace(['ؤ'], 'و', $value);
        $value = str_replace(['ئ'], 'ي', $value);
        $value = str_replace(['ة'], 'ه', $value);

        // Remove Arabic diacritics and tatweel.
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value) ?? $value;

        // Keep letters/numbers/spaces only.
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param array<int, int> $excludeIds
     */
    private function fetchTypoCategories(string $normalizedQuery, int $limit, array $excludeIds = []): Collection
    {
        if ($normalizedQuery === '' || $limit <= 0) {
            return collect();
        }

        $pool = Category::query()
            ->active()
            ->select(['id', 'name', 'name_en', 'slug', 'parent_id', 'image', 'sort_order'])
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderBy('sort_order')
            ->limit(220)
            ->get();

        return $pool
            ->map(function (Category $category) use ($normalizedQuery): array {
                $score = $this->scoreTypoMatch($normalizedQuery, [
                    $category->name,
                    $category->name_en,
                    $category->slug,
                ]);

                return ['item' => $category, 'score' => $score];
            })
            ->filter(fn (array $row): bool => $row['score'] >= 62)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('item')
            ->values();
    }

    /**
     * @param array<int, int> $excludeIds
     */
    private function fetchTypoProducts(string $normalizedQuery, int $limit, array $excludeIds = []): Collection
    {
        if ($normalizedQuery === '' || $limit <= 0) {
            return collect();
        }

        $pool = Product::query()
            ->active()
            ->with([
                'category:id,name,name_en,slug,parent_id',
                'primaryImage:id,product_id,image,is_primary,sort_order',
            ])
            ->select([
                'id',
                'name',
                'name_en',
                'slug',
                'price',
                'old_price',
                'sku',
                'product_type',
                'category_id',
                'is_featured',
                'created_at',
            ])
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit(260)
            ->get();

        return $pool
            ->map(function (Product $product) use ($normalizedQuery): array {
                $categoryName = $product->category?->name;
                $categoryNameEn = $product->category?->name_en;

                $score = $this->scoreTypoMatch($normalizedQuery, [
                    $product->name,
                    $product->name_en,
                    $product->slug,
                    $product->sku,
                    $categoryName,
                    $categoryNameEn,
                ]);

                return ['item' => $product, 'score' => $score];
            })
            ->filter(fn (array $row): bool => $row['score'] >= 65)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('item')
            ->values();
    }

    /**
     * @param array<int, string|null> $candidates
     */
    private function scoreTypoMatch(string $normalizedQuery, array $candidates): int
    {
        $best = 0;

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $normalizedCandidate = $this->normalizeForSearch($candidate);

            if ($normalizedCandidate === '') {
                continue;
            }

            if ($normalizedCandidate === $normalizedQuery) {
                return 100;
            }

            if (str_starts_with($normalizedCandidate, $normalizedQuery)) {
                $best = max($best, 95);
                continue;
            }

            similar_text($normalizedQuery, $normalizedCandidate, $similarityPercent);

            $distance = levenshtein($normalizedQuery, $normalizedCandidate);
            $maxLen = max(mb_strlen($normalizedQuery, 'UTF-8'), mb_strlen($normalizedCandidate, 'UTF-8'));
            $distancePercent = $maxLen > 0 ? (1 - min($distance, $maxLen) / $maxLen) * 100 : 0;

            $score = (int) round(($similarityPercent * 0.55) + ($distancePercent * 0.45));
            $best = max($best, $score);
        }

        return $best;
    }
}
