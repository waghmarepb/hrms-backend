<?php

class LeaveController
{
    private $leaveModel;

    public function __construct()
    {
        $this->leaveModel = new Leave();
    }

    public function index()
    {
        try {
            $leaves = $this->leaveModel->getAll();

            Response::json([
                'success' => true,
                'data' => $leaves
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch leaves: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $leave = $this->leaveModel->findById($id);

            if (!$leave) {
                Response::notFound('Leave application not found');
            }

            Response::json([
                'success' => true,
                'data' => $leave
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch leave: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();

            // Validation
            Validator::make($data, [
                'employee_id' => 'required|integer',
                'leave_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'reason' => 'required|string',
            ])->validate();

            // Calculate total days
            $totalDays = $this->leaveModel->calculateDays($data['start_date'], $data['end_date']);

            $leaveData = [
                'employee_id' => $data['employee_id'],
                'leave_type' => $data['leave_type'],
                'apply_strt_date' => $data['start_date'],
                'apply_end_date' => $data['end_date'],
                'apply_day' => $totalDays,
                'reason' => $data['reason'],
                'apply_date' => now(),
            ];

            $leaveId = $this->leaveModel->create($leaveData);

            Response::json([
                'success' => true,
                'message' => 'Leave application submitted successfully',
                'data' => ['id' => $leaveId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create leave: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $leave = $this->leaveModel->findById($id);

            if (!$leave) {
                Response::notFound('Leave application not found');
            }

            $data = Request::all();
            $this->leaveModel->update($id, $data);

            Response::json([
                'success' => true,
                'message' => 'Leave updated successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update leave: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $leave = $this->leaveModel->findById($id);

            if (!$leave) {
                Response::notFound('Leave application not found');
            }

            $this->leaveModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Leave deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete leave: ' . $e->getMessage(), 500);
        }
    }

    public function approve($id)
    {
        try {
            $leave = $this->leaveModel->findById($id);

            if (!$leave) {
                Response::notFound('Leave application not found');
            }

            $user = Auth::user();
            $this->leaveModel->approve($id, $user['id']);

            Response::json([
                'success' => true,
                'message' => 'Leave approved successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to approve leave: ' . $e->getMessage(), 500);
        }
    }

    public function reject($id)
    {
        try {
            $leave = $this->leaveModel->findById($id);

            if (!$leave) {
                Response::notFound('Leave application not found');
            }

            $user = Auth::user();
            $this->leaveModel->reject($id, $user['id']);

            Response::json([
                'success' => true,
                'message' => 'Leave rejected'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to reject leave: ' . $e->getMessage(), 500);
        }
    }
}

