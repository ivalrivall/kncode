<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexWorkRequest;
use App\Http\Requests\Api\StoreWorkRequest;
use App\Http\Requests\Api\UpdateWorkRequest;
use App\Http\Resources\WorkResource;
use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class WorkController extends Controller
{
    /**
     * List open works for freelances to browse.
     * Public endpoint — no authentication required.
     */
    public function index(IndexWorkRequest $request): AnonymousResourceCollection
    {
        $query = Work::query()->where('status', 'open');

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by experience level
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->input('experience_level'));
        }

        // Filter by budget range
        if ($request->filled('budget_min')) {
            $query->where('budget_min', '>=', $request->input('budget_min'));
        }

        if ($request->filled('budget_max')) {
            $query->where('budget_max', '<=', $request->input('budget_max'));
        }

        // Filter by skills
        if ($request->filled('skills')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->whereIn('skills.id', $request->input('skills'));
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = (int) $request->input('per_page', 15);

        return WorkResource::collection(
            $query->with(['company', 'skills'])->paginate(max(1, min(100, $perPage)))
        );
    }

    /**
     * List works belonging to the authenticated company (any status).
     */
    public function companyIndex(IndexWorkRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can access this endpoint.',
            ], 403);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'message' => 'User does not have a company profile.',
            ], 403);
        }

        $query = Work::query()->where('company_id', $company->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by experience level
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->input('experience_level'));
        }

        // Filter by budget range
        if ($request->filled('budget_min')) {
            $query->where('budget_min', '>=', $request->input('budget_min'));
        }

        if ($request->filled('budget_max')) {
            $query->where('budget_max', '<=', $request->input('budget_max'));
        }

        // Filter by skills
        if ($request->filled('skills')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->whereIn('skills.id', $request->input('skills'));
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = (int) $request->input('per_page', 15);

        return WorkResource::collection(
            $query->with(['skills'])->paginate(max(1, min(100, $perPage)))
        );
    }

    /**
     * Store a newly created work (company only).
     */
    public function store(StoreWorkRequest $request): WorkResource|JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'message' => 'User does not have a company profile.',
            ], 403);
        }

        return DB::transaction(function () use ($request, $company) {
            /** @var Work $work */
            $work = $company->works()->create(array_merge(
                $request->validated(),
                ['status' => 'draft']
            ));

            if ($request->has('skills')) {
                $work->skills()->sync($request->input('skills'));
            }

            return new WorkResource($work->load('skills'));
        });
    }

    /**
     * Update an existing work (company only, must own the work).
     */
    public function update(UpdateWorkRequest $request, string $work_id): WorkResource|JsonResponse
    {
        $work = Work::find($work_id);

        if (!$work) {
            return response()->json([
                'message' => 'Work not found.',
            ], 404);
        }

        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'message' => 'User does not have a company profile.',
            ], 403);
        }

        if ($work->company_id !== $company->id) {
            return response()->json([
                'message' => 'You do not own this work.',
            ], 403);
        }

        return DB::transaction(function () use ($request, $work) {
            $work->update($request->validated());

            if ($request->has('skills')) {
                $work->skills()->sync($request->input('skills'));
            }

            return new WorkResource($work->load(['company', 'skills']));
        });
    }
}
