<?php

class ExpenseController
{
    private $expenseModel;
    private $categoryModel;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->categoryModel = new ExpenseCategory();
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
            Response::error('Failed to fetch expense categories: ' . $e->getMessage(), 500);
        }
    }

    public function createCategory()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'expense_name' => 'required|string|max:250'
            ])->validate();

            $categoryId = $this->categoryModel->create([
                'expense_name' => $data['expense_name']
            ]);

            Response::json([
                'success' => true,
                'message' => 'Expense category created successfully',
                'data' => ['id' => $categoryId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create expense category: ' . $e->getMessage(), 500);
        }
    }

    public function showCategory($id)
    {
        try {
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Expense category not found');
            }

            Response::json([
                'success' => true,
                'data' => $category
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch expense category: ' . $e->getMessage(), 500);
        }
    }

    public function updateCategory($id)
    {
        try {
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Expense category not found');
            }

            $data = Request::all();
            $this->categoryModel->update($id, $data);

            Response::json([
                'success' => true,
                'message' => 'Expense category updated successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update expense category: ' . $e->getMessage(), 500);
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Expense category not found');
            }

            $this->categoryModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Expense category deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete expense category: ' . $e->getMessage(), 500);
        }
    }

    // Expense Entry Management
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

            $expenses = $this->expenseModel->getAll($filters);

            Response::json([
                'success' => true,
                'data' => $expenses
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch expenses: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'COAID' => 'required|string',
                'VDate' => 'required|date',
                'Debit' => 'required|numeric',
                'Narration' => 'required|string'
            ])->validate();

            $user = Auth::user();
            $voucherNo = $this->expenseModel->generateVoucherNo();

            $expenseData = [
                'VNo' => $voucherNo,
                'VDate' => $data['VDate'],
                'COAID' => $data['COAID'],
                'Narration' => $data['Narration'],
                'Debit' => $data['Debit'],
                'Credit' => 0,
                'CreateBy' => $user['id'],
                'CreateDate' => now()
            ];

            $expenseId = $this->expenseModel->create($expenseData);

            Response::json([
                'success' => true,
                'message' => 'Expense recorded successfully',
                'data' => ['id' => $expenseId, 'voucher_no' => $voucherNo]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to record expense: ' . $e->getMessage(), 500);
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

            $statement = $this->expenseModel->getStatement($filters);

            Response::json([
                'success' => true,
                'data' => $statement
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate expense statement: ' . $e->getMessage(), 500);
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

            $summary = $this->expenseModel->getSummary($filters);
            
            $totalExpenses = array_sum(array_column($summary, 'total_amount'));

            Response::json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'total_expenses' => $totalExpenses
                ]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate expense summary: ' . $e->getMessage(), 500);
        }
    }
}

