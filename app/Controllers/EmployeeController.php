<?php

class EmployeeController
{
    private $employeeModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees",
     *     tags={"Employees"},
     *     summary="Get all employees",
     *     description="Retrieve a list of all employees",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Employees retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@hrms.com"),
     *                     @OA\Property(property="phone", type="string", example="+1234567890"),
     *                     @OA\Property(property="dept_id", type="integer", example=1),
     *                     @OA\Property(property="pos_id", type="integer", example=1),
     *                     @OA\Property(property="hire_date", type="string", format="date", example="2024-01-15")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        try {
            $employees = $this->employeeModel->getAll();

            Response::json([
                'success' => true,
                'data' => $employees
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch employees: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees/{id}",
     *     tags={"Employees"},
     *     summary="Get employee by ID",
     *     description="Retrieve detailed information about a specific employee",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Employee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@hrms.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found"
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $employee = $this->employeeModel->findById($id);

            if (!$employee) {
                Response::notFound('Employee not found');
            }

            $formatted = $this->employeeModel->formatDetailedEmployee($employee);

            Response::json([
                'success' => true,
                'data' => $formatted
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/employees",
     *     tags={"Employees"},
     *     summary="Create new employee",
     *     description="Add a new employee to the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "phone", "dept_id", "pos_id", "hire_date"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@hrms.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="dept_id", type="integer", example=1),
     *             @OA\Property(property="pos_id", type="integer", example=1),
     *             @OA\Property(property="hire_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="salary", type="number", format="float", example=50000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Employee created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employee created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store()
    {
        try {
            $data = Request::all();

            // Validation
            Validator::make($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required',
                'dept_id' => 'required|integer',
                'pos_id' => 'required|integer',
                'hire_date' => 'required|date',
            ])->validate();

            $employeeId = $this->employeeModel->create($data);

            Response::json([
                'success' => true,
                'message' => 'Employee created successfully',
                'data' => ['id' => $employeeId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/employees/{id}",
     *     tags={"Employees"},
     *     summary="Update employee",
     *     description="Update employee information",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Employee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@hrms.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="dept_id", type="integer", example=1),
     *             @OA\Property(property="pos_id", type="integer", example=1),
     *             @OA\Property(property="salary", type="number", format="float", example=55000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employee updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found"
     *     )
     * )
     */
    public function update($id)
    {
        try {
            $employee = $this->employeeModel->findById($id);

            if (!$employee) {
                Response::notFound('Employee not found');
            }

            $data = Request::all();

            // Validation (optional fields)
            $validationRules = [];
            if (isset($data['email'])) {
                $validationRules['email'] = 'email';
            }
            if (isset($data['hire_date'])) {
                $validationRules['hire_date'] = 'date';
            }

            if (!empty($validationRules)) {
                Validator::make($data, $validationRules)->validate();
            }

            $this->employeeModel->update($id, $data);

            Response::json([
                'success' => true,
                'message' => 'Employee updated successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/employees/{id}",
     *     tags={"Employees"},
     *     summary="Delete employee",
     *     description="Remove an employee from the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Employee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employee deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $employee = $this->employeeModel->findById($id);

            if (!$employee) {
                Response::notFound('Employee not found');
            }

            $this->employeeModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete employee: ' . $e->getMessage(), 500);
        }
    }
}

