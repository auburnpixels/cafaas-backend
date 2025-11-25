<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operator\ComplianceSummaryRequest;
use App\Http\Requests\Api\V1\Operator\StoreComplaintRequest;
use App\Http\Resources\Api\V1\ComplaintResource;
use App\Models\Competition;
use App\Models\Complaint;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for operator compliance and complaints
 */
final class ComplianceController extends Controller
{
    public function __construct(
        private readonly ComplianceService $complianceService
    ) {}

    /**
     * Get operator compliance summary.
     */
    public function summary(ComplianceSummaryRequest $request): JsonResponse
    {
        $operator = $request->operator;

        $compliance = $this->complianceService->getOperatorCompliance($operator);

        return response()->json([
            'compliance' => $compliance,
        ]);
    }

    /**
     * Submit a complaint.
     */
    public function storeComplaint(StoreComplaintRequest $request): JsonResponse
    {
        $operator = $request->operator;

        $validated = $request->validated();

        // Find competition
        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $validated['competition_external_id'])
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        // Create complaint
        $complaint = Complaint::create([
            'competition_id' => $competition->id,
            'operator_id' => $operator->id,
            'category' => $validated['category'],
            'message' => $validated['description'],
            'status' => 'pending',
        ]);

        return (new ComplaintResource($complaint))
            ->additional(['competition_id' => $validated['competition_external_id']])
            ->response()
            ->setStatusCode(201);
    }
}
