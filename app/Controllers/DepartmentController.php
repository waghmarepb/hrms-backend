<?php

class DepartmentController
{
    private $departmentModel;

    public function __construct()
    {
        $this->departmentModel = new Department();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/departments",
     *     tags={"Departments"},
     *     summary="Get all departments",
     *     description="Retrieve a list of all departments",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Departments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="department_name", type="string", example="Human Resources"),
     *                     @OA\Property(property="description", type="string", example="HR Department")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $departments = $this->departmentModel->getAll();

            Response::json([
                'success' => true,
                'data' => $departments
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch departments: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $department = $this->departmentModel->findById($id);

            if (!$department) {
                Response::notFound('Department not found');
            }

            Response::json([
                'success' => true,
                'data' => $department
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch department: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'department_name' => 'required|string|max:255',
            ])->validate();

            $departmentId = $this->departmentModel->create($data);

            Response::json([
                'success' => true,
                'message' => 'Department created successfully',
                'data' => ['id' => $departmentId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create department: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $department = $this->departmentModel->findById($id);

            if (!$department) {
                Response::notFound('Department not found');
            }

            $data = Request::all();
            $this->departmentModel->update($id, $data);

            Response::json([
                'success' => true,
                'message' => 'Department updated successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update department: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $department = $this->departmentModel->findById($id);

            if (!$department) {
                Response::notFound('Department not found');
            }

            $this->departmentModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Department deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete department: ' . $e->getMessage(), 500);
        }
    }
}

