<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Financial Reports",
 *     description="Financial reports (Trial Balance, P&L, Cash Flow, Balance Sheet)"
 * )
 */
class FinancialReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/financial-reports/trial-balance",
     *     summary="Get trial balance report",
     *     tags={"Financial Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="with_opening",
     *         in="query",
     *         description="Include opening balances",
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(response=200, description="Trial balance report")
     * )
     */
    public function trialBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'with_opening' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $withOpening = $request->input('with_opening', false);
        
        // Get all transaction accounts
        $accounts = ChartOfAccount::where('IsTransaction', 1)
            ->where('IsActive', 1)
            ->orderBy('HeadCode')
            ->get();

        $trialBalanceData = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            if ($withOpening) {
                // Calculate opening balance
                $openingResult = AccountTransaction::where('COAID', $account->HeadCode)
                    ->where('VDate', '<', $request->from_date)
                    ->where('IsPosted', 1)
                    ->selectRaw('SUM(Debit) as debit, SUM(Credit) as credit')
                    ->first();

                $openingDebit = $openingResult->debit ?? 0;
                $openingCredit = $openingResult->credit ?? 0;
            } else {
                $openingDebit = 0;
                $openingCredit = 0;
            }

            // Calculate period transactions
            $periodResult = AccountTransaction::where('COAID', $account->HeadCode)
                ->whereBetween('VDate', [$request->from_date, $request->to_date])
                ->where('IsPosted', 1)
                ->selectRaw('SUM(Debit) as debit, SUM(Credit) as credit')
                ->first();

            $periodDebit = $periodResult->debit ?? 0;
            $periodCredit = $periodResult->credit ?? 0;

            // Calculate closing balance
            $closingDebit = $openingDebit + $periodDebit;
            $closingCredit = $openingCredit + $periodCredit;

            // Only include accounts with transactions
            if ($closingDebit > 0 || $closingCredit > 0) {
                $balance = $closingDebit - $closingCredit;
                
                $trialBalanceData[] = [
                    'account_code' => $account->HeadCode,
                    'account_name' => $account->HeadName,
                    'head_type' => $account->HeadType,
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

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
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
     * @OA\Get(
     *     path="/api/v1/financial-reports/profit-loss",
     *     summary="Get profit and loss statement",
     *     tags={"Financial Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="Profit and loss statement")
     * )
     */
    public function profitLoss(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get income accounts (I)
        $incomeAccounts = $this->getAccountBalances('I', $request->from_date, $request->to_date);
        $totalIncome = collect($incomeAccounts)->sum('balance');

        // Get expense accounts (E)
        $expenseAccounts = $this->getAccountBalances('E', $request->from_date, $request->to_date);
        $totalExpenses = collect($expenseAccounts)->sum('balance');

        // Calculate net profit/loss
        $netProfitLoss = $totalIncome - $totalExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
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
     * @OA\Get(
     *     path="/api/v1/financial-reports/balance-sheet",
     *     summary="Get balance sheet",
     *     tags={"Financial Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="as_of_date",
     *         in="query",
     *         required=true,
     *         description="Date for balance sheet",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="Balance sheet")
     * )
     */
    public function balanceSheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'as_of_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $asOfDate = $request->as_of_date;

        // Get asset accounts (A)
        $assets = $this->getAccountBalances('A', null, $asOfDate);
        $totalAssets = collect($assets)->sum('balance');

        // Get liability accounts (L)
        $liabilities = $this->getAccountBalances('L', null, $asOfDate);
        $totalLiabilities = collect($liabilities)->sum('balance');

        // Calculate equity (Assets - Liabilities)
        $equity = $totalAssets - $totalLiabilities;

        return response()->json([
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
     * @OA\Get(
     *     path="/api/v1/financial-reports/cash-flow",
     *     summary="Get cash flow statement",
     *     tags={"Financial Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="Cash flow statement")
     * )
     */
    public function cashFlow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get cash and bank accounts
        $cashAccounts = ChartOfAccount::where('PHeadName', 'Cash & Cash Equivalent')
            ->where('IsActive', 1)
            ->pluck('HeadCode');

        // Opening cash balance
        $openingBalance = 0;
        foreach ($cashAccounts as $accountCode) {
            $result = AccountTransaction::where('COAID', $accountCode)
                ->where('VDate', '<', $request->from_date)
                ->where('IsPosted', 1)
                ->selectRaw('SUM(Debit - Credit) as balance')
                ->first();
            
            $openingBalance += $result->balance ?? 0;
        }

        // Cash flows during period
        $transactions = AccountTransaction::with('chartOfAccount')
            ->whereIn('COAID', $cashAccounts)
            ->whereBetween('VDate', [$request->from_date, $request->to_date])
            ->where('IsPosted', 1)
            ->orderBy('VDate')
            ->get();

        $cashInflows = $transactions->where('Debit', '>', 0);
        $cashOutflows = $transactions->where('Credit', '>', 0);

        $totalInflows = $cashInflows->sum('Debit');
        $totalOutflows = $cashOutflows->sum('Credit');
        $netCashFlow = $totalInflows - $totalOutflows;
        $closingBalance = $openingBalance + $netCashFlow;

        // Categorize cash flows (simplified version)
        $operatingActivities = [];
        $investingActivities = [];
        $financingActivities = [];

        foreach ($transactions as $transaction) {
            $amount = $transaction->Debit > 0 ? $transaction->Debit : -$transaction->Credit;
            $item = [
                'date' => $transaction->VDate,
                'account' => $transaction->chartOfAccount->HeadName ?? '',
                'description' => $transaction->Narration,
                'amount' => $amount
            ];

            // Simple categorization based on account type
            // In reality, this would need more sophisticated logic
            if (strpos(strtolower($transaction->Narration), 'asset') !== false ||
                strpos(strtolower($transaction->Narration), 'investment') !== false) {
                $investingActivities[] = $item;
            } elseif (strpos(strtolower($transaction->Narration), 'loan') !== false ||
                      strpos(strtolower($transaction->Narration), 'capital') !== false) {
                $financingActivities[] = $item;
            } else {
                $operatingActivities[] = $item;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'opening_balance' => $openingBalance,
                'cash_flows' => [
                    'operating_activities' => [
                        'items' => $operatingActivities,
                        'total' => collect($operatingActivities)->sum('amount')
                    ],
                    'investing_activities' => [
                        'items' => $investingActivities,
                        'total' => collect($investingActivities)->sum('amount')
                    ],
                    'financing_activities' => [
                        'items' => $financingActivities,
                        'total' => collect($financingActivities)->sum('amount')
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
        $accounts = ChartOfAccount::where('HeadType', $headType)
            ->where('IsActive', 1)
            ->where('IsTransaction', 1)
            ->orderBy('HeadName')
            ->get();

        $balances = [];

        foreach ($accounts as $account) {
            $query = AccountTransaction::where('COAID', $account->HeadCode)
                ->where('IsPosted', 1);

            if ($fromDate && $toDate) {
                $query->whereBetween('VDate', [$fromDate, $toDate]);
            } elseif ($toDate) {
                $query->where('VDate', '<=', $toDate);
            }

            $result = $query->selectRaw('SUM(Debit) as debit, SUM(Credit) as credit')
                ->first();

            $debit = $result->debit ?? 0;
            $credit = $result->credit ?? 0;
            $balance = abs($debit - $credit);

            // Only include accounts with balance
            if ($balance > 0) {
                $balances[] = [
                    'account_code' => $account->HeadCode,
                    'account_name' => $account->HeadName,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance
                ];
            }
        }

        return $balances;
    }
}

