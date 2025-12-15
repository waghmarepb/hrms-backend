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
 *     name="Ledgers",
 *     description="Ledger reports (General Ledger, Cash Book, Bank Book)"
 * )
 */
class LedgerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/ledgers/general",
     *     summary="Get general ledger report",
     *     tags={"Ledgers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="account_code",
     *         in="query",
     *         required=true,
     *         description="Account head code",
     *         @OA\Schema(type="string")
     *     ),
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
     *     @OA\Response(response=200, description="General ledger report")
     * )
     */
    public function generalLedger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_code' => 'required|exists:acc_coa,HeadCode',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $account = ChartOfAccount::find($request->account_code);
        
        // Get opening balance
        $openingBalance = $this->calculateOpeningBalance(
            $request->account_code,
            $request->from_date
        );

        // Get transactions
        $transactions = AccountTransaction::with('chartOfAccount')
            ->where('COAID', $request->account_code)
            ->whereBetween('VDate', [$request->from_date, $request->to_date])
            ->where('IsPosted', 1)
            ->orderBy('VDate')
            ->orderBy('VNo')
            ->get();

        // Calculate running balance
        $balance = $openingBalance;
        $ledgerData = [];

        foreach ($transactions as $transaction) {
            $balance += $transaction->Debit - $transaction->Credit;
            
            $ledgerData[] = [
                'date' => $transaction->VDate,
                'voucher_no' => $transaction->VNo,
                'voucher_type' => $transaction->Vtype,
                'narration' => $transaction->Narration,
                'debit' => $transaction->Debit,
                'credit' => $transaction->Credit,
                'balance' => $balance
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'opening_balance' => $openingBalance,
                'closing_balance' => $balance,
                'total_debit' => $transactions->sum('Debit'),
                'total_credit' => $transactions->sum('Credit'),
                'transactions' => $ledgerData
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ledgers/cash-book",
     *     summary="Get cash book report",
     *     tags={"Ledgers"},
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
     *     @OA\Response(response=200, description="Cash book report")
     * )
     */
    public function cashBook(Request $request)
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

        // Get cash accounts (typically HeadCode like 102010001 for Cash)
        $cashAccounts = ChartOfAccount::where('PHeadName', 'Cash & Cash Equivalent')
            ->where('IsActive', 1)
            ->where('HeadName', 'like', '%Cash%')
            ->pluck('HeadCode');

        if ($cashAccounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cash accounts found'
            ], 404);
        }

        $transactions = AccountTransaction::with('chartOfAccount')
            ->whereIn('COAID', $cashAccounts)
            ->whereBetween('VDate', [$request->from_date, $request->to_date])
            ->where('IsPosted', 1)
            ->orderBy('VDate')
            ->orderBy('VNo')
            ->get();

        // Calculate opening balance
        $openingBalance = 0;
        foreach ($cashAccounts as $accountCode) {
            $openingBalance += $this->calculateOpeningBalance($accountCode, $request->from_date);
        }

        // Calculate running balance
        $balance = $openingBalance;
        $cashBookData = [];

        foreach ($transactions as $transaction) {
            $balance += $transaction->Debit - $transaction->Credit;
            
            $cashBookData[] = [
                'date' => $transaction->VDate,
                'voucher_no' => $transaction->VNo,
                'voucher_type' => $transaction->Vtype,
                'account' => $transaction->chartOfAccount->HeadName ?? '',
                'narration' => $transaction->Narration,
                'receipt' => $transaction->Debit, // Cash receipts
                'payment' => $transaction->Credit, // Cash payments
                'balance' => $balance
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'opening_balance' => $openingBalance,
                'closing_balance' => $balance,
                'total_receipts' => $transactions->sum('Debit'),
                'total_payments' => $transactions->sum('Credit'),
                'transactions' => $cashBookData
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ledgers/bank-book",
     *     summary="Get bank book report",
     *     tags={"Ledgers"},
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
     *         name="bank_account",
     *         in="query",
     *         description="Specific bank account code (optional)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Bank book report")
     * )
     */
    public function bankBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'bank_account' => 'nullable|exists:acc_coa,HeadCode'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get bank accounts
        if ($request->has('bank_account')) {
            $bankAccounts = [$request->bank_account];
        } else {
            $bankAccounts = ChartOfAccount::where('PHeadName', 'Cash & Cash Equivalent')
                ->where('IsActive', 1)
                ->where(function($query) {
                    $query->where('HeadName', 'like', '%Bank%')
                          ->orWhere('HeadName', 'like', '%bank%');
                })
                ->pluck('HeadCode');
        }

        if (empty($bankAccounts) || collect($bankAccounts)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No bank accounts found'
            ], 404);
        }

        $transactions = AccountTransaction::with('chartOfAccount')
            ->whereIn('COAID', $bankAccounts)
            ->whereBetween('VDate', [$request->from_date, $request->to_date])
            ->where('IsPosted', 1)
            ->orderBy('VDate')
            ->orderBy('VNo')
            ->get();

        // Calculate opening balance
        $openingBalance = 0;
        foreach ($bankAccounts as $accountCode) {
            $openingBalance += $this->calculateOpeningBalance($accountCode, $request->from_date);
        }

        // Calculate running balance
        $balance = $openingBalance;
        $bankBookData = [];

        foreach ($transactions as $transaction) {
            $balance += $transaction->Debit - $transaction->Credit;
            
            $bankBookData[] = [
                'date' => $transaction->VDate,
                'voucher_no' => $transaction->VNo,
                'voucher_type' => $transaction->Vtype,
                'account' => $transaction->chartOfAccount->HeadName ?? '',
                'narration' => $transaction->Narration,
                'deposit' => $transaction->Debit, // Bank deposits
                'withdrawal' => $transaction->Credit, // Bank withdrawals
                'balance' => $balance
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'opening_balance' => $openingBalance,
                'closing_balance' => $balance,
                'total_deposits' => $transactions->sum('Debit'),
                'total_withdrawals' => $transactions->sum('Credit'),
                'transactions' => $bankBookData
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ledgers/account-balance/{accountCode}",
     *     summary="Get account balance",
     *     tags={"Ledgers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accountCode",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="as_of_date",
     *         in="query",
     *         description="Date to calculate balance (defaults to today)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="Account balance"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function accountBalance($accountCode, Request $request)
    {
        $account = ChartOfAccount::find($accountCode);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $balance = $this->calculateBalance($accountCode, $asOfDate);

        return response()->json([
            'success' => true,
            'data' => [
                'account_code' => $accountCode,
                'account_name' => $account->HeadName,
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
        $result = AccountTransaction::where('COAID', $accountCode)
            ->where('VDate', '<', $fromDate)
            ->where('IsPosted', 1)
            ->selectRaw('SUM(Debit) as total_debit, SUM(Credit) as total_credit')
            ->first();

        return ($result->total_debit ?? 0) - ($result->total_credit ?? 0);
    }

    /**
     * Calculate balance as of a specific date
     */
    private function calculateBalance($accountCode, $asOfDate)
    {
        $result = AccountTransaction::where('COAID', $accountCode)
            ->where('VDate', '<=', $asOfDate)
            ->where('IsPosted', 1)
            ->selectRaw('SUM(Debit) as total_debit, SUM(Credit) as total_credit')
            ->first();

        return ($result->total_debit ?? 0) - ($result->total_credit ?? 0);
    }
}

