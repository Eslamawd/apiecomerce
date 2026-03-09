<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $categories = Category::with('children')
            ->active()
            ->parents()
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        $categories = Category::with([
            'children' => fn ($query) => $query->with('children')->withCount('products')->orderBy('sort_order'),
        ])
            ->withCount('products')
            ->parents()
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::with(['children', 'products' => function ($query) {
            $query->active()->with(['primaryImage', 'images', 'videos']);
        }])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(new CategoryResource($category));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->ensureValidParentHierarchy($data['parent_id'] ?? null, null);

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileUploadService->upload($request->file('image'), 'categories/images');
        }

        if ($request->hasFile('video')) {
            $data['video'] = $this->fileUploadService->upload($request->file('video'), 'categories/videos');
        }

        $category = Category::create($data);

        return response()->json(new CategoryResource($category), 201);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $data = $request->validated();
        $this->ensureValidParentHierarchy($data['parent_id'] ?? null, $category->id);

        if ($request->hasFile('image')) {
            $this->fileUploadService->delete($this->getRawPath($category, 'image'));
            $data['image'] = $this->fileUploadService->upload($request->file('image'), 'categories/images');
        }

        if ($request->hasFile('video')) {
            $this->fileUploadService->delete($this->getRawPath($category, 'video'));
            $data['video'] = $this->fileUploadService->upload($request->file('video'), 'categories/videos');
        }

        if (isset($data['name_en'])) {
            $data['slug'] = Str::slug($data['name_en']);
        }

        $category->update($data);

        return response()->json(new CategoryResource($category->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->fileUploadService->delete($this->getRawPath($category, 'image'));
        $this->fileUploadService->delete($this->getRawPath($category, 'video'));

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }

    private function getRawPath(Category $category, string $field): ?string
    {
        return $category->getRawOriginal($field);
    }

    private function ensureValidParentHierarchy(?int $parentId, ?int $currentCategoryId): void
    {
        if (! $parentId) {
            return;
        }

        if ($currentCategoryId !== null && $parentId === $currentCategoryId) {
            throw ValidationException::withMessages([
                'parent_id' => ['Category cannot be its own parent.'],
            ]);
        }

        $cursor = Category::query()->select(['id', 'parent_id'])->find($parentId);

        while ($cursor) {
            if ($currentCategoryId !== null && (int) $cursor->id === (int) $currentCategoryId) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Invalid parent category. Circular hierarchy is not allowed.'],
                ]);
            }

            if (! $cursor->parent_id) {
                break;
            }

            $cursor = Category::query()->select(['id', 'parent_id'])->find($cursor->parent_id);
        }
    }
}
