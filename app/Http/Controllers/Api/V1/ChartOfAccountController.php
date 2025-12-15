<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Chart of Accounts",
 *     description="Chart of Accounts (COA) management endpoints"
 * )
 */
class ChartOfAccountController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/chart-of-accounts",
     *     summary="Get all chart of accounts",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status (1 or 0)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Parameter(
     *         name="is_transaction",
     *         in="query",
     *         description="Filter transaction accounts (1 or 0)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Parameter(
     *         name="head_type",
     *         in="query",
     *         description="Filter by head type (A=Asset, L=Liability, I=Income, E=Expense)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of chart of accounts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="HeadCode", type="string", example="102010001"),
     *                 @OA\Property(property="HeadName", type="string", example="Cash"),
     *                 @OA\Property(property="PHeadName", type="string", example="Cash & Cash Equivalent"),
     *                 @OA\Property(property="HeadLevel", type="integer", example=3),
     *                 @OA\Property(property="IsActive", type="boolean", example=true),
     *                 @OA\Property(property="IsTransaction", type="boolean", example=true),
     *                 @OA\Property(property="HeadType", type="string", example="A")
     *             ))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = ChartOfAccount::orderBy('HeadCode');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('IsActive', $request->is_active);
        }

        // Filter transaction accounts
        if ($request->has('is_transaction')) {
            $query->where('IsTransaction', $request->is_transaction);
        }

        // Filter by head type
        if ($request->has('head_type')) {
            $query->where('HeadType', $request->head_type);
        }

        // Filter by head level
        if ($request->has('head_level')) {
            $query->where('HeadLevel', $request->head_level);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('HeadName', 'like', "%{$search}%")
                  ->orWhere('HeadCode', 'like', "%{$search}%");
            });
        }

        $accounts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chart-of-accounts/tree",
     *     summary="Get chart of accounts in tree structure",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tree structure of accounts"
     *     )
     * )
     */
    public function tree()
    {
        $accounts = ChartOfAccount::active()
            ->orderBy('HeadCode')
            ->get();

        // Build tree structure
        $tree = $this->buildTree($accounts);

        return response()->json([
            'success' => true,
            'data' => $tree
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chart-of-accounts/{headCode}",
     *     summary="Get specific account details",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="headCode",
     *         in="path",
     *         description="Head Code of the account",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account details"
     *     ),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function show($headCode)
    {
        $account = ChartOfAccount::find($headCode);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Load relationships
        $account->load(['children', 'parent']);

        return response()->json([
            'success' => true,
            'data' => $account
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chart-of-accounts",
     *     summary="Create new account",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"HeadCode","HeadName","PHeadName","HeadLevel","HeadType"},
     *             @OA\Property(property="HeadCode", type="string", example="102010001"),
     *             @OA\Property(property="HeadName", type="string", example="Petty Cash"),
     *             @OA\Property(property="PHeadName", type="string", example="Cash & Cash Equivalent"),
     *             @OA\Property(property="HeadLevel", type="integer", example=3),
     *             @OA\Property(property="HeadType", type="string", example="A"),
     *             @OA\Property(property="IsActive", type="boolean", example=true),
     *             @OA\Property(property="IsTransaction", type="boolean", example=true),
     *             @OA\Property(property="IsGL", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'HeadCode' => 'required|string|unique:acc_coa,HeadCode|max:50',
            'HeadName' => 'required|string|max:100',
            'PHeadName' => 'required|string|max:100',
            'HeadLevel' => 'required|integer|min:1|max:10',
            'HeadType' => 'required|in:A,L,I,E',
            'IsActive' => 'boolean',
            'IsTransaction' => 'boolean',
            'IsGL' => 'boolean',
            'IsBudget' => 'boolean',
            'IsDepreciation' => 'boolean',
            'DepreciationRate' => 'numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $account = ChartOfAccount::create([
            'HeadCode' => $request->HeadCode,
            'HeadName' => $request->HeadName,
            'PHeadName' => $request->PHeadName,
            'HeadLevel' => $request->HeadLevel,
            'HeadType' => $request->HeadType,
            'IsActive' => $request->input('IsActive', 1),
            'IsTransaction' => $request->input('IsTransaction', 0),
            'IsGL' => $request->input('IsGL', 0),
            'IsBudget' => $request->input('IsBudget', 0),
            'IsDepreciation' => $request->input('IsDepreciation', 0),
            'DepreciationRate' => $request->input('DepreciationRate', 0),
            'CreateBy' => auth()->id(),
            'CreateDate' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/chart-of-accounts/{headCode}",
     *     summary="Update account",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="headCode",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="HeadName", type="string"),
     *             @OA\Property(property="IsActive", type="boolean"),
     *             @OA\Property(property="IsTransaction", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Account updated successfully"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function update(Request $request, $headCode)
    {
        $account = ChartOfAccount::find($headCode);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'HeadName' => 'string|max:100',
            'IsActive' => 'boolean',
            'IsTransaction' => 'boolean',
            'IsGL' => 'boolean',
            'IsBudget' => 'boolean',
            'IsDepreciation' => 'boolean',
            'DepreciationRate' => 'numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $account->fill($request->only([
            'HeadName', 'IsActive', 'IsTransaction', 'IsGL',
            'IsBudget', 'IsDepreciation', 'DepreciationRate'
        ]));
        $account->UpdateBy = auth()->id();
        $account->UpdateDate = now();
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/chart-of-accounts/{headCode}",
     *     summary="Delete account",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="headCode",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Account deleted successfully"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function destroy($headCode)
    {
        $account = ChartOfAccount::find($headCode);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Check if account has transactions
        if ($account->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with existing transactions'
            ], 422);
        }

        // Check if account has children
        if ($account->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with child accounts'
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chart-of-accounts/transaction-accounts",
     *     summary="Get accounts available for transactions",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of transaction accounts")
     * )
     */
    public function transactionAccounts()
    {
        $accounts = ChartOfAccount::active()
            ->transactionable()
            ->orderBy('HeadName')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chart-of-accounts/by-type/{type}",
     *     summary="Get accounts by type",
     *     tags={"Chart of Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", enum={"A", "L", "I", "E"})
     *     ),
     *     @OA\Response(response=200, description="List of accounts by type")
     * )
     */
    public function byType($type)
    {
        $accounts = ChartOfAccount::active()
            ->byHeadType($type)
            ->orderBy('HeadName')
            ->get();

        return response()->json([
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
            if ($account->PHeadName == $parentName) {
                $children = $this->buildTree($accounts, $account->HeadName);
                
                $accountArray = $account->toArray();
                if ($children) {
                    $accountArray['children'] = $children;
                }
                
                $branch[] = $accountArray;
            }
        }

        return $branch;
    }
}

