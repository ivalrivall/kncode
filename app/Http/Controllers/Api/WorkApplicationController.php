<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexWorkApplicationRequest;
use App\Http\Requests\Api\StoreWorkApplicationRequest;
use App\Http\Resources\WorkApplicationResource;
use App\Models\Work;
use App\Models\WorkApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class WorkApplicationController extends Controller
{
    public function index(IndexWorkApplicationRequest $request, string $work_id): AnonymousResourceCollection|JsonResponse
    {
        $work = $this->authorizeWorkOwnership($request, $work_id);

        if ($work instanceof JsonResponse) {
            return $work;
        }

        $query = WorkApplication::where('work_id', $work->id)
            ->with(['freelance.skills', 'freelance.portfolios']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $applications = $query->paginate($request->input('per_page', 15));

        return WorkApplicationResource::collection($applications);
    }

    public function show(Request $request, string $work_id, string $application_id): WorkApplicationResource|JsonResponse
    {
        $work = $this->authorizeWorkOwnership($request, $work_id);

        if ($work instanceof JsonResponse) {
            return $work;
        }

        $application = WorkApplication::where('id', $application_id)
            ->where('work_id', $work->id)
            ->with(['freelance.skills', 'freelance.portfolios'])
            ->first();

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        return new WorkApplicationResource($application);
    }

    public function store(StoreWorkApplicationRequest $request, string $work_id): WorkApplicationResource|JsonResponse
    {
        $work = Work::find($work_id);

        if (!$work) {
            return response()->json([
                'message' => 'Work not found.',
            ], 404);
        }

        $user = $request->user();
        $freelance = $user->freelance;

        if (!$freelance) {
            return response()->json([
                'message' => 'User does not have a freelancer profile.',
            ], 403);
        }

        if ($work->status !== 'open') {
            return response()->json([
                'message' => 'This work is not open for applications.',
            ], 422);
        }

        $existingApplication = WorkApplication::where('work_id', $work->id)
            ->where('freelancer_id', $freelance->id)
            ->exists();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied to this work.',
            ], 409);
        }

        $application = $work->applications()->create([
            ...$request->validated(),
            'freelancer_id' => $freelance->id,
            'status' => 'pending',
        ]);

        return new WorkApplicationResource($application->load('work'));
    }

    private function authorizeWorkOwnership(Request $request, string $work_id): Work|JsonResponse
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
                'message' => 'You do not have a company profile.',
            ], 403);
        }

        if ($work->company_id !== $company->id) {
            return response()->json([
                'message' => 'You do not own this work.',
            ], 403);
        }

        return $work;
    }
}
