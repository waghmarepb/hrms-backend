<?php

class AwardController
{
    private $awardModel;

    public function __construct()
    {
        $this->awardModel = new Award();
    }

    public function index()
    {
        try {
            $awards = $this->awardModel->getAll();
            Response::json(['success' => true, 'data' => $awards], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch awards: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'employee_id' => 'required|string',
                'award_name' => 'required|string',
                'award_date' => 'required|date',
                'award_description' => 'string'
            ])->validate();

            $awardId = $this->awardModel->create($data);
            Response::json(['success' => true, 'message' => 'Award created', 'data' => ['id' => $awardId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to create award: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $award = $this->awardModel->findById($id);
            if (!$award) Response::notFound('Award not found');
            Response::json(['success' => true, 'data' => $award], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch award: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $award = $this->awardModel->findById($id);
            if (!$award) Response::notFound('Award not found');
            
            $data = Request::all();
            $this->awardModel->update($id, $data);
            Response::json(['success' => true, 'message' => 'Award updated'], 200);
        } catch (Exception $e) {
            Response::error('Failed to update award: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $award = $this->awardModel->findById($id);
            if (!$award) Response::notFound('Award not found');
            
            $this->awardModel->delete($id);
            Response::json(['success' => true, 'message' => 'Award deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete award: ' . $e->getMessage(), 500);
        }
    }

    public function employeeAwards($employeeId)
    {
        try {
            $awards = $this->awardModel->getEmployeeAwards($employeeId);
            Response::json(['success' => true, 'data' => $awards], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch employee awards: ' . $e->getMessage(), 500);
        }
    }
}

