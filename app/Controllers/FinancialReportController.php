<?php

class FinancialReportController
{
    private $transactionModel;
    private $chartOfAccountModel;
    private $request;
    private $db;

    public function __construct()
    {
        $this->transactionModel = new AccountTransaction();
        $this->chartOfAccountModel = new ChartOfAccount();
        $this->request = new Request();
        $this->db = Database::getInstance();
    }

    /**
     * Get trial balance report
     * GET /api/financial-reports/trial-balance
     */
    public function trialBalance()
    {
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'with_opening' => 'boolean'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $fromDate = $this->request->query('from_date');
        $toDate = $this->request->query('to_date');
        $withOpening = $this->request->query('with_opening') == '1' || $this->request->query('with_opening') === 'true';

        // Get all transaction accounts
        $accounts = $this->db->query(
            "SELECT * FROM acc_coa WHERE IsTransaction = 1 AND IsActive = 1 ORDER BY HeadCode",
            []
        );

        $trialBalanceData = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            if ($withOpening) {
                // Calculate opening balance
                $openingResult = $this->db->query(
                    "SELECT SUM(Debit) as debit, SUM(Credit) as credit
                     FROM acc_transaction
                     WHERE COAID = ? AND VDate < ? AND IsPosted = 1",
                    [$account['HeadCode'], $fromDate]
                );
                $openingDebit = $openingResult[0]['debit'] ?? 0;
                $openingCredit = $openingResult[0]['credit'] ?? 0;
            } else {
                $openingDebit = 0;
                $openingCredit = 0;
            }

            // Calculate period transactions
            $periodResult = $this->db->query(
                "SELECT SUM(Debit) as debit, SUM(Credit) as credit
                 FROM acc_transaction
                 WHERE COAID = ? AND VDate BETWEEN ? AND ? AND IsPosted = 1",
                [$account['HeadCode'], $fromDate, $toDate]
            );
            $periodDebit = $periodResult[0]['debit'] ?? 0;
            $periodCredit = $periodResult[0]['credit'] ?? 0;

            // Calculate closing balance
            $closingDebit = $openingDebit + $periodDebit;
            $closingCredit = $openingCredit + $periodCredit;

            // Only include accounts with transactions
            if ($closingDebit > 0 || $closingCredit > 0) {
                $balance = $closingDebit - $closingCredit;
                
                $trialBalanceData[] = [
                    'account_code' => $account['HeadCode'],
                    'account_name' => $account['HeadName'],
                    'head_type' => $account['HeadType'],
                    'opening_debit' => $openingDebit,
                    'opening_credit' => $openingCredit,
                    'period_debit' => $periodDebit,
                    'period_credit' => $periodCredit,
                    'closing_debit' => $balance >= 0 ? $balance : 0,
                    'closing_credit' => $balance < 0 ? abs($balance) : 0
                ];

                if ($balance >= 0) {
                    $totalDebit += $balance;
                } else {
                    $totalCredit += abs($balance);
                }
            }
        }

