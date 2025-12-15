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
 *     name="Vouchers",
 *     description="Voucher management endpoints (Debit, Credit, Contra, Journal)"
 * )
 */
class VoucherController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/vouchers",
     *     summary="Get all vouchers",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="vtype",
     *         in="query",
     *         description="Voucher type (DV, CV, ContV, JV)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Start date (Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="End date (Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="is_approved",
     *         in="query",
     *         description="Filter by approval status",
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Response(response=200, description="List of vouchers")
     * )
     */
    public function index(Request $request)
    {
        $query = DB::table('acc_transaction')
            ->select('VNo', 'Vtype', 'VDate', 'IsAppove', 'CreateDate')
            ->selectRaw('SUM(Debit) as total_debit')
            ->selectRaw('SUM(Credit) as total_credit')
            ->selectRaw('MAX(Narration) as narration')
            ->groupBy('VNo', 'Vtype', 'VDate', 'IsAppove', 'CreateDate')
            ->orderBy('VDate', 'DESC')
            ->orderBy('VNo', 'DESC');

        // Filter by voucher type
        if ($request->has('vtype')) {
            $query->where('Vtype', $request->vtype);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('VDate', [$request->from_date, $request->to_date]);
        }

        // Filter by approval status
        if ($request->has('is_approved')) {
            $query->where('IsAppove', $request->is_approved);
        }

        $vouchers = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vouchers/{voucherNo}",
     *     summary="Get voucher details",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="voucherNo",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Voucher details"),
     *     @OA\Response(response=404, description="Voucher not found")
     * )
     */
    public function show($voucherNo)
    {
        $transactions = AccountTransaction::with('chartOfAccount')
            ->byVoucherNo($voucherNo)
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
        }

        $voucher = [
            'voucher_no' => $voucherNo,
            'voucher_type' => $transactions->first()->Vtype,
            'voucher_date' => $transactions->first()->VDate,
            'is_approved' => $transactions->first()->IsAppove,
            'is_posted' => $transactions->first()->IsPosted,
            'narration' => $transactions->first()->Narration,
            'total_debit' => $transactions->sum('Debit'),
            'total_credit' => $transactions->sum('Credit'),
            'transactions' => $transactions
        ];

        return response()->json([
            'success' => true,
            'data' => $voucher
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vouchers/debit",
     *     summary="Create debit voucher",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"voucher_date","debit_account","credit_accounts"},
     *             @OA\Property(property="voucher_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="debit_account", type="object",
     *                 @OA\Property(property="account_code", type="string", example="102010001"),
     *                 @OA\Property(property="amount", type="number", example=5000.00)
     *             ),
     *             @OA\Property(property="credit_accounts", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="account_code", type="string"),
     *                     @OA\Property(property="amount", type="number"),
     *                     @OA\Property(property="narration", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="narration", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Debit voucher created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createDebitVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_date' => 'required|date',
            'debit_account.account_code' => 'required|exists:acc_coa,HeadCode',
            'debit_account.amount' => 'required|numeric|min:0.01',
            'credit_accounts' => 'required|array|min:1',
            'credit_accounts.*.account_code' => 'required|exists:acc_coa,HeadCode',
            'credit_accounts.*.amount' => 'required|numeric|min:0.01',
            'narration' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate debit = credit
        $debitAmount = $request->input('debit_account.amount');
        $creditTotal = collect($request->credit_accounts)->sum('amount');

        if (abs($debitAmount - $creditTotal) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Debit and Credit amounts must be equal',
                'debit' => $debitAmount,
                'credit' => $creditTotal
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate voucher number
            $voucherNo = $this->generateVoucherNumber('DV');

            // Create debit entry
            AccountTransaction::create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_DEBIT,
                'VDate' => $request->voucher_date,
                'COAID' => $request->input('debit_account.account_code'),
                'Narration' => $request->narration ?? 'Debit Voucher',
                'Debit' => $debitAmount,
                'Credit' => 0,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            // Create credit entries
            foreach ($request->credit_accounts as $creditAccount) {
                AccountTransaction::create([
                    'VNo' => $voucherNo,
                    'Vtype' => AccountTransaction::VTYPE_DEBIT,
                    'VDate' => $request->voucher_date,
                    'COAID' => $creditAccount['account_code'],
                    'Narration' => $creditAccount['narration'] ?? $request->narration ?? 'Debit Voucher',
                    'Debit' => 0,
                    'Credit' => $creditAccount['amount'],
                    'IsPosted' => 1,
                    'IsAppove' => 0,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Debit voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create debit voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vouchers/credit",
     *     summary="Create credit voucher",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"voucher_date","debit_accounts","credit_account"},
     *             @OA\Property(property="voucher_date", type="string", format="date"),
     *             @OA\Property(property="debit_accounts", type="array", @OA\Items(
     *                 @OA\Property(property="account_code", type="string"),
     *                 @OA\Property(property="amount", type="number"),
     *                 @OA\Property(property="narration", type="string")
     *             )),
     *             @OA\Property(property="credit_account", type="object",
     *                 @OA\Property(property="account_code", type="string"),
     *                 @OA\Property(property="amount", type="number")
     *             ),
     *             @OA\Property(property="narration", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Credit voucher created")
     * )
     */
    public function createCreditVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_date' => 'required|date',
            'debit_accounts' => 'required|array|min:1',
            'debit_accounts.*.account_code' => 'required|exists:acc_coa,HeadCode',
            'debit_accounts.*.amount' => 'required|numeric|min:0.01',
            'credit_account.account_code' => 'required|exists:acc_coa,HeadCode',
            'credit_account.amount' => 'required|numeric|min:0.01',
            'narration' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate debit = credit
        $debitTotal = collect($request->debit_accounts)->sum('amount');
        $creditAmount = $request->input('credit_account.amount');

        if (abs($debitTotal - $creditAmount) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Debit and Credit amounts must be equal',
                'debit' => $debitTotal,
                'credit' => $creditAmount
            ], 422);
        }

        DB::beginTransaction();
        try {
            $voucherNo = $this->generateVoucherNumber('CV');

            // Create debit entries
            foreach ($request->debit_accounts as $debitAccount) {
                AccountTransaction::create([
                    'VNo' => $voucherNo,
                    'Vtype' => AccountTransaction::VTYPE_CREDIT,
                    'VDate' => $request->voucher_date,
                    'COAID' => $debitAccount['account_code'],
                    'Narration' => $debitAccount['narration'] ?? $request->narration ?? 'Credit Voucher',
                    'Debit' => $debitAccount['amount'],
                    'Credit' => 0,
                    'IsPosted' => 1,
                    'IsAppove' => 0,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            }

            // Create credit entry
            AccountTransaction::create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_CREDIT,
                'VDate' => $request->voucher_date,
                'COAID' => $request->input('credit_account.account_code'),
                'Narration' => $request->narration ?? 'Credit Voucher',
                'Debit' => 0,
                'Credit' => $creditAmount,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credit voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create credit voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vouchers/contra",
     *     summary="Create contra voucher",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="voucher_date", type="string", format="date"),
     *             @OA\Property(property="debit_account", type="object"),
     *             @OA\Property(property="credit_account", type="object"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="narration", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Contra voucher created")
     * )
     */
    public function createContraVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_date' => 'required|date',
            'debit_account' => 'required|exists:acc_coa,HeadCode',
            'credit_account' => 'required|exists:acc_coa,HeadCode',
            'amount' => 'required|numeric|min:0.01',
            'narration' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $voucherNo = $this->generateVoucherNumber('ContV');

            // Create debit entry
            AccountTransaction::create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_CONTRA,
                'VDate' => $request->voucher_date,
                'COAID' => $request->debit_account,
                'Narration' => $request->narration ?? 'Contra Voucher',
                'Debit' => $request->amount,
                'Credit' => 0,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            // Create credit entry
            AccountTransaction::create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_CONTRA,
                'VDate' => $request->voucher_date,
                'COAID' => $request->credit_account,
                'Narration' => $request->narration ?? 'Contra Voucher',
                'Debit' => 0,
                'Credit' => $request->amount,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contra voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contra voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vouchers/journal",
     *     summary="Create journal voucher",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="voucher_date", type="string", format="date"),
     *             @OA\Property(property="entries", type="array", @OA\Items(
     *                 @OA\Property(property="account_code", type="string"),
     *                 @OA\Property(property="debit", type="number"),
     *                 @OA\Property(property="credit", type="number"),
     *                 @OA\Property(property="narration", type="string")
     *             )),
     *             @OA\Property(property="narration", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Journal voucher created")
     * )
     */
    public function createJournalVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_date' => 'required|date',
            'entries' => 'required|array|min:2',
            'entries.*.account_code' => 'required|exists:acc_coa,HeadCode',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'narration' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate debit = credit
        $totalDebit = collect($request->entries)->sum('debit');
        $totalCredit = collect($request->entries)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total debit and credit must be equal',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit
            ], 422);
        }

        DB::beginTransaction();
        try {
            $voucherNo = $this->generateVoucherNumber('JV');

            foreach ($request->entries as $entry) {
                AccountTransaction::create([
                    'VNo' => $voucherNo,
                    'Vtype' => AccountTransaction::VTYPE_JOURNAL,
                    'VDate' => $request->voucher_date,
                    'COAID' => $entry['account_code'],
                    'Narration' => $entry['narration'] ?? $request->narration ?? 'Journal Voucher',
                    'Debit' => $entry['debit'] ?? 0,
                    'Credit' => $entry['credit'] ?? 0,
                    'IsPosted' => 1,
                    'IsAppove' => 0,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Journal voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/vouchers/{voucherNo}/approve",
     *     summary="Approve voucher",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="voucherNo",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Voucher approved"),
     *     @OA\Response(response=404, description="Voucher not found")
     * )
     */
    public function approve($voucherNo)
    {
        $transactions = AccountTransaction::byVoucherNo($voucherNo)->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
        }

        AccountTransaction::byVoucherNo($voucherNo)->update([
            'IsAppove' => 1,
            'UpdateBy' => auth()->id(),
            'UpdateDate' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher approved successfully'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/vouchers/{voucherNo}",
     *     summary="Delete voucher",
     *     tags={"Vouchers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="voucherNo",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Voucher deleted"),
     *     @OA\Response(response=404, description="Voucher not found")
     * )
     */
    public function destroy($voucherNo)
    {
        $transactions = AccountTransaction::byVoucherNo($voucherNo)->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
        }

        // Check if approved
        if ($transactions->first()->IsAppove) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete approved voucher'
            ], 422);
        }

        AccountTransaction::byVoucherNo($voucherNo)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully'
        ]);
    }

    /**
     * Generate voucher number
     */
    private function generateVoucherNumber($type)
    {
        $lastVoucher = AccountTransaction::where('Vtype', $type)
            ->orderBy('VNo', 'DESC')
            ->first();

        if ($lastVoucher) {
            // Extract number from last voucher and increment
            $lastNo = (int) filter_var($lastVoucher->VNo, FILTER_SANITIZE_NUMBER_INT);
            $newNo = $lastNo + 1;
        } else {
            $newNo = 1;
        }

        return $type . '-' . str_pad($newNo, 6, '0', STR_PAD_LEFT);
    }
}

