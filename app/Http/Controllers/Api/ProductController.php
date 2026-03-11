<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductImageResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'primaryImage', 'vendor'])
            ->active();

        if ($request->filled('category')) {
            $selectedCategory = Category::query()
                ->where('slug', $request->category)
                ->first();

            if ($selectedCategory) {
                $query->whereIn(
                    'category_id',
                    $this->getCategoryAndDescendantIds($selectedCategory)
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $terms = $this->tokenizeSearch($search);

            $query->where(function ($q) use ($search, $terms) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('description_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
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
            });
        }

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        $variantAttributeFilters = [
            'color' => $request->get('color'),
            'size' => $request->get('size'),
            'make' => $request->get('make'),
            'model' => $request->get('model'),
            'year' => $request->get('year'),
        ];

        foreach ($variantAttributeFilters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query->whereRaw(
                "JSON_SEARCH(variants, 'one', ?, NULL, '$[*].attributes.{$key}') IS NOT NULL",
                [(string) $value]
            );
        }

        if ($request->filled('spec_key') && $request->filled('spec_value')) {
            $specKey = (string) $request->spec_key;
            $specValue = (string) $request->spec_value;

            $query->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(specifications, ?)) = ?",
                ["$.{$specKey}", $specValue]
            );
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }

        $sortBy = in_array($request->sort_by, ['price', 'name', 'created_at']) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        $query->orderBy('id', 'desc');

        $perPage = min((int) $request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['category', 'images', 'videos', 'primaryImage', 'vendor'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(new ProductResource($product));
    }

    public function vendorIndex(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'images', 'videos', 'primaryImage', 'vendor'])
            ->where('vendor_id', $request->user()->id);

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $sortBy = in_array($request->sort_by, ['price', 'name', 'created_at', 'quantity']) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        $query->orderBy('id', 'desc');

        $perPage = min((int) $request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['vendor_id'] = $request->user()->id;

        $product = Product::create($data);

        if ($request->hasFile('images')) {
            $first = true;
            foreach ($request->file('images') as $index => $imageFile) {
                $path = $this->fileUploadService->upload($imageFile, 'products/images');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image'      => $path,
                    'sort_order' => $index,
                    'is_primary' => $first,
                ]);
                $first = false;
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $index => $videoFile) {
                $path = $this->fileUploadService->upload($videoFile, 'products/videos');
                ProductVideo::create([
                    'product_id' => $product->id,
                    'video'      => $path,
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json(
            new ProductResource($product->load(['category', 'images', 'videos', 'primaryImage', 'vendor'])),
            201
        );
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $data = $request->validated();

        if (isset($data['name_en'])) {
            $data['slug'] = Str::slug($data['name_en']);
        }

        $product->update($data);

        if ($request->hasFile('images')) {
            $currentCount = $product->images()->count();
            $hasPrimary = $product->images()->where('is_primary', true)->exists();

            foreach ($request->file('images') as $index => $imageFile) {
                $path = $this->fileUploadService->upload($imageFile, 'products/images');
                $isPrimary = ! $hasPrimary && $index === 0;

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                    'sort_order' => $currentCount + $index,
                    'is_primary' => $isPrimary,
                ]);

                if ($isPrimary) {
                    $hasPrimary = true;
                }
            }
        }

        if ($request->hasFile('videos')) {
            $currentCount = $product->videos()->count();

            foreach ($request->file('videos') as $index => $videoFile) {
                $path = $this->fileUploadService->upload($videoFile, 'products/videos');

                ProductVideo::create([
                    'product_id' => $product->id,
                    'video' => $path,
                    'sort_order' => $currentCount + $index,
                ]);
            }
        }

        return response()->json(
            new ProductResource($product->fresh()->load(['category', 'images', 'videos', 'primaryImage', 'vendor']))
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function addImages(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $newImages = [];
        $currentCount = $product->images()->count();
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images') as $index => $imageFile) {
            $path = $this->fileUploadService->upload($imageFile, 'products/images');
            $isPrimary = !$hasPrimary && $index === 0;
            $image = ProductImage::create([
                'product_id' => $product->id,
                'image'      => $path,
                'sort_order' => $currentCount + $index,
                'is_primary' => $isPrimary,
            ]);
            if ($isPrimary) {
                $hasPrimary = true;
            }
            $newImages[] = $image;
        }

        return response()->json(ProductImageResource::collection(collect($newImages)), 201);
    }

    public function deleteImage(Request $request, int $id, int $imageId): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $image = ProductImage::where('product_id', $product->id)->findOrFail($imageId);

        $this->fileUploadService->delete($image->getRawOriginal('image'));
        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $next = ProductImage::where('product_id', $product->id)->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return response()->json(['message' => 'Image deleted successfully']);
    }

    public function addVideos(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $request->validate([
            'videos'   => 'required|array|max:5',
            'videos.*' => 'file|mimes:mp4,mov,avi|max:51200',
        ]);

        $newVideos = [];
        $currentCount = $product->videos()->count();

        foreach ($request->file('videos') as $index => $videoFile) {
            $path = $this->fileUploadService->upload($videoFile, 'products/videos');
            $video = ProductVideo::create([
                'product_id' => $product->id,
                'video'      => $path,
                'title'      => $request->input("titles.{$index}"),
                'sort_order' => $currentCount + $index,
            ]);
            $newVideos[] = $video;
        }

        return response()->json(\App\Http\Resources\ProductVideoResource::collection(collect($newVideos)), 201);
    }

    public function deleteVideo(Request $request, int $id, int $videoId): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $video = ProductVideo::where('product_id', $product->id)->findOrFail($videoId);

        $this->fileUploadService->delete($video->getRawOriginal('video'));
        $video->delete();

        return response()->json(['message' => 'Video deleted successfully']);
    }

    public function setPrimaryImage(Request $request, int $id, int $imageId): JsonResponse
    {
        $product = Product::findOrFail($id);

        $this->authorizeProductAccess($request, $product);

        $image = ProductImage::where('product_id', $product->id)->findOrFail($imageId);

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json(new ProductImageResource($image->fresh()));
    }

    private function authorizeProductAccess(Request $request, Product $product): void
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ((int) $product->vendor_id !== (int) $user->id) {
            abort(403, 'Unauthorized. You do not own this product.');
        }
    }

    private function getCategoryAndDescendantIds(Category $category): array
    {
        $childrenIds = Category::query()
            ->where('parent_id', $category->id)
            ->pluck('id')
            ->all();

        $allIds = [$category->id];

        foreach ($childrenIds as $childId) {
            $child = Category::query()->find($childId);
            if (! $child) {
                continue;
            }

            $allIds = array_merge($allIds, $this->getCategoryAndDescendantIds($child));
        }

        return array_values(array_unique($allIds));
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeSearch(string $value): array
    {
        $parts = preg_split('/\s+/u', trim($value)) ?: [];
        $parts = array_filter($parts, static fn (string $v): bool => $v !== '');

        return array_values(array_unique(array_slice($parts, 0, 6)));
    }
}
