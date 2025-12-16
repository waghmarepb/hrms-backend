<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\ChartOfAccount;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Income",
 *     description="Income management endpoints (Categories and Income Entries)"
 * )
 */
class IncomeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/income-categories",
     *     summary="Get all income categories",
     *     tags={"Income"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of income categories",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="income_field", type="string", example="Sales Revenue")
     *             ))
     *         )
     *     )
     * )
     */
    public function categories()
    {
        $categories = IncomeCategory::orderBy('income_field', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/income-categories",
     *     summary="Create new income category",
     *     tags={"Income"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"income_field"},
     *             @OA\Property(property="income_field", type="string", example="Sales Revenue")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'income_field' => 'required|string|max:250|unique:income_area,income_field'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create income category
            $category = IncomeCategory::create([
                'income_field' => $request->income_field
            ]);

            // Create corresponding Chart of Account entry
            $maxHeadCode = ChartOfAccount::where('HeadLevel', 1)
                ->where('HeadCode', 'like', '30%')
                ->max('HeadCode');

            $newHeadCode = $maxHeadCode ? $maxHeadCode + 1 : 301;

            ChartOfAccount::create([
                'HeadCode' => $newHeadCode,
                'HeadName' => $request->income_field,
                'PHeadName' => 'Income',
                'HeadLevel' => 1,
                'IsActive' => 1,
                'IsTransaction' => 1,
                'IsGL' => 0,
                'HeadType' => 'I',
                'IsBudget' => 0,
                'IsDepreciation' => 0,
                'DepreciationRate' => 0,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Income category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create income category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/income-categories/{id}",
     *     summary="Get income category details",
     *     tags={"Income"},
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
        $category = IncomeCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Income category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/income-categories/{id}",
     *     summary="Update income category",
     *     tags={"Income"},
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
     *             @OA\Property(property="income_field", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category updated"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function updateCategory(Request $request, $id)
    {
        $category = IncomeCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Income category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'income_field' => 'required|string|max:250|unique:income_area,income_field,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldName = $category->income_field;
            $category->income_field = $request->income_field;
            $category->save();

            // Update Chart of Account
            ChartOfAccount::where('HeadName', $oldName)
                ->where('HeadType', 'I')
                ->update(['HeadName' => $request->income_field]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Income category updated successfully',
                'data' => $category
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update income category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/income-categories/{id}",
     *     summary="Delete income category",
     *     tags={"Income"},
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
        $category = IncomeCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Income category not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Delete Chart of Account entry
            ChartOfAccount::where('HeadName', $category->income_field)
                ->where('HeadType', 'I')
                ->delete();

            // Delete category
            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Income category deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete income category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/incomes",
     *     summary="Get all income entries",
     *     tags={"Income"},
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
     *     @OA\Response(response=200, description="List of incomes")
     * )
     */
    public function index(Request $request)
    {
        $query = AccountTransaction::with('chartOfAccount')
            ->where('Vtype', Income::VTYPE_INCOME)
            ->where('Credit', '>', 0) // Only credit entries (actual income)
            ->orderBy('VDate', 'DESC');

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('VDate', [$request->from_date, $request->to_date]);
        }

        // Filter by category
        if ($request->has('category')) {
            $headCode = ChartOfAccount::where('HeadName', $request->category)
                ->where('HeadType', 'I')
                ->value('HeadCode');
            
            if ($headCode) {
                $query->where('COAID', $headCode);
            }
        }

        $incomes = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $incomes
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/incomes",
     *     summary="Create new income entry",
     *     tags={"Income"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"income_category","amount","income_date","payment_type"},
     *             @OA\Property(property="income_category", type="string", example="Sales Revenue"),
     *             @OA\Property(property="amount", type="number", example=50000.00),
     *             @OA\Property(property="income_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="payment_type", type="integer", example=1, description="1=Cash, 2=Bank"),
     *             @OA\Property(property="bank_name", type="string", example="Bank Name (required if payment_type=2)"),
     *             @OA\Property(property="remark", type="string", example="Monthly sales income")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Income created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'income_category' => 'required|string|exists:income_area,income_field',
            'amount' => 'required|numeric|min:0.01',
            'income_date' => 'required|date',
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
            // Get income category Head Code
            $incomeHeadCode = ChartOfAccount::where('HeadName', $request->income_category)
                ->where('HeadType', 'I')
                ->value('HeadCode');

            if (!$incomeHeadCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Income category not found in Chart of Accounts'
                ], 404);
            }

            // Generate voucher number
            $voucherNo = date('Ymdhis');

            // Create income credit entry (Income is Credit in accounting)
            AccountTransaction::create([
                'VNo' => $voucherNo,
                'Vtype' => Income::VTYPE_INCOME,
                'VDate' => $request->income_date,
                'COAID' => $incomeHeadCode,
                'Narration' => $request->remark ?? 'Income entry',
                'Debit' => 0,
                'Credit' => $request->amount,
                'IsPosted' => 1,
                'IsAppove' => 1,
                'CreateBy' => auth()->id(),
                'CreateDate' => now()
            ]);

            // Create payment debit entry (Cash or Bank receives money)
            if ($request->payment_type == 1) {
                // Cash receipt
                AccountTransaction::create([
                    'VNo' => $voucherNo,
                    'Vtype' => Income::VTYPE_INCOME,
                    'VDate' => $request->income_date,
                    'COAID' => '1020101', // Cash in Hand
                    'Narration' => $request->income_category . ' Income ' . $voucherNo,
                    'Debit' => $request->amount,
                    'Credit' => 0,
                    'IsPosted' => 1,
                    'IsAppove' => 1,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            } else {
                // Bank receipt
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
                    'Vtype' => Income::VTYPE_INCOME,
                    'VDate' => $request->income_date,
                    'COAID' => $bankHeadCode,
                    'Narration' => $request->bank_name . ' Income ' . $voucherNo,
                    'Debit' => $request->amount,
                    'Credit' => 0,
                    'IsPosted' => 1,
                    'IsAppove' => 1,
                    'CreateBy' => auth()->id(),
                    'CreateDate' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Income recorded successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create income',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/incomes/statement",
     *     summary="Get income statement (by category)",
     *     tags={"Income"},
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
     *     @OA\Response(response=200, description="Income statement")
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
            ->where('HeadType', 'I')
            ->value('HeadCode');

        if (!$headCode) {
            return response()->json([
                'success' => false,
                'message' => 'Income category not found'
            ], 404);
        }

        $incomes = AccountTransaction::with('chartOfAccount')
            ->where('COAID', $headCode)
            ->whereBetween('VDate', [$request->from_date, $request->to_date])
            ->orderBy('VDate', 'DESC')
            ->get();

        $totalIncome = $incomes->sum('Credit');

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $request->category,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'total_income' => $totalIncome,
                'transactions' => $incomes
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/incomes/summary",
     *     summary="Get all incomes summary",
     *     tags={"Income"},
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
     *     @OA\Response(response=200, description="Income summary by category")
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
            ->select('a.HeadName', DB::raw('SUM(b.Credit) as total_income'))
            ->leftJoin('acc_transaction as b', 'b.COAID', '=', 'a.HeadCode')
            ->where('a.PHeadName', 'Income')
            ->whereBetween('b.VDate', [$request->from_date, $request->to_date])
            ->groupBy('b.COAID', 'a.HeadName')
            ->get();

        $grandTotal = $summary->sum('total_income');

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


