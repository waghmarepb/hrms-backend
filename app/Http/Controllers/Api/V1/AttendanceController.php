<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/attendance",
     *     tags={"Attendance"},
     *     summary="Get attendance records",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter by date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="From date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="To date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        $query = Attendance::with('employee');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        }

        $attendance = $query->orderBy('date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/attendance/clock-in",
     *     tags={"Attendance"},
     *     summary="Clock in",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id"},
     *             @OA\Property(property="employee_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Clocked in successfully")
     * )
     */
    public function clockIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if already clocked in today
        $today = Carbon::now()->format('Y-m-d');
        $existing = Attendance::where('employee_id', $request->employee_id)
                              ->whereDate('date', $today)
                              ->whereNull('time_out')
                              ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Already clocked in today'
            ], 400);
        }

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $today,
            'time_in' => Carbon::now()->format('H:i:s'),
            'status' => 'Present',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked in successfully',
            'data' => $attendance
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/attendance/clock-out",
     *     tags={"Attendance"},
     *     summary="Clock out",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id"},
     *             @OA\Property(property="employee_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Clocked out successfully")
     * )
     */
    public function clockOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $today = Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('employee_id', $request->employee_id)
                                ->whereDate('date', $today)
                                ->whereNull('time_out')
                                ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No clock-in record found for today'
            ], 404);
        }

        $timeIn = Carbon::parse($attendance->date . ' ' . $attendance->time_in);
        $timeOut = Carbon::now();
        $workHours = $timeIn->diffInHours($timeOut, true);

        $attendance->update([
            'time_out' => $timeOut->format('H:i:s'),
            'work_hours' => round($workHours, 2),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked out successfully',
            'data' => $attendance
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/attendance/report",
     *     tags={"Attendance"},
     *     summary="Get attendance report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month (1-12)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function report(Request $request)
    {
        $employeeId = $request->employee_id;
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->whereMonth('date', $month)
                                ->whereYear('date', $year)
                                ->orderBy('date')
                                ->get();

        $summary = [
            'total_days' => $attendance->count(),
            'present_days' => $attendance->where('status', 'Present')->count(),
            'absent_days' => $attendance->where('status', 'Absent')->count(),
            'total_hours' => $attendance->sum('work_hours'),
            'average_hours' => $attendance->avg('work_hours'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'records' => $attendance
            ]
        ]);
    }
}



