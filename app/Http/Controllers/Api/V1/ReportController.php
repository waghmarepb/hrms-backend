<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Leave;
use App\Models\Attendance;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/reports/dashboard",
     *     tags={"Reports"},
     *     summary="Get dashboard statistics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function dashboard()
    {
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 1)->count(),
            'total_departments' => Department::count(),
            'pending_leaves' => Leave::where('status', 0)->count(),
            'today_attendance' => Attendance::whereDate('date', today())->count(),
            'this_month_payroll' => Payroll::where('month', now()->month)
                                          ->where('year', now()->year)
                                          ->sum('net_salary'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/employees",
     *     tags={"Reports"},
     *     summary="Employee analytics report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function employeeReport()
    {
        $byDepartment = Employee::selectRaw('department_id, count(*) as count')
                                ->groupBy('department_id')
                                ->with('department')
                                ->get();

        $byDesignation = Employee::selectRaw('designation, count(*) as count')
                                 ->whereNotNull('designation')
                                 ->groupBy('designation')
                                 ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'by_department' => $byDepartment,
                'by_designation' => $byDesignation,
                'total' => Employee::count(),
                'active' => Employee::where('status', 1)->count(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/attendance",
     *     tags={"Reports"},
     *     summary="Attendance report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
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
    public function attendanceReport(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $attendance = Attendance::whereMonth('date', $month)
                                ->whereYear('date', $year)
                                ->selectRaw('employee_id, count(*) as days, sum(work_hours) as total_hours')
                                ->groupBy('employee_id')
                                ->with('employee')
                                ->get();

        $summary = [
            'total_records' => Attendance::whereMonth('date', $month)
                                        ->whereYear('date', $year)
                                        ->count(),
            'unique_employees' => $attendance->count(),
            'average_hours' => $attendance->avg('total_hours'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'records' => $attendance
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/leave",
     *     tags={"Reports"},
     *     summary="Leave report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function leaveReport(Request $request)
    {
        $year = $request->get('year', now()->year);

        $byType = Leave::whereYear('application_date', $year)
                      ->selectRaw('leave_type, count(*) as count, sum(total_days) as total_days')
                      ->groupBy('leave_type')
                      ->get();

        $byStatus = Leave::whereYear('application_date', $year)
                        ->selectRaw('status, count(*) as count')
                        ->groupBy('status')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'by_type' => $byType,
                'by_status' => $byStatus,
                'total_applications' => Leave::whereYear('application_date', $year)->count(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/payroll",
     *     tags={"Reports"},
     *     summary="Payroll report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function payrollReport(Request $request)
    {
        $year = $request->get('year', now()->year);

        $byMonth = Payroll::where('year', $year)
                         ->selectRaw('month, sum(net_salary) as total, count(*) as count')
                         ->groupBy('month')
                         ->orderBy('month')
                         ->get();

        $summary = [
            'total_paid' => Payroll::where('year', $year)
                                  ->where('status', 'Paid')
                                  ->sum('net_salary'),
            'total_pending' => Payroll::where('year', $year)
                                     ->where('status', '!=', 'Paid')
                                     ->sum('net_salary'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'by_month' => $byMonth
            ]
        ]);
    }
}



