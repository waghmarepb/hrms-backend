<?php

class PayrollController
{
    private $payrollModel;
    private $employeeModel;

    public function __construct()
    {
        $this->payrollModel = new Payroll();
        $this->employeeModel = new Employee();
    }

    public function index()
    {
        try {
            $filters = [];
            
            if (Request::has('employee_id')) {
                $filters['employee_id'] = Request::input('employee_id');
            }
            
            if (Request::has('month')) {
                $filters['month'] = Request::input('month');
            }
            
            if (Request::has('year')) {
                $filters['year'] = Request::input('year');
            }

            $payroll = $this->payrollModel->getAll($filters);

            Response::json([
                'success' => true,
                'data' => $payroll
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch payroll: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $payroll = $this->payrollModel->findById($id);

            if (!$payroll) {
                Response::notFound('Payroll record not found');
            }

            Response::json([
                'success' => true,
                'data' => $payroll
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch payroll: ' . $e->getMessage(), 500);
        }
    }

    public function generate()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'month' => 'required|integer',
                'year' => 'required|integer',
            ])->validate();

            $month = $data['month'];
            $year = $data['year'];
            $employeeIds = $data['employee_ids'] ?? null;

            // Get employees
            $employees = $this->employeeModel->getAll();
            
            // Filter by employee IDs if provided
            if ($employeeIds && is_array($employeeIds)) {
                $employees = array_filter($employees, function($emp) use ($employeeIds) {
                    return in_array($emp['employee_id'], $employeeIds);
                });
            }

            $generated = [];
            $errors = [];

            foreach ($employees as $employee) {
                // Check if payroll already exists
                $existing = $this->payrollModel->findExisting(
                    $employee['employee_id'], 
                    $month, 
                    $year
                );

                if ($existing) {
                    $errors[] = "Payroll already exists for employee " . $employee['full_name'];
                    continue;
                }

                // Calculate payroll
                $basicSalary = 50000; // Default salary - should come from employee record
                $payrollData = $this->payrollModel->calculatePayroll($basicSalary);

                $payrollData['employee_id'] = $employee['employee_id'];
                $payrollData['month'] = $month;
                $payrollData['year'] = $year;
                $payrollData['status'] = 'Generated';

                $payrollId = $this->payrollModel->create($payrollData);
                $generated[] = array_merge($payrollData, ['id' => $payrollId]);
            }

            Response::json([
                'success' => true,
                'message' => 'Payroll generated successfully',
                'data' => [
                    'generated' => count($generated),
                    'errors' => $errors,
                    'records' => $generated
                ]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to generate payroll: ' . $e->getMessage(), 500);
        }
    }

    public function markAsPaid($id)
    {
        try {
            $payroll = $this->payrollModel->findById($id);

            if (!$payroll) {
                Response::notFound('Payroll record not found');
            }

            $this->payrollModel->markAsPaid($id);

            Response::json([
                'success' => true,
                'message' => 'Payroll marked as paid'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to mark payroll as paid: ' . $e->getMessage(), 500);
        }
    }
}