        Response::json([
            'success' => true,
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'with_opening' => $withOpening,
                'accounts' => $trialBalanceData,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'difference' => abs($totalDebit - $totalCredit),
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01
            ]
        ]);
    }

    /**
     * Get profit and loss statement
     * GET /api/financial-reports/profit-loss
     */
    public function profitLoss()
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

        // Get income accounts (I)
        $incomeAccounts = $this->getAccountBalances('I', $fromDate, $toDate);
        $totalIncome = array_sum(array_column($incomeAccounts, 'balance'));

        // Get expense accounts (E)
        $expenseAccounts = $this->getAccountBalances('E', $fromDate, $toDate);
        $totalExpenses = array_sum(array_column($expenseAccounts, 'balance'));

        // Calculate net profit/loss
        $netProfitLoss = $totalIncome - $totalExpenses;

        Response::json([
            'success' => true,
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'income' => [
                    'accounts' => $incomeAccounts,
                    'total' => $totalIncome
                ],
                'expenses' => [
                    'accounts' => $expenseAccounts,
                    'total' => $totalExpenses
                ],
                'net_profit_loss' => $netProfitLoss,
                'is_profit' => $netProfitLoss > 0
            ]
        ]);
    }

    /**
     * Get balance sheet
     * GET /api/financial-reports/balance-sheet
     */
    public function balanceSheet()
    {
        $rules = [
            'as_of_date' => 'required|date'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $asOfDate = $this->request->query('as_of_date');

        // Get asset accounts (A)
        $assets = $this->getAccountBalances('A', null, $asOfDate);
        $totalAssets = array_sum(array_column($assets, 'balance'));

        // Get liability accounts (L)
        $liabilities = $this->getAccountBalances('L', null, $asOfDate);
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));

        // Calculate equity (Assets - Liabilities)
        $equity = $totalAssets - $totalLiabilities;

        Response::json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'assets' => [
                    'accounts' => $assets,
                    'total' => $totalAssets
                ],
                'liabilities' => [
                    'accounts' => $liabilities,
                    'total' => $totalLiabilities
                ],
                'equity' => $equity,
                'total_liabilities_and_equity' => $totalLiabilities + $equity,
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $equity)) < 0.01
            ]
        ]);
    }

    /**
     * Get cash flow statement
     * GET /api/financial-reports/cash-flow
     */
    public function cashFlow()
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

        // Get cash and bank accounts
        $cashAccounts = $this->db->query(
            "SELECT HeadCode FROM acc_coa 
             WHERE PHeadName = 'Cash & Cash Equivalent' 
             AND IsActive = 1",
            []
        );
        $cashAccountCodes = array_column($cashAccounts, 'HeadCode');

        // Opening cash balance
        $openingBalance = 0;
        foreach ($cashAccountCodes as $accountCode) {
            $result = $this->db->query(
                "SELECT SUM(Debit - Credit) as balance
                 FROM acc_transaction
                 WHERE COAID = ? AND VDate < ? AND IsPosted = 1",
                [$accountCode, $fromDate]
            );
            $openingBalance += $result[0]['balance'] ?? 0;
        }

        // Cash flows during period
        $placeholders = implode(',', array_fill(0, count($cashAccountCodes), '?'));
        $params = array_merge($cashAccountCodes, [$fromDate, $toDate]);
        $transactions = $this->db->query(
            "SELECT t.*, a.HeadName
             FROM acc_transaction t
             LEFT JOIN acc_coa a ON t.COAID = a.HeadCode
             WHERE t.COAID IN ($placeholders)
             AND t.VDate BETWEEN ? AND ?
             AND t.IsPosted = 1
             ORDER BY t.VDate",
            $params
        );

        $totalInflows = 0;
        $totalOutflows = 0;
        $operatingActivities = [];
        $investingActivities = [];
        $financingActivities = [];

        foreach ($transactions as $transaction) {
            $amount = $transaction['Debit'] > 0 ? $transaction['Debit'] : -$transaction['Credit'];
            
            if ($transaction['Debit'] > 0) {
                $totalInflows += $transaction['Debit'];
            } else {
                $totalOutflows += $transaction['Credit'];
            }

            $item = [
                'date' => $transaction['VDate'],
                'account' => $transaction['HeadName'] ?? '',
                'description' => $transaction['Narration'],
                'amount' => $amount
            ];

            // Simple categorization based on narration
            $narrationLower = strtolower($transaction['Narration']);
            if (strpos($narrationLower, 'asset') !== false ||
                strpos($narrationLower, 'investment') !== false) {
                $investingActivities[] = $item;
            } elseif (strpos($narrationLower, 'loan') !== false ||
                      strpos($narrationLower, 'capital') !== false) {
                $financingActivities[] = $item;
            } else {
                $operatingActivities[] = $item;
            }
        }

        $netCashFlow = $totalInflows - $totalOutflows;
        $closingBalance = $openingBalance + $netCashFlow;

        Response::json([
            'success' => true,
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'opening_balance' => $openingBalance,
                'cash_flows' => [
                    'operating_activities' => [
                        'items' => $operatingActivities,
                        'total' => array_sum(array_column($operatingActivities, 'amount'))
                    ],
                    'investing_activities' => [
                        'items' => $investingActivities,
                        'total' => array_sum(array_column($investingActivities, 'amount'))
                    ],
                    'financing_activities' => [
                        'items' => $financingActivities,
                        'total' => array_sum(array_column($financingActivities, 'amount'))
                    ]
                ],
                'total_inflows' => $totalInflows,
                'total_outflows' => $totalOutflows,
                'net_cash_flow' => $netCashFlow,
                'closing_balance' => $closingBalance
            ]
        ]);
    }

    /**
     * Get account balances by head type
     */
    private function getAccountBalances($headType, $fromDate = null, $toDate = null)
    {
        $accounts = $this->db->query(
            "SELECT * FROM acc_coa 
             WHERE HeadType = ? AND IsActive = 1 AND IsTransaction = 1
             ORDER BY HeadName",
            [$headType]
        );

        $balances = [];

        foreach ($accounts as $account) {
            $sql = "SELECT SUM(Debit) as debit, SUM(Credit) as credit
                    FROM acc_transaction
                    WHERE COAID = ? AND IsPosted = 1";
            $params = [$account['HeadCode']];

            if ($fromDate && $toDate) {
                $sql .= " AND VDate BETWEEN ? AND ?";
                $params[] = $fromDate;
                $params[] = $toDate;
            } elseif ($toDate) {
                $sql .= " AND VDate <= ?";
                $params[] = $toDate;
            }

            $result = $this->db->query($sql, $params);
            $debit = $result[0]['debit'] ?? 0;
            $credit = $result[0]['credit'] ?? 0;
            $balance = abs($debit - $credit);

            // Only include accounts with balance
            if ($balance > 0) {
                $balances[] = [
                    'account_code' => $account['HeadCode'],
                    'account_name' => $account['HeadName'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance
                ];
            }
        }

        return $balances;
    }
}

