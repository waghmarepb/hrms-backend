<?php

class LoanController
{
    private $loanModel;
    private $installmentModel;

    public function __construct()
    {
        $this->loanModel = new Loan();
        $this->installmentModel = new LoanInstallment();
    }

    public function index()
    {
        try {
            $filters = [];
            
            if (Request::has('employee_id')) {
                $filters['employee_id'] = Request::input('employee_id');
            }
            
            if (Request::has('status')) {
                $filters['status'] = Request::input('status');
            }

            $loans = $this->loanModel->getAll($filters);
            
            // Add calculated fields
            foreach ($loans as &$loan) {
                $loan['total_paid'] = $this->loanModel->getTotalPaid($loan['loan_id']);
                $loan['remaining_balance'] = $loan['repayment_amount'] - $loan['total_paid'];
            }

            Response::json([
                'success' => true,
                'data' => $loans
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch loans: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $loan['installments'] = $this->installmentModel->findByLoan($id);
            $loan['total_paid'] = $this->loanModel->getTotalPaid($id);
            $loan['remaining_balance'] = $loan['repayment_amount'] - $loan['total_paid'];

            Response::json([
                'success' => true,
                'data' => $loan
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch loan: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'employee_id' => 'required|string',
                'permission_by' => 'required|string',
                'amount' => 'required|numeric',
                'interest_rate' => 'required|numeric',
                'installment' => 'required|integer',
                'installment_period' => 'required|string',
                'repayment_amount' => 'required|numeric',
                'date_of_approve' => 'required|date',
                'repayment_start_date' => 'required|date'
            ])->validate();

            $loanData = [
                'employee_id' => $data['employee_id'],
                'permission_by' => $data['permission_by'],
                'loan_details' => $data['loan_details'] ?? null,
                'amount' => $data['amount'],
                'interest_rate' => $data['interest_rate'],
                'installment' => $data['installment'],
                'installment_period' => $data['installment_period'],
                'repayment_amount' => $data['repayment_amount'],
                'date_of_approve' => $data['date_of_approve'],
                'loan_status' => $data['loan_status'] ?? Loan::STATUS_PENDING,
                'repayment_start_date' => $data['repayment_start_date']
            ];

            $loanId = $this->loanModel->create($loanData);

            Response::json([
                'success' => true,
                'message' => 'Loan application created successfully',
                'data' => ['id' => $loanId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create loan: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $data = Request::all();
            $this->loanModel->update($id, $data);

            Response::json([
                'success' => true,
                'message' => 'Loan updated successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update loan: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $this->loanModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Loan deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete loan: ' . $e->getMessage(), 500);
        }
    }

    public function approve($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $user = Auth::user();
            $this->loanModel->approve($id, $user['id']);

            Response::json([
                'success' => true,
                'message' => 'Loan approved successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to approve loan: ' . $e->getMessage(), 500);
        }
    }

    public function reject($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $this->loanModel->reject($id);

            Response::json([
                'success' => true,
                'message' => 'Loan rejected'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to reject loan: ' . $e->getMessage(), 500);
        }
    }

    public function installments($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $installments = $this->installmentModel->findByLoan($id);

            Response::json([
                'success' => true,
                'data' => $installments
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch installments: ' . $e->getMessage(), 500);
        }
    }

    public function recordInstallment($id)
    {
        try {
            $loan = $this->loanModel->findById($id);

            if (!$loan) {
                Response::notFound('Loan not found');
            }

            $data = Request::all();

            Validator::make($data, [
                'installment_amount' => 'required|numeric',
                'payment' => 'required|numeric',
                'date' => 'required|date'
            ])->validate();

            $user = Auth::user();
            $installmentNo = $this->installmentModel->getNextInstallmentNo($id);

            $installmentData = [
                'loan_id' => $id,
                'employee_id' => $loan['employee_id'],
                'installment_amount' => $data['installment_amount'],
                'payment' => $data['payment'],
                'date' => $data['date'],
                'received_by' => $user['id'],
                'installment_no' => $installmentNo
            ];

            $installmentId = $this->installmentModel->create($installmentData);

            // Check if loan is completed
            $totalPaid = $this->loanModel->getTotalPaid($id);
            if ($totalPaid >= $loan['repayment_amount']) {
                $this->loanModel->update($id, ['loan_status' => Loan::STATUS_COMPLETED]);
            }

            Response::json([
                'success' => true,
                'message' => 'Installment recorded successfully',
                'data' => ['id' => $installmentId, 'installment_no' => $installmentNo]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to record installment: ' . $e->getMessage(), 500);
        }
    }

    public function summary()
    {
        try {
            $summary = $this->loanModel->getSummary();

            Response::json([
                'success' => true,
                'data' => $summary
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate summary: ' . $e->getMessage(), 500);
        }
    }
}

