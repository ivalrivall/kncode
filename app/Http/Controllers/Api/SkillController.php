<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SkillController extends Controller
{
    /**
     * Get a list of skills with pagination, filtering, and sorting.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Skill::query();

        // Filtering by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSortFields = ['id', 'name', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 15);
        $skills = $query->paginate(max(1, min(100, $perPage)));

        return SkillResource::collection($skills);
    }
}
