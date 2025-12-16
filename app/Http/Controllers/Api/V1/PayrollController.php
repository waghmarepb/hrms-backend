<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/payroll",
     *     tags={"Payroll"},
     *     summary="Get payroll records",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
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
    public function index(Request $request)
    {
        $query = Payroll::with('employee');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $payroll = $query->orderBy('year', 'desc')
                         ->orderBy('month', 'desc')
                         ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payroll
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payroll/{id}",
     *     tags={"Payroll"},
     *     summary="Get payroll by ID",
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
        $payroll = Payroll::with('employee')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll record not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payroll
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payroll/generate",
     *     tags={"Payroll"},
     *     summary="Generate payroll for month",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"month","year"},
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="year", type="integer", example=2024),
     *             @OA\Property(property="employee_ids", type="array", @OA\Items(type="integer"), example={1,2,3})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payroll generated")
     * )
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'employee_ids' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $month = $request->month;
        $year = $request->year;
        
        // Get employees to generate payroll for
        $query = Employee::query();
        if ($request->has('employee_ids')) {
            $query->whereIn('employee_id', $request->employee_ids);
        }
        $employees = $query->get();

        $generated = [];
        $errors = [];

        foreach ($employees as $employee) {
            // Check if payroll already exists
            $existing = Payroll::where('employee_id', $employee->employee_id)
                              ->where('month', $month)
                              ->where('year', $year)
                              ->first();

            if ($existing) {
                $errors[] = "Payroll already exists for employee {$employee->full_name}";
                continue;
            }

            // Calculate payroll (simplified - you can add complex logic here)
            $basicSalary = $employee->salary ?? 0;
            $allowances = $basicSalary * 0.1; // 10% allowances
            $deductions = $basicSalary * 0.05; // 5% deductions
            $tax = $basicSalary * 0.1; // 10% tax
            $netSalary = $basicSalary + $allowances - $deductions - $tax;

            $payroll = Payroll::create([
                'employee_id' => $employee->employee_id,
                'month' => $month,
                'year' => $year,
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'tax' => $tax,
                'net_salary' => $netSalary,
                'status' => 'Generated',
            ]);

            $generated[] = $payroll;
        }

        return response()->json([
            'success' => true,
            'message' => 'Payroll generated successfully',
            'data' => [
                'generated' => count($generated),
                'errors' => $errors,
                'records' => $generated
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/payroll/{id}/pay",
     *     tags={"Payroll"},
     *     summary="Mark payroll as paid",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Marked as paid")
     * )
     */
    public function markAsPaid($id)
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll record not found'
            ], 404);
        }

        $payroll->update([
            'status' => 'Paid',
            'payment_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payroll marked as paid',
            'data' => $payroll
        ]);
    }
}



