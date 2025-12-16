<?php

class LedgerController
{
    private $transactionModel;
    private $chartOfAccountModel;
    private $request;

    public function __construct()
    {
        $this->transactionModel = new AccountTransaction();
        $this->chartOfAccountModel = new ChartOfAccount();
        $this->request = new Request();
    }

    /**
     * Get general ledger report
     * GET /api/ledgers/general
     */
    public function generalLedger()
    {
        $rules = [
            'account_code' => 'required',
            'from_date' => 'required|date',
            'to_date' => 'required|date'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $accountCode = $this->request->query('account_code');
        $fromDate = $this->request->query('from_date');
        $toDate = $this->request->query('to_date');

        $account = $this->chartOfAccountModel->find($accountCode);

        if (!$account) {
            Response::json(['success' => false, 'message' => 'Account not found'], 404);
            return;
        }

        // Get opening balance
        $openingBalance = $this->calculateOpeningBalance($accountCode, $fromDate);

        // Get transactions
        $transactions = $this->transactionModel->getByDateRange($fromDate, $toDate, $accountCode);

        // Calculate running balance
        $balance = $openingBalance;
        $ledgerData = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($transactions as $transaction) {
            $balance += $transaction['Debit'] - $transaction['Credit'];
            $totalDebit += $transaction['Debit'];
            $totalCredit += $transaction['Credit'];
            
            $ledgerData[] = [
                'date' => $transaction['VDate'],
                'voucher_no' => $transaction['VNo'],
                'voucher_type' => $transaction['Vtype'],
                'narration' => $transaction['Narration'],
                'debit' => $transaction['Debit'],
                'credit' => $transaction['Credit'],
                'balance' => $balance
            ];
        }

        Response::json([
            'success' => true,
            'data' => [
                'account' => $account,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'opening_balance' => $openingBalance,
                'closing_balance' => $balance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'transactions' => $ledgerData
            ]
        ]);
    }

    /**
     * Get cash book report
     * GET /api/ledgers/cash-book
     */
    public function cashBook()
    {
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $fromDate = $this->request->query('from_date');
        $toDate = $this->request->query('to_date');

        // Get cash accounts
        $db = Database::getInstance();
        $cashAccounts = $db->query(
            "SELECT HeadCode FROM acc_coa 
             WHERE PHeadName = 'Cash & Cash Equivalent' 
             AND IsActive = 1 
             AND HeadName LIKE '%Cash%'",
            []
        );

        if (empty($cashAccounts)) {
            Response::json(['success' => false, 'message' => 'No cash accounts found'], 404);
            return;
        }

        $cashAccountCodes = array_column($cashAccounts, 'HeadCode');
        
        // Get transactions
        $placeholders = implode(',', array_fill(0, count($cashAccountCodes), '?'));
        $sql = "SELECT t.*, a.HeadName
                FROM acc_transaction t
                LEFT JOIN acc_coa a ON t.COAID = a.HeadCode
                WHERE t.COAID IN ($placeholders)
                AND t.VDate BETWEEN ? AND ?
                AND t.IsPosted = 1
                ORDER BY t.VDate, t.VNo";
        
        $params = array_merge($cashAccountCodes, [$fromDate, $toDate]);
        $transactions = $db->query($sql, $params);

        // Calculate opening balance
        $openingBalance = 0;
        foreach ($cashAccountCodes as $accountCode) {
            $openingBalance += $this->calculateOpeningBalance($accountCode, $fromDate);
        }

        // Calculate running balance
        $balance = $openingBalance;
        $cashBookData = [];
        $totalReceipts = 0;
        $totalPayments = 0;

        foreach ($transactions as $transaction) {
            $balance += $transaction['Debit'] - $transaction['Credit'];
            $totalReceipts += $transaction['Debit'];
            $totalPayments += $transaction['Credit'];
            
            $cashBookData[] = [
                'date' => $transaction['VDate'],
                'voucher_no' => $transaction['VNo'],
                'voucher_type' => $transaction['Vtype'],
                'account' => $transaction['HeadName'] ?? '',
                'narration' => $transaction['Narration'],
                'receipt' => $transaction['Debit'],
                'payment' => $transaction['Credit'],
                'balance' => $balance
            ];
        }

        Response::json([
            'success' => true,
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'opening_balance' => $openingBalance,
                'closing_balance' => $balance,
                'total_receipts' => $totalReceipts,
                'total_payments' => $totalPayments,
                'transactions' => $cashBookData
            ]
        ]);
    }

