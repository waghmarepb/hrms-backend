<?php

class TaxController
{
    private $taxSetupModel;
    private $taxCollectionModel;

    public function __construct()
    {
        $this->taxSetupModel = new TaxSetup();
        $this->taxCollectionModel = new TaxCollection();
    }

    // Tax Setup/Brackets
    public function index()
    {
        try {
            $brackets = $this->taxSetupModel->getAll();
            Response::json(['success' => true, 'data' => $brackets], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch tax brackets: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'min_amount' => 'required|numeric',
                'max_amount' => 'required|numeric',
                'rate' => 'required|numeric'
            ])->validate();

            $bracketId = $this->taxSetupModel->create($data);
            Response::json(['success' => true, 'message' => 'Tax bracket created', 'data' => ['id' => $bracketId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to create tax bracket: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $bracket = $this->taxSetupModel->findById($id);
            if (!$bracket) Response::notFound('Tax bracket not found');
            Response::json(['success' => true, 'data' => $bracket], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch tax bracket: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $bracket = $this->taxSetupModel->findById($id);
            if (!$bracket) Response::notFound('Tax bracket not found');
            
            $data = Request::all();
            $this->taxSetupModel->update($id, $data);
            Response::json(['success' => true, 'message' => 'Tax bracket updated'], 200);
        } catch (Exception $e) {
            Response::error('Failed to update tax bracket: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bracket = $this->taxSetupModel->findById($id);
            if (!$bracket) Response::notFound('Tax bracket not found');
            
            $this->taxSetupModel->delete($id);
            Response::json(['success' => true, 'message' => 'Tax bracket deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete tax bracket: ' . $e->getMessage(), 500);
        }
    }

    public function calculate()
    {
        try {
            $data = Request::all();
            Validator::make($data, ['income' => 'required|numeric'])->validate();

            $tax = $this->taxSetupModel->calculateTax($data['income']);
            Response::json(['success' => true, 'data' => ['tax_amount' => $tax]], 200);
        } catch (Exception $e) {
            Response::error('Failed to calculate tax: ' . $e->getMessage(), 500);
        }
    }

    // Tax Collections
    public function collections()
    {
        try {
            $filters = [];
            if (Request::has('year')) {
                $filters['year'] = Request::input('year');
            }
            
            $collections = $this->taxCollectionModel->getAll($filters);
            Response::json(['success' => true, 'data' => $collections], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch tax collections: ' . $e->getMessage(), 500);
        }
    }

    public function deleteCollection($id)
    {
        try {
            $collection = $this->taxCollectionModel->findById($id);
            if (!$collection) Response::notFound('Tax collection not found');
            
            $this->taxCollectionModel->delete($id);
            Response::json(['success' => true, 'message' => 'Tax collection deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete tax collection: ' . $e->getMessage(), 500);
        }
    }

    public function summary()
    {
        try {
            $filters = [];
            if (Request::has('year')) {
                $filters['year'] = Request::input('year');
            }
            
            $summary = $this->taxCollectionModel->getSummary($filters);
            Response::json(['success' => true, 'data' => $summary], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate tax summary: ' . $e->getMessage(), 500);
        }
    }
}

