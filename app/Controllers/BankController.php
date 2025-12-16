<?php

class BankController
{
    private $bankModel;

    public function __construct()
    {
        $this->bankModel = new Bank();
    }

    public function index()
    {
        try {
            $banks = $this->bankModel->getAll();
            Response::json(['success' => true, 'data' => $banks], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch banks: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $bank = $this->bankModel->findById($id);
            if (!$bank) Response::notFound('Bank not found');
            Response::json(['success' => true, 'data' => $bank], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch bank: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'bank_name' => 'required|string',
                'ac_number' => 'required|string',
                'branch' => 'string'
            ])->validate();

            $bankId = $this->bankModel->create($data);
            Response::json(['success' => true, 'message' => 'Bank created', 'data' => ['id' => $bankId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to create bank: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $bank = $this->bankModel->findById($id);
            if (!$bank) Response::notFound('Bank not found');
            
            $data = Request::all();
            $this->bankModel->update($id, $data);
            Response::json(['success' => true, 'message' => 'Bank updated'], 200);
        } catch (Exception $e) {
            Response::error('Failed to update bank: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bank = $this->bankModel->findById($id);
            if (!$bank) Response::notFound('Bank not found');
            
            $this->bankModel->delete($id);
            Response::json(['success' => true, 'message' => 'Bank deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete bank: ' . $e->getMessage(), 500);
        }
    }
}

