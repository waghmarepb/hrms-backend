<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        try {
            $employees = Employee::with(['department', 'position'])
                ->orderBy('first_name', 'asc')
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->emp_his_id,
                        'employee_id' => $employee->employee_id,
                        'full_name' => $employee->full_name,
                        'fullName' => $employee->full_name,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'designation' => $employee->position->position_name ?? 'Employee',
                        'department_name' => $employee->department->department_name ?? 'No Department',
                        'departmentName' => $employee->department->department_name ?? 'No Department',
                        'hire_date' => $employee->hire_date,
                        'status' => $employee->status,
                        'picture' => $employee->picture,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employees',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $employee = Employee::with(['department', 'position'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $employee->emp_his_id,
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->full_name,
                    'fullName' => $employee->full_name,
                    'first_name' => $employee->first_name,
                    'middle_name' => $employee->middle_name,
                    'last_name' => $employee->last_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'alter_phone' => $employee->alter_phone,
                    'designation' => $employee->position->position_name ?? 'Employee',
                    'department_name' => $employee->department->department_name ?? 'No Department',
                    'departmentName' => $employee->department->department_name ?? 'No Department',
                    'dept_id' => $employee->dept_id,
                    'pos_id' => $employee->pos_id,
                    'present_address' => $employee->present_address,
                    'permanent_address' => $employee->parmanent_address,
                    'hire_date' => $employee->hire_date,
                    'dob' => $employee->dob,
                    'gender' => $employee->gender,
                    'marital_status' => $employee->marital_status,
                    'status' => $employee->status,
                    'picture' => $employee->picture,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee',
            ], 500);
        }
    }
}
