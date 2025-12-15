<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/leaves",
     *     tags={"Leave Management"},
     *     summary="Get list of leave applications",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (0=Pending, 1=Approved, 2=Rejected)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        try {
            $leaves = Leave::with('employee')
                ->orderBy('apply_date', 'desc')
                ->get()
                ->map(function ($leave) {
                    return [
                        'id' => $leave->leave_appl_id,
                        'employee_id' => $leave->employee_id,
                        'employee_name' => $leave->employee_name,
                        'leave_type' => $leave->leave_type ?? 'General Leave',
                        'start_date' => $leave->apply_strt_date,
                        'end_date' => $leave->apply_end_date,
                        'days' => $leave->apply_day ?? 0,
                        'reason' => $leave->reason ?? '',
                        'status' => $leave->status,
                        'apply_date' => $leave->apply_date,
                        'approved_by' => $leave->approved_by,
                        'approve_date' => $leave->approve_date,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $leaves
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leaves',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/leaves/{id}",
     *     tags={"Leave Management"},
     *     summary="Get leave application by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show($id)
    {
        $leave = Leave::with('employee')->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $leave
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/leaves",
     *     tags={"Leave Management"},
     *     summary="Apply for leave",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","leave_type","start_date","end_date","reason"},
     *             @OA\Property(property="employee_id", type="integer", example=1),
     *             @OA\Property(property="leave_type", type="string", example="Sick Leave"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-12-20"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-12-22"),
     *             @OA\Property(property="reason", type="string", example="Medical appointment")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Leave applied successfully")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Calculate total days
        $startDate = new \DateTime($request->start_date);
        $endDate = new \DateTime($request->end_date);
        $totalDays = $endDate->diff($startDate)->days + 1;

        $leave = Leave::create([
            'employee_id' => $request->employee_id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'application_date' => now(),
            'status' => 0, // Pending
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave application submitted successfully',
            'data' => $leave
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/leaves/{id}/approve",
     *     tags={"Leave Management"},
     *     summary="Approve leave application",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Leave approved")
     * )
     */
    public function approve(Request $request, $id)
    {
        $leave = Leave::find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found'
            ], 404);
        }

        $leave->update([
            'status' => 1, // Approved
            'approved_by' => $request->user()->id,
            'approved_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave approved successfully',
            'data' => $leave
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/leaves/{id}/reject",
     *     tags={"Leave Management"},
     *     summary="Reject leave application",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Leave rejected")
     * )
     */
    public function reject(Request $request, $id)
    {
        $leave = Leave::find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found'
            ], 404);
        }

        $leave->update([
            'status' => 2, // Rejected
            'approved_by' => $request->user()->id,
            'approved_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave rejected',
            'data' => $leave
        ]);
    }
}

