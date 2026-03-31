<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::forUser($request->user()->id);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $categories = $query->orderBy('name')->get();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
            'icon' => ['sometimes', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'max:7'],
        ]);

        $category = Category::create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'is_system' => false,
        ]));

        return $this->successResponse(new CategoryResource($category), 'Category created successfully', 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        if ($category->is_system || $category->user_id !== $request->user()->id) {
            return $this->errorResponse('Cannot modify system categories', 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $category->update($validated);

        return $this->successResponse(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        if ($category->is_system || $category->user_id !== $request->user()->id) {
            return $this->errorResponse('Cannot delete system categories', 403);
        }

        if ($category->transactions()->exists()) {
            $other = Category::where('is_system', true)
                ->where('type', $category->type)
                ->where('name', 'like', '%Other%')
                ->first();

            if ($other) {
                $category->transactions()->update(['category_id' => $other->id]);
            }
        }

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }
}
