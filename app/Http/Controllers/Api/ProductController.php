<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductImageResource;
use App\Http\Resources\ProductResource;
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
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
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
            $next = $product->images()->first();
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
}
