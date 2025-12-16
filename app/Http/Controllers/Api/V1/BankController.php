<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Banks",
 *     description="Bank account management endpoints"
 * )
 */
class BankController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/banks",
     *     summary="Get all banks",
     *     tags={"Banks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by bank name, account number, or branch",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of banks")
     * )
     */
    public function index(Request $request)
    {
        $query = Bank::orderBy('bank_name', 'ASC');

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $banks = $query->get();

        return response()->json([
            'success' => true,
            'data' => $banks
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/banks/{id}",
     *     summary="Get bank details",
     *     tags={"Banks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Bank details"),
     *     @OA\Response(response=404, description="Bank not found")
     * )
     */
    public function show($id)
    {
        $bank = Bank::find($id);

        if (!$bank) {
            return response()->json([
                'success' => false,
                'message' => 'Bank not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $bank
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/banks",
     *     summary="Create bank",
     *     tags={"Banks"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bank_name","account_number"},
     *             @OA\Property(property="bank_name", type="string", example="HDFC Bank"),
     *             @OA\Property(property="account_name", type="string", example="ABC Company Pvt Ltd"),
     *             @OA\Property(property="account_number", type="string", example="1234567890"),
     *             @OA\Property(property="branch_name", type="string", example="Mumbai Branch")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Bank created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:250',
            'account_name' => 'nullable|string|max:250',
            'account_number' => 'required|string|max:100|unique:bank_information,account_number',
            'branch_name' => 'nullable|string|max:250'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create bank
            $bank = Bank::create([
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name ?? '',
                'account_number' => $request->account_number,
                'branch_name' => $request->branch_name ?? ''
            ]);

            // Create Chart of Account entry for the bank
            $this->createBankCOA($request->bank_name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank created successfully',
                'data' => $bank
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bank',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/banks/{id}",
     *     summary="Update bank",
     *     tags={"Banks"},
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
     *             @OA\Property(property="bank_name", type="string"),
     *             @OA\Property(property="account_name", type="string"),
     *             @OA\Property(property="account_number", type="string"),
     *             @OA\Property(property="branch_name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Bank updated"),
     *     @OA\Response(response=404, description="Bank not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $bank = Bank::find($id);

        if (!$bank) {
            return response()->json([
                'success' => false,
                'message' => 'Bank not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:250',
            'account_name' => 'nullable|string|max:250',
            'account_number' => 'required|string|max:100|unique:bank_information,account_number,' . $id . ',id',
            'branch_name' => 'nullable|string|max:250'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldBankName = $bank->bank_name;

            // Update bank
            $bank->update([
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name ?? '',
                'account_number' => $request->account_number,
                'branch_name' => $request->branch_name ?? ''
            ]);

            // Update COA if bank name changed
            if ($oldBankName !== $request->bank_name) {
                ChartOfAccount::where('HeadName', $oldBankName)
                    ->update(['HeadName' => $request->bank_name]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank updated successfully',
                'data' => $bank
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bank',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/banks/{id}",
     *     summary="Delete bank",
     *     tags={"Banks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Bank deleted"),
     *     @OA\Response(response=404, description="Bank not found")
     * )
     */
    public function destroy($id)
    {
        $bank = Bank::find($id);

        if (!$bank) {
            return response()->json([
                'success' => false,
                'message' => 'Bank not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $bankName = $bank->bank_name;

            // Delete bank
            $bank->delete();

            // Delete from COA
            ChartOfAccount::where('HeadName', $bankName)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bank',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Chart of Account entry for bank
     */
    private function createBankCOA($bankName)
    {
        // Get next head code for Cash At Bank
        $maxHeadCode = ChartOfAccount::where('HeadLevel', '4')
            ->where('HeadCode', 'LIKE', '102010200%')
            ->max('HeadCode');

        $headCode = $maxHeadCode ? $maxHeadCode + 1 : '10201020001';

        // Create COA entry
        ChartOfAccount::create([
            'HeadCode' => $headCode,
            'HeadName' => $bankName,
            'PHeadName' => 'Cash At Bank',
            'HeadLevel' => '4',
            'IsActive' => '1',
            'IsTransaction' => '1',
            'IsGL' => '0',
            'HeadType' => 'A',
            'IsBudget' => '0',
            'IsDepreciation' => '0',
            'DepreciationRate' => '0',
            'CreateBy' => auth()->id(),
            'CreateDate' => now()
        ]);
    }
}