    /**
     * Get bank book report
     * GET /api/ledgers/bank-book
     */
    public function bankBook()
    {
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'bank_account' => 'string'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $fromDate = $this->request->query('from_date');
        $toDate = $this->request->query('to_date');
        $bankAccount = $this->request->query('bank_account');

        // Get bank accounts
        $db = Database::getInstance();
        if ($bankAccount) {
            $bankAccounts = [$bankAccount];
        } else {
            $bankAccountsResult = $db->query(
                "SELECT HeadCode FROM acc_coa 
                 WHERE PHeadName = 'Cash & Cash Equivalent' 
                 AND IsActive = 1 
                 AND (HeadName LIKE '%Bank%' OR HeadName LIKE '%bank%')",
                []
            );
            $bankAccounts = array_column($bankAccountsResult, 'HeadCode');
        }

        if (empty($bankAccounts)) {
            Response::json(['success' => false, 'message' => 'No bank accounts found'], 404);
            return;
        }

        // Get transactions
        $placeholders = implode(',', array_fill(0, count($bankAccounts), '?'));
        $sql = "SELECT t.*, a.HeadName
                FROM acc_transaction t
                LEFT JOIN acc_coa a ON t.COAID = a.HeadCode
                WHERE t.COAID IN ($placeholders)
                AND t.VDate BETWEEN ? AND ?
                AND t.IsPosted = 1
                ORDER BY t.VDate, t.VNo";
        
        $params = array_merge($bankAccounts, [$fromDate, $toDate]);
        $transactions = $db->query($sql, $params);

        // Calculate opening balance
        $openingBalance = 0;
        foreach ($bankAccounts as $accountCode) {
            $openingBalance += $this->calculateOpeningBalance($accountCode, $fromDate);
        }

        // Calculate running balance
        $balance = $openingBalance;
        $bankBookData = [];
        $totalDeposits = 0;
        $totalWithdrawals = 0;

        foreach ($transactions as $transaction) {
            $balance += $transaction['Debit'] - $transaction['Credit'];
            $totalDeposits += $transaction['Debit'];
            $totalWithdrawals += $transaction['Credit'];
            
            $bankBookData[] = [
                'date' => $transaction['VDate'],
                'voucher_no' => $transaction['VNo'],
                'voucher_type' => $transaction['Vtype'],
                'account' => $transaction['HeadName'] ?? '',
                'narration' => $transaction['Narration'],
                'deposit' => $transaction['Debit'],
                'withdrawal' => $transaction['Credit'],
                'balance' => $balance
            ];
        }

        Response::json([
            'success' => true,
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'opening_balance' => $openingBalance,
                'closing_balance' => $balance,
                'total_deposits' => $totalDeposits,
                'total_withdrawals' => $totalWithdrawals,
                'transactions' => $bankBookData
            ]
        ]);
    }

    /**
     * Get account balance
     * GET /api/ledgers/account-balance/{accountCode}
     */
    public function accountBalance($accountCode)
    {
        $account = $this->chartOfAccountModel->find($accountCode);

        if (!$account) {
            Response::json(['success' => false, 'message' => 'Account not found'], 404);
            return;
        }

        $asOfDate = $this->request->query('as_of_date') ?? date('Y-m-d');
        $balance = $this->calculateBalance($accountCode, $asOfDate);

        Response::json([
            'success' => true,
            'data' => [
                'account_code' => $accountCode,
                'account_name' => $account['HeadName'],
                'balance' => $balance,
                'as_of_date' => $asOfDate
            ]
        ]);
    }

    /**
     * Calculate opening balance for an account
     */
    private function calculateOpeningBalance($accountCode, $fromDate)
    {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT SUM(Debit) as total_debit, SUM(Credit) as total_credit
             FROM acc_transaction
             WHERE COAID = ? AND VDate < ? AND IsPosted = 1",
            [$accountCode, $fromDate]
        );

        if (!empty($result)) {
            return ($result[0]['total_debit'] ?? 0) - ($result[0]['total_credit'] ?? 0);
        }

        return 0;
    }

    /**
     * Calculate balance as of a specific date
     */
    private function calculateBalance($accountCode, $asOfDate)
    {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT SUM(Debit) as total_debit, SUM(Credit) as total_credit
             FROM acc_transaction
             WHERE COAID = ? AND VDate <= ? AND IsPosted = 1",
            [$accountCode, $asOfDate]
        );

        if (!empty($result)) {
            return ($result[0]['total_debit'] ?? 0) - ($result[0]['total_credit'] ?? 0);
        }

        return 0;
    }
}

