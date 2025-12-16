<?php

class IncomeController
{
    private $incomeModel;
    private $categoryModel;

    public function __construct()
    {
        $this->incomeModel = new Income();
        $this->categoryModel = new IncomeCategory();
    }

    // Category Management
    public function categories()
    {
        try {
            $categories = $this->categoryModel->getAll();

            Response::json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch income categories: ' . $e->getMessage(), 500);
        }
    }

    public function createCategory()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'income_name' => 'required|string|max:250'
            ])->validate();

            $categoryId = $this->categoryModel->create([
                'income_name' => $data['income_name']
            ]);

            Response::json([
                'success' => true,
                'message' => 'Income category created successfully',
                'data' => ['id' => $categoryId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create income category: ' . $e->getMessage(), 500);
        }
    }

    public function showCategory($id)
    {
        try {
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Income category not found');
            }

            Response::json([
                'success' => true,
                'data' => $category
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch income category: ' . $e->getMessage(), 500);
        }
    }

    public function updateCategory($id)
    {
        try {
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Income category not found');
            }

            $data = Request::all();
            $this->categoryModel->update($id, $data);

            Response::json([
                'success' => true,
                'message' => 'Income category updated successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update income category: ' . $e->getMessage(), 500);
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Income category not found');
            }

            $this->categoryModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Income category deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete income category: ' . $e->getMessage(), 500);
        }
    }

    // Income Entry Management
    public function index()
    {
        try {
            $filters = [];
            
            if (Request::has('from_date') && Request::has('to_date')) {
                $filters['from_date'] = Request::input('from_date');
                $filters['to_date'] = Request::input('to_date');
            }
            
            if (Request::has('category')) {
                $filters['category'] = Request::input('category');
            }

            $incomes = $this->incomeModel->getAll($filters);

            Response::json([
                'success' => true,
                'data' => $incomes
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch incomes: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'COAID' => 'required|string',
                'VDate' => 'required|date',
                'Credit' => 'required|numeric',
                'Narration' => 'required|string'
            ])->validate();

            $user = Auth::user();
            $voucherNo = $this->incomeModel->generateVoucherNo();

            $incomeData = [
                'VNo' => $voucherNo,
                'VDate' => $data['VDate'],
                'COAID' => $data['COAID'],
                'Narration' => $data['Narration'],
                'Debit' => 0,
                'Credit' => $data['Credit'],
                'CreateBy' => $user['id'],
                'CreateDate' => now()
            ];

            $incomeId = $this->incomeModel->create($incomeData);

            Response::json([
                'success' => true,
                'message' => 'Income recorded successfully',
                'data' => ['id' => $incomeId, 'voucher_no' => $voucherNo]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to record income: ' . $e->getMessage(), 500);
        }
    }

    public function statement()
    {
        try {
            $filters = [];
            
            if (Request::has('from_date') && Request::has('to_date')) {
                $filters['from_date'] = Request::input('from_date');
                $filters['to_date'] = Request::input('to_date');
            }

            $statement = $this->incomeModel->getStatement($filters);

            Response::json([
                'success' => true,
                'data' => $statement
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate income statement: ' . $e->getMessage(), 500);
        }
    }

    public function summary()
    {
        try {
            $filters = [];
            
            if (Request::has('from_date') && Request::has('to_date')) {
                $filters['from_date'] = Request::input('from_date');
                $filters['to_date'] = Request::input('to_date');
            }

            $summary = $this->incomeModel->getSummary($filters);
            
            $totalIncome = array_sum(array_column($summary, 'total_amount'));

            Response::json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'total_income' => $totalIncome
                ]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate income summary: ' . $e->getMessage(), 500);
        }
    }
}

