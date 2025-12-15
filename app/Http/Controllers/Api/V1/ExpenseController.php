<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ChartOfAccount;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Expenses",
 *     description="Expense management endpoints (Categories and Expense Entries)"
 * )
 */
class ExpenseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/expense-categories",
     *     summary="Get all expense categories",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of expense categories",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="expense_name", type="string", example="Office Supplies")
     *             ))
     *         )
     *     )
     * )
     */
    public function categories()
    {
        $categories = ExpenseCategory::orderBy('expense_name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/expense-categories",
     *     summary="Create new expense category",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"expense_name"},
     *             @OA\Property(property="expense_name", type="string", example="Office Supplies")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_name' => 'required|string|max:250|unique:expense_information,expense_name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create expense category
            $category = ExpenseCategory::create([
                'expense_name' => $request->expense_name
            ]);

            // Create corresponding Chart of Account entry
            $maxHeadCode = ChartOfAccount::where('HeadLevel', 1)
                ->where('HeadCode', 'like', '40%')
                ->max('HeadCode');

            $newHeadCode = $maxHeadCode ? $maxHeadCode + 1 : 402;

            ChartOfAccount::create([
                'HeadCode' => $newHeadCode,
                'HeadName' => $request->expense_name,
                'PHeadName' => 'Expence',
                'HeadLevel' => 1,
                'IsActive' => 1,
                'IsTransaction' => 1,
                'IsGL' => 0,
                'HeadType' => 'E',
                'IsBudget' => 0,
                'IsDepreciation' => 0,
                'DepreciationRate' => 0,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/expense-categories/{id}",
     *     summary="Get expense category details",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function showCategory($id)
    {
        $category = ExpenseCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/expense-categories/{id}",
     *     summary="Update expense category",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="expense_name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category updated"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function updateCategory(Request $request, $id)
    {
        $category = ExpenseCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'expense_name' => 'required|string|max:250|unique:expense_information,expense_name,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldName = $category->expense_name;
            $category->expense_name = $request->expense_name;
            $category->save();

            // Update Chart of Account
            ChartOfAccount::where('HeadName', $oldName)
                ->where('HeadType', 'E')
                ->update(['HeadName' => $request->expense_name]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense category updated successfully',
                'data' => $category
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/expense-categories/{id}",
     *     summary="Delete expense category",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category deleted"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function deleteCategory($id)
    {
        $category = ExpenseCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Delete Chart of Account entry
            ChartOfAccount::where('HeadName', $category->expense_name)
                ->where('HeadType', 'E')
                ->delete();

            // Delete category
            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense category deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/expenses",
     *     summary="Get all expense entries",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of expenses")
     * )
     */
    public function index(Request $request)
    {
        $query = AccountTransaction::with('chartOfAccount')
            ->where('Vtype', Expense::VTYPE_EXPENSE)
            ->where('Debit', '>', 0) // Only debit entries (actual expenses)
            ->orderBy('VDate', 'DESC');

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('VDate', [$request->from_date, $request->to_date]);
        }

        // Filter by category
        if ($request->has('category')) {
            $headCode = ChartOfAccount::where('HeadName', $request->category)
                ->where('HeadType', 'E')
                ->value('HeadCode');
            
            if ($headCode) {
                $query->where('COAID', $headCode);
            }
        }

        $expenses = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/expenses",
     *     summary="Create new expense entry",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"expense_category","amount","expense_date","payment_type"},
     *             @OA\Property(property="expense_category", type="string", example="Office Supplies"),
     *             @OA\Property(property="amount", type="number", example=5000.00),
     *             @OA\Property(property="expense_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="payment_type", type="integer", example=1, description="1=Cash, 2=Bank"),
     *             @OA\Property(property="bank_name", type="string", example="Bank Name (required if payment_type=2)"),
     *             @OA\Property(property="remark", type="string", example="Purchase of office supplies")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Expense created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_category' => 'required|string|exists:expense_information,expense_name',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_type' => 'required|in:1,2',
            'bank_name' => 'required_if:payment_type,2|string',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get expense category Head Code
            $expenseHeadCode = ChartOfAccount::where('HeadName', $request->expense_category)
                ->where('HeadType', 'E')
                ->value('HeadCode');

            if (!$expenseHeadCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense category not found in Chart of Accounts'
                ], 404);
            }

            // Generate voucher number
            $voucherNo = date('Ymdhis');

            // Create expense debit entry
            AccountTransaction::create([
                'VNo' => $voucherNo,
                'Vtype' => Expense::VTYPE_EXPENSE,
                'VDate' => $request->expense_date,
                'COAID' => $expenseHeadCode,
                'Narration' => $request->remark ?? 'Expense entry',
                'Debit' => $request->amount,
                'Credit' => 0,
                'IsPosted' => 1,
                'IsAppove' => 1,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            // Create payment credit entry (Cash or Bank)
            if ($request->payment_type == 1) {
                // Cash payment
                AccountTransaction::create([
                    'VNo' => $voucherNo,
                    'Vtype' => Expense::VTYPE_EXPENSE,
                    'VDate' => $request->expense_date,
                    'COAID' => '1020101', // Cash in Hand
                    'Narration' => $request->expense_category . ' Expense ' . $voucherNo,
                    'Debit' => 0,
                    'Credit' => $request->amount,
                    'IsPosted' => 1,
                    'IsAppove' => 1,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            } else {
                // Bank payment
                $bankHeadCode = ChartOfAccount::where('HeadName', $request->bank_name)
                    ->value('HeadCode');

                if (!$bankHeadCode) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Bank account not found'
                    ], 404);
                }

                AccountTransaction::create([
                    'VNo' => $voucherNo,
                    'Vtype' => Expense::VTYPE_EXPENSE,
                    'VDate' => $request->expense_date,
                    'COAID' => $bankHeadCode,
                    'Narration' => $request->bank_name . ' Expense ' . $voucherNo,
                    'Debit' => 0,
                    'Credit' => $request->amount,
                    'IsPosted' => 1,
                    'IsAppove' => 1,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/expenses/statement",
     *     summary="Get expense statement (by category)",
     *     tags={"Expenses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=true,
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
     *     @OA\Response(response=200, description="Expense statement")
     * )
     */
    public function statement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $headCode = ChartOfAccount::where('HeadName', $request->category)
            ->where('HeadType', 'E')
            ->value('HeadCode');

        if (!$headCode) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found'
            ], 404);
        }

        $expenses = AccountTransaction::with('chartOfAccount')
            ->where('COAID', $headCode)
            ->whereBetween('VDate', [$request->from_date, $request->to_date])
            ->orderBy('VDate', 'DESC')
            ->get();

        $totalExpense = $expenses->sum('Debit');

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $request->category,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'total_expense' => $totalExpense,
                'transactions' => $expenses
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/expenses/summary",
     *     summary="Get all expenses summary",
     *     tags={"Expenses"},
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
     *     @OA\Response(response=200, description="Expense summary by category")
     * )
     */
    public function summary(Request $request)
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

        $summary = DB::table('acc_coa as a')
            ->select('a.HeadName', DB::raw('SUM(b.Debit) as total_expense'))
            ->leftJoin('acc_transaction as b', 'b.COAID', '=', 'a.HeadCode')
            ->where('a.PHeadName', 'Expence')
            ->whereBetween('b.VDate', [$request->from_date, $request->to_date])
            ->groupBy('b.COAID', 'a.HeadName')
            ->get();

        $grandTotal = $summary->sum('total_expense');

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'summary' => $summary,
                'grand_total' => $grandTotal
            ]
        ]);
    }
}

