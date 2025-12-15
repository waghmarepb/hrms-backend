<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Awards",
 *     description="Employee award management endpoints"
 * )
 */
class AwardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/awards",
     *     summary="Get all awards",
     *     tags={"Awards"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Start date filter",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="End date filter",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="List of awards")
     * )
     */
    public function index(Request $request)
    {
        $query = Award::with(['employee', 'awardedBy'])
            ->orderBy('award_id', 'DESC');

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->dateRange($request->from_date, $request->to_date);
        }

        // Search by award name
        if ($request->has('search')) {
            $query->byAwardName($request->search);
        }

        $awards = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $awards
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/awards/{id}",
     *     summary="Get award details",
     *     tags={"Awards"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Award details"),
     *     @OA\Response(response=404, description="Award not found")
     * )
     */
    public function show($id)
    {
        $award = Award::with(['employee', 'awardedBy'])->find($id);

        if (!$award) {
            return response()->json([
                'success' => false,
                'message' => 'Award not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $award
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/awards",
     *     summary="Create award",
     *     tags={"Awards"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"award_name","employee_id","date"},
     *             @OA\Property(property="award_name", type="string", example="Employee of the Month"),
     *             @OA\Property(property="aw_description", type="string", example="For outstanding performance"),
     *             @OA\Property(property="awr_gift_item", type="string", example="Certificate and Gift Voucher"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="employee_id", type="string", example="EMP001"),
     *             @OA\Property(property="awarded_by", type="string", example="EMP002")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Award created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'award_name' => 'required|string|max:255',
            'aw_description' => 'nullable|string',
            'awr_gift_item' => 'nullable|string|max:50',
            'date' => 'required|date',
            'employee_id' => 'required|string|exists:employee_history,employee_id',
            'awarded_by' => 'nullable|string|exists:employee_history,employee_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $award = Award::create([
            'award_name' => $request->award_name,
            'aw_description' => $request->aw_description ?? '',
            'awr_gift_item' => $request->awr_gift_item ?? '',
            'date' => $request->date,
            'employee_id' => $request->employee_id,
            'awarded_by' => $request->awarded_by ?? ''
        ]);

        $award->load(['employee', 'awardedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Award created successfully',
            'data' => $award
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/awards/{id}",
     *     summary="Update award",
     *     tags={"Awards"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="award_name", type="string"),
     *             @OA\Property(property="aw_description", type="string"),
     *             @OA\Property(property="awr_gift_item", type="string"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="employee_id", type="string"),
     *             @OA\Property(property="awarded_by", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Award updated"),
     *     @OA\Response(response=404, description="Award not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $award = Award::find($id);

        if (!$award) {
            return response()->json([
                'success' => false,
                'message' => 'Award not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'award_name' => 'required|string|max:255',
            'aw_description' => 'nullable|string',
            'awr_gift_item' => 'nullable|string|max:50',
            'date' => 'required|date',
            'employee_id' => 'required|string|exists:employee_history,employee_id',
            'awarded_by' => 'nullable|string|exists:employee_history,employee_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $award->update([
            'award_name' => $request->award_name,
            'aw_description' => $request->aw_description ?? '',
            'awr_gift_item' => $request->awr_gift_item ?? '',
            'date' => $request->date,
            'employee_id' => $request->employee_id,
            'awarded_by' => $request->awarded_by ?? ''
        ]);

        $award->load(['employee', 'awardedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Award updated successfully',
            'data' => $award
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/awards/{id}",
     *     summary="Delete award",
     *     tags={"Awards"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Award deleted"),
     *     @OA\Response(response=404, description="Award not found")
     * )
     */
    public function destroy($id)
    {
        $award = Award::find($id);

        if (!$award) {
            return response()->json([
                'success' => false,
                'message' => 'Award not found'
            ], 404);
        }

        $award->delete();

        return response()->json([
            'success' => true,
            'message' => 'Award deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/awards/employee/{employeeId}",
     *     summary="Get employee awards",
     *     tags={"Awards"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employeeId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Employee's awards")
     * )
     */
    public function employeeAwards($employeeId)
    {
        $awards = Award::with(['employee', 'awardedBy'])
            ->where('employee_id', $employeeId)
            ->orderBy('date', 'DESC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $awards
        ]);
    }
}

