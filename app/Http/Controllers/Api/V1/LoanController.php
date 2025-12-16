<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Employee;
use App\Models\AccountTransaction;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Loans",
 *     description="Loan management endpoints"
 * )
 */
class LoanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/loans",
     *     summary="Get all loans",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (0=Pending, 1=Approved, 2=Rejected, 3=Completed)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of loans")
     * )
     */
    public function index(Request $request)
    {
        $query = Loan::with(['employee', 'supervisor', 'installments'])
            ->orderBy('loan_id', 'DESC');

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('loan_status', $request->status);
        }

        $loans = $query->paginate(50);

        // Add calculated fields
        $loans->getCollection()->transform(function ($loan) {
            $loan->total_paid = $loan->installments->sum('payment');
            $loan->remaining_balance = $loan->repayment_amount - $loan->total_paid;
            return $loan;
        });

        return response()->json([
            'success' => true,
            'data' => $loans
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/loans/{id}",
     *     summary="Get loan details",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Loan details"),
     *     @OA\Response(response=404, description="Loan not found")
     * )
     */
    public function show($id)
    {
        $loan = Loan::with(['employee', 'supervisor', 'installments.receiver'])
            ->find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $loan->total_paid = $loan->installments->sum('payment');
        $loan->remaining_balance = $loan->repayment_amount - $loan->total_paid;

        return response()->json([
            'success' => true,
            'data' => $loan
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/loans",
     *     summary="Apply for a loan",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","permission_by","amount","interest_rate","installment","installment_period","date_of_approve","repayment_start_date"},
     *             @OA\Property(property="employee_id", type="string", example="EMP001"),
     *             @OA\Property(property="permission_by", type="string", example="EMP002"),
     *             @OA\Property(property="loan_details", type="string", example="Personal loan for emergency"),
     *             @OA\Property(property="amount", type="number", example=50000.00),
     *             @OA\Property(property="interest_rate", type="number", example=5.00),
     *             @OA\Property(property="installment", type="integer", example=12),
     *             @OA\Property(property="installment_period", type="string", example="Monthly"),
     *             @OA\Property(property="repayment_amount", type="number", example=52500.00),
     *             @OA\Property(property="date_of_approve", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="loan_status", type="integer", example=0),
     *             @OA\Property(property="repayment_start_date", type="string", format="date", example="2026-01-15")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Loan created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string|exists:employee_history,employee_id',
            'permission_by' => 'required|string|exists:employee_history,employee_id',
            'loan_details' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'interest_rate' => 'required|numeric|min:0',
            'installment' => 'required|integer|min:1',
            'installment_period' => 'required|string',
            'repayment_amount' => 'required|numeric|min:0.01',
            'date_of_approve' => 'required|date',
            'loan_status' => 'nullable|in:0,1,2,3',
            'repayment_start_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create loan
            $loan = Loan::create([
                'employee_id' => $request->employee_id,
                'permission_by' => $request->permission_by,
                'loan_details' => $request->loan_details ?? '',
                'amount' => $request->amount,
                'interest_rate' => $request->interest_rate,
                'installment' => $request->installment,
                'installment_period' => $request->installment_period,
                'repayment_amount' => $request->repayment_amount,
                'date_of_approve' => $request->date_of_approve,
                'loan_status' => $request->loan_status ?? Loan::STATUS_PENDING,
                'repayment_start_date' => $request->repayment_start_date,
            ]);

            // If loan is approved, create accounting entries
            if ($request->loan_status == Loan::STATUS_APPROVED) {
                $this->createLoanAccountingEntries($loan);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan application created successfully',
                'data' => $loan
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create loan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/loans/{id}",
     *     summary="Update loan",
     *     tags={"Loans"},
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
     *             @OA\Property(property="loan_details", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="interest_rate", type="number"),
     *             @OA\Property(property="installment", type="integer"),
     *             @OA\Property(property="repayment_amount", type="number"),
     *             @OA\Property(property="loan_status", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Loan updated"),
     *     @OA\Response(response=404, description="Loan not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'loan_details' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0.01',
            'interest_rate' => 'nullable|numeric|min:0',
            'installment' => 'nullable|integer|min:1',
            'repayment_amount' => 'nullable|numeric|min:0.01',
            'loan_status' => 'nullable|in:0,1,2,3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $loan->fill($request->only([
            'loan_details', 'amount', 'interest_rate', 'installment',
            'repayment_amount', 'loan_status'
        ]));
        $loan->save();

        return response()->json([
            'success' => true,
            'message' => 'Loan updated successfully',
            'data' => $loan
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/loans/{id}",
     *     summary="Delete loan",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Loan deleted"),
     *     @OA\Response(response=404, description="Loan not found")
     * )
     */
    public function destroy($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        // Check if loan has installments
        if ($loan->installments()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete loan with existing installments'
            ], 422);
        }

        $loan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Loan deleted successfully'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/loans/{id}/approve",
     *     summary="Approve loan",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Loan approved"),
     *     @OA\Response(response=404, description="Loan not found")
     * )
     */
    public function approve($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $loan->loan_status = Loan::STATUS_APPROVED;
            $loan->save();

            // Create accounting entries
            $this->createLoanAccountingEntries($loan);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan approved successfully',
                'data' => $loan
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve loan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/loans/{id}/reject",
     *     summary="Reject loan",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Loan rejected")
     * )
     */
    public function reject($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $loan->loan_status = Loan::STATUS_REJECTED;
        $loan->save();

        return response()->json([
            'success' => true,
            'message' => 'Loan rejected',
            'data' => $loan
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/loans/{id}/installments",
     *     summary="Get loan installments",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of installments")
     * )
     */
    public function installments($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $installments = $loan->installments()->with(['employee', 'receiver'])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'installments' => $installments,
                'total_paid' => $installments->sum('payment'),
                'remaining_balance' => $loan->repayment_amount - $installments->sum('payment')
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/loans/{id}/installments",
     *     summary="Record installment payment",
     *     tags={"Loans"},
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
     *             required={"installment_amount","payment","date","received_by"},
     *             @OA\Property(property="installment_amount", type="number", example=4375.00),
     *             @OA\Property(property="payment", type="number", example=4375.00),
     *             @OA\Property(property="date", type="string", format="date", example="2026-01-15"),
     *             @OA\Property(property="received_by", type="string", example="EMP002"),
     *             @OA\Property(property="installment_no", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Installment recorded")
     * )
     */
    public function recordInstallment(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'installment_amount' => 'required|numeric|min:0.01',
            'payment' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'received_by' => 'required|string|exists:employee_history,employee_id',
            'installment_no' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get next installment number
        $installmentNo = $request->installment_no ?? 
            ($loan->installments()->max('installment_no') + 1);

        $installment = LoanInstallment::create([
            'loan_id' => $loan->loan_id,
            'employee_id' => $loan->employee_id,
            'installment_amount' => $request->installment_amount,
            'payment' => $request->payment,
            'date' => $request->date,
            'received_by' => $request->received_by,
            'installment_no' => $installmentNo
        ]);

        // Check if loan is completed
        $totalPaid = $loan->installments()->sum('payment');
        if ($totalPaid >= $loan->repayment_amount) {
            $loan->loan_status = Loan::STATUS_COMPLETED;
            $loan->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Installment recorded successfully',
            'data' => $installment
        ], 201);
    }

    /**
     * Create accounting entries for approved loan
     */
    private function createLoanAccountingEntries($loan)
    {
        // Get employee COA
        $employee = Employee::where('employee_id', $loan->employee_id)->first();
        $employeeName = $loan->employee_id . '-' . $employee->first_name . $employee->last_name;
        
        $employeeCOA = ChartOfAccount::where('HeadName', $employeeName)->value('HeadCode');

        if (!$employeeCOA) {
            throw new \Exception('Employee account not found in Chart of Accounts');
        }

        // Cash in Hand Credit (money going out)
        AccountTransaction::create([
            'VNo' => $loan->loan_id,
            'Vtype' => 'GrantLoan',
            'VDate' => now(),
            'COAID' => '1020101', // Cash in Hand
            'Narration' => 'Cash in hand Credit For Employee Id ' . $loan->employee_id,
            'Debit' => 0,
            'Credit' => $loan->amount,
            'IsPosted' => 1,
            'IsAppove' => 1,
            'CreateBy' => auth()->id(),
            'CreateDate' => now()
        ]);

        // Employee Account Debit (loan receivable)
        AccountTransaction::create([
            'VNo' => $loan->loan_id,
            'Vtype' => 'Loan Grant',
            'VDate' => now(),
            'COAID' => $employeeCOA,
            'Narration' => 'Loan for ' . $loan->employee_id,
            'Debit' => $loan->amount,
            'Credit' => 0,
            'IsPosted' => 1,
            'IsAppove' => 1,
            'CreateBy' => auth()->id(),
            'CreateDate' => now()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/loans/reports/summary",
     *     summary="Get loan summary report",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
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
     *     @OA\Response(response=200, description="Loan summary")
     * )
     */
    public function summary(Request $request)
    {
        $query = Loan::with(['employee', 'installments']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('date_of_approve', [$request->from_date, $request->to_date]);
        }

        $loans = $query->get();

        $summary = [
            'total_loans' => $loans->count(),
            'total_amount_disbursed' => $loans->sum('amount'),
            'total_repayment_expected' => $loans->sum('repayment_amount'),
            'total_paid' => $loans->sum(function($loan) {
                return $loan->installments->sum('payment');
            }),
            'total_outstanding' => 0,
            'by_status' => [
                'pending' => $loans->where('loan_status', Loan::STATUS_PENDING)->count(),
                'approved' => $loans->where('loan_status', Loan::STATUS_APPROVED)->count(),
                'rejected' => $loans->where('loan_status', Loan::STATUS_REJECTED)->count(),
                'completed' => $loans->where('loan_status', Loan::STATUS_COMPLETED)->count(),
            ]
        ];

        $summary['total_outstanding'] = $summary['total_repayment_expected'] - $summary['total_paid'];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}


