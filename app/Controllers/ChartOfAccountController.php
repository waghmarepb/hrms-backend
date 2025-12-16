<?php

class ChartOfAccountController
{
    private $chartOfAccountModel;
    private $request;

    public function __construct()
    {
        $this->chartOfAccountModel = new ChartOfAccount();
        $this->request = new Request();
    }

    /**
     * Get all chart of accounts with filters
     * GET /api/chart-of-accounts
     */
    public function index()
    {
        $filters = [
            'is_active' => $this->request->query('is_active'),
            'is_transaction' => $this->request->query('is_transaction'),
            'head_type' => $this->request->query('head_type'),
            'head_level' => $this->request->query('head_level'),
            'search' => $this->request->query('search'),
        ];

        // Remove null values
        $filters = array_filter($filters, function($value) {
            return $value !== null;
        });

        $accounts = $this->chartOfAccountModel->all($filters);

        Response::json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * Get chart of accounts in tree structure
     * GET /api/chart-of-accounts/tree
     */
    public function tree()
    {
        $accounts = $this->chartOfAccountModel->getActive();
        
        // Build tree structure
        $tree = $this->buildTree($accounts);

        Response::json([
            'success' => true,
            'data' => $tree
        ]);
    }

    /**
     * Get specific account details
     * GET /api/chart-of-accounts/{headCode}
     */
    public function show($headCode)
    {
        $account = $this->chartOfAccountModel->find($headCode);

        if (!$account) {
            Response::json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
            return;
        }

        // Load relationships
        if ($account['PHeadName']) {
            $account['parent'] = $this->chartOfAccountModel->getParent($account['PHeadName']);
        }
        $account['children'] = $this->chartOfAccountModel->getChildren($account['HeadName']);

        Response::json([
            'success' => true,
            'data' => $account
        ]);
    }

    /**
     * Create new account
     * POST /api/chart-of-accounts
     */
    public function store()
    {
        $rules = [
            'HeadCode' => 'required|max:50',
            'HeadName' => 'required|max:100',
            'PHeadName' => 'required|max:100',
            'HeadLevel' => 'required|integer|min:1|max:10',
            'HeadType' => 'required|in:A,L,I,E',
            'IsActive' => 'boolean',
            'IsTransaction' => 'boolean',
            'IsGL' => 'boolean',
            'IsBudget' => 'boolean',
            'IsDepreciation' => 'boolean',
            'DepreciationRate' => 'numeric|min:0|max:100'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json([
                'success' => false,
                'errors' => $errors
            ], 422);
            return;
        }

        // Check if HeadCode already exists
        if ($this->chartOfAccountModel->exists($this->request->input('HeadCode'))) {
            Response::json([
                'success' => false,
                'errors' => ['HeadCode' => ['HeadCode already exists']]
            ], 422);
            return;
        }

        $data = $this->request->only([
            'HeadCode', 'HeadName', 'PHeadName', 'HeadLevel', 'HeadType',
            'IsActive', 'IsTransaction', 'IsGL', 'IsBudget', 
            'IsDepreciation', 'DepreciationRate'
        ]);

        $data['CreateBy'] = $this->request->user['user_id'];
        
        $account = $this->chartOfAccountModel->create($data);

        Response::json([
            'success' => true,
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }

    /**
     * Update account
     * PUT /api/chart-of-accounts/{headCode}
     */
    public function update($headCode)
    {
        $account = $this->chartOfAccountModel->find($headCode);

        if (!$account) {
            Response::json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
            return;
        }

        $rules = [
            'HeadName' => 'string|max:100',
            'IsActive' => 'boolean',
            'IsTransaction' => 'boolean',
            'IsGL' => 'boolean',
            'IsBudget' => 'boolean',
            'IsDepreciation' => 'boolean',
            'DepreciationRate' => 'numeric|min:0|max:100'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json([
                'success' => false,
                'errors' => $errors
            ], 422);
            return;
        }

        $data = $this->request->only([
            'HeadName', 'IsActive', 'IsTransaction', 'IsGL',
            'IsBudget', 'IsDepreciation', 'DepreciationRate'
        ]);

        $data['UpdateBy'] = $this->request->user['user_id'];
        
        $updated = $this->chartOfAccountModel->update($headCode, $data);

        Response::json([
            'success' => true,
            'message' => 'Account updated successfully',
            'data' => $updated
        ]);
    }

    /**
     * Delete account
     * DELETE /api/chart-of-accounts/{headCode}
     */
    public function destroy($headCode)
    {
        $account = $this->chartOfAccountModel->find($headCode);

        if (!$account) {
            Response::json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
            return;
        }

        // Check if account has transactions
        if ($this->chartOfAccountModel->hasTransactions($headCode)) {
            Response::json([
                'success' => false,
                'message' => 'Cannot delete account with existing transactions'
            ], 422);
            return;
        }

        // Check if account has children
        if ($this->chartOfAccountModel->hasChildren($account['HeadName'])) {
            Response::json([
                'success' => false,
                'message' => 'Cannot delete account with child accounts'
            ], 422);
            return;
        }

        $this->chartOfAccountModel->delete($headCode);

        Response::json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Get accounts available for transactions
     * GET /api/chart-of-accounts/transaction-accounts
     */
    public function transactionAccounts()
    {
        $accounts = $this->chartOfAccountModel->getTransactionAccounts();

        Response::json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * Get accounts by type
     * GET /api/chart-of-accounts/by-type/{type}
     */
    public function byType($type)
    {
        // Validate type
        if (!in_array($type, ['A', 'L', 'I', 'E'])) {
            Response::json([
                'success' => false,
                'message' => 'Invalid head type. Must be A, L, I, or E'
            ], 422);
            return;
        }

        $accounts = $this->chartOfAccountModel->getByType($type);

        Response::json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * Build tree structure from flat array
     */
    private function buildTree($accounts, $parentName = null)
    {
        $branch = [];

        foreach ($accounts as $account) {
            if ($account['PHeadName'] == $parentName) {
                $children = $this->buildTree($accounts, $account['HeadName']);
                
                if ($children) {
                    $account['children'] = $children;
                }
                
                $branch[] = $account;
            }
        }

        return $branch;
    }
}

