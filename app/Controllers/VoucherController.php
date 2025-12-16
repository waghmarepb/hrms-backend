<?php

class VoucherController
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
     * Get all vouchers
     * GET /api/vouchers
     */
    public function index()
    {
        $filters = [
            'vtype' => $this->request->query('vtype'),
            'from_date' => $this->request->query('from_date'),
            'to_date' => $this->request->query('to_date'),
            'is_approved' => $this->request->query('is_approved'),
        ];

        // Remove null values
        $filters = array_filter($filters, function($value) {
            return $value !== null;
        });

        $vouchers = $this->transactionModel->getVouchersSummary($filters);

        Response::json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    /**
     * Get voucher details
     * GET /api/vouchers/{voucherNo}
     */
    public function show($voucherNo)
    {
        $transactions = $this->transactionModel->getByVoucherNo($voucherNo, true);

        if (empty($transactions)) {
            Response::json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
            return;
        }

        $firstTrans = $transactions[0];
        $totalDebit = array_sum(array_column($transactions, 'Debit'));
        $totalCredit = array_sum(array_column($transactions, 'Credit'));

        $voucher = [
            'voucher_no' => $voucherNo,
            'voucher_type' => $firstTrans['Vtype'],
            'voucher_date' => $firstTrans['VDate'],
            'is_approved' => $firstTrans['IsAppove'],
            'is_posted' => $firstTrans['IsPosted'],
            'narration' => $firstTrans['Narration'],
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'transactions' => $transactions
        ];

        Response::json([
            'success' => true,
            'data' => $voucher
        ]);
    }

    /**
     * Create debit voucher
     * POST /api/vouchers/debit
     */
    public function createDebitVoucher()
    {
        $rules = [
            'voucher_date' => 'required|date',
            'debit_account.account_code' => 'required',
            'debit_account.amount' => 'required|numeric|min:0.01',
            'credit_accounts' => 'required|array',
            'narration' => 'string'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        // Validate debit = credit
        $debitAmount = $this->request->input('debit_account')['amount'];
        $creditAccounts = $this->request->input('credit_accounts');
        $creditTotal = array_sum(array_column($creditAccounts, 'amount'));

        if (abs($debitAmount - $creditTotal) > 0.01) {
            Response::json([
                'success' => false,
                'message' => 'Debit and Credit amounts must be equal',
                'debit' => $debitAmount,
                'credit' => $creditTotal
            ], 422);
            return;
        }

        try {
            $this->db->beginTransaction();

            $voucherNo = $this->transactionModel->generateVoucherNumber(AccountTransaction::VTYPE_DEBIT);
            $narration = $this->request->input('narration') ?? 'Debit Voucher';
            $userId = $this->request->user['user_id'];

            // Create debit entry
            $this->transactionModel->create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_DEBIT,
                'VDate' => $this->request->input('voucher_date'),
                'COAID' => $this->request->input('debit_account')['account_code'],
                'Narration' => $narration,
                'Debit' => $debitAmount,
                'Credit' => 0,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => $userId
            ]);

            // Create credit entries
            foreach ($creditAccounts as $creditAccount) {
                $this->transactionModel->create([
                    'VNo' => $voucherNo,
                    'Vtype' => AccountTransaction::VTYPE_DEBIT,
                    'VDate' => $this->request->input('voucher_date'),
                    'COAID' => $creditAccount['account_code'],
                    'Narration' => $creditAccount['narration'] ?? $narration,
                    'Debit' => 0,
                    'Credit' => $creditAccount['amount'],
                    'IsPosted' => 1,
                    'IsAppove' => 0,
                    'CreateBy' => $userId
                ]);
            }

            $this->db->commit();

            Response::json([
                'success' => true,
                'message' => 'Debit voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::json([
                'success' => false,
                'message' => 'Failed to create debit voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create credit voucher
     * POST /api/vouchers/credit
     */
    public function createCreditVoucher()
    {
        $rules = [
            'voucher_date' => 'required|date',
            'debit_accounts' => 'required|array',
            'credit_account.account_code' => 'required',
            'credit_account.amount' => 'required|numeric|min:0.01',
            'narration' => 'string'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        // Validate debit = credit
        $debitAccounts = $this->request->input('debit_accounts');
        $debitTotal = array_sum(array_column($debitAccounts, 'amount'));
        $creditAmount = $this->request->input('credit_account')['amount'];

        if (abs($debitTotal - $creditAmount) > 0.01) {
            Response::json([
                'success' => false,
                'message' => 'Debit and Credit amounts must be equal',
                'debit' => $debitTotal,
                'credit' => $creditAmount
            ], 422);
            return;
        }

        try {
            $this->db->beginTransaction();

            $voucherNo = $this->transactionModel->generateVoucherNumber(AccountTransaction::VTYPE_CREDIT);
            $narration = $this->request->input('narration') ?? 'Credit Voucher';
            $userId = $this->request->user['user_id'];

            // Create debit entries
            foreach ($debitAccounts as $debitAccount) {
                $this->transactionModel->create([
                    'VNo' => $voucherNo,
                    'Vtype' => AccountTransaction::VTYPE_CREDIT,
                    'VDate' => $this->request->input('voucher_date'),
                    'COAID' => $debitAccount['account_code'],
                    'Narration' => $debitAccount['narration'] ?? $narration,
                    'Debit' => $debitAccount['amount'],
                    'Credit' => 0,
                    'IsPosted' => 1,
                    'IsAppove' => 0,
                    'CreateBy' => $userId
                ]);
            }

            // Create credit entry
            $this->transactionModel->create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_CREDIT,
                'VDate' => $this->request->input('voucher_date'),
                'COAID' => $this->request->input('credit_account')['account_code'],
                'Narration' => $narration,
                'Debit' => 0,
                'Credit' => $creditAmount,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => $userId
            ]);

            $this->db->commit();

            Response::json([
                'success' => true,
                'message' => 'Credit voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::json([
                'success' => false,
                'message' => 'Failed to create credit voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create contra voucher
     * POST /api/vouchers/contra
     */
    public function createContraVoucher()
    {
        $rules = [
            'voucher_date' => 'required|date',
            'debit_account' => 'required',
            'credit_account' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'narration' => 'string'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        try {
            $this->db->beginTransaction();

            $voucherNo = $this->transactionModel->generateVoucherNumber(AccountTransaction::VTYPE_CONTRA);
            $narration = $this->request->input('narration') ?? 'Contra Voucher';
            $userId = $this->request->user['user_id'];
            $amount = $this->request->input('amount');

            // Create debit entry
            $this->transactionModel->create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_CONTRA,
                'VDate' => $this->request->input('voucher_date'),
                'COAID' => $this->request->input('debit_account'),
                'Narration' => $narration,
                'Debit' => $amount,
                'Credit' => 0,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => $userId
            ]);

            // Create credit entry
            $this->transactionModel->create([
                'VNo' => $voucherNo,
                'Vtype' => AccountTransaction::VTYPE_CONTRA,
                'VDate' => $this->request->input('voucher_date'),
                'COAID' => $this->request->input('credit_account'),
                'Narration' => $narration,
                'Debit' => 0,
                'Credit' => $amount,
                'IsPosted' => 1,
                'IsAppove' => 0,
                'CreateBy' => $userId
            ]);

            $this->db->commit();

            Response::json([
                'success' => true,
                'message' => 'Contra voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::json([
                'success' => false,
                'message' => 'Failed to create contra voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create journal voucher
     * POST /api/vouchers/journal
     */
    public function createJournalVoucher()
    {
        $rules = [
            'voucher_date' => 'required|date',
            'entries' => 'required|array',
            'narration' => 'string'
        ];

        $validator = new Validator();
        $errors = $validator->validate($this->request->all(), $rules);

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $entries = $this->request->input('entries');
        
        // Validate debit = credit
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($entries as $entry) {
            $totalDebit += $entry['debit'] ?? 0;
            $totalCredit += $entry['credit'] ?? 0;
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            Response::json([
                'success' => false,
                'message' => 'Total debit and credit must be equal',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit
            ], 422);
            return;
        }

        try {
            $this->db->beginTransaction();

            $voucherNo = $this->transactionModel->generateVoucherNumber(AccountTransaction::VTYPE_JOURNAL);
            $narration = $this->request->input('narration') ?? 'Journal Voucher';
            $userId = $this->request->user['user_id'];

            foreach ($entries as $entry) {
                $this->transactionModel->create([
                    'VNo' => $voucherNo,
                    'Vtype' => AccountTransaction::VTYPE_JOURNAL,
                    'VDate' => $this->request->input('voucher_date'),
                    'COAID' => $entry['account_code'],
                    'Narration' => $entry['narration'] ?? $narration,
                    'Debit' => $entry['debit'] ?? 0,
                    'Credit' => $entry['credit'] ?? 0,
                    'IsPosted' => 1,
                    'IsAppove' => 0,
                    'CreateBy' => $userId
                ]);
            }

            $this->db->commit();

            Response::json([
                'success' => true,
                'message' => 'Journal voucher created successfully',
                'voucher_no' => $voucherNo
            ], 201);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::json([
                'success' => false,
                'message' => 'Failed to create journal voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve voucher
     * PUT /api/vouchers/{voucherNo}/approve
     */
    public function approve($voucherNo)
    {
        $transactions = $this->transactionModel->getByVoucherNo($voucherNo, false);

        if (empty($transactions)) {
            Response::json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
            return;
        }

        $this->transactionModel->updateByVoucherNo($voucherNo, [
            'IsAppove' => 1,
            'UpdateBy' => $this->request->user['user_id']
        ]);

        Response::json([
            'success' => true,
            'message' => 'Voucher approved successfully'
        ]);
    }

    /**
     * Delete voucher
     * DELETE /api/vouchers/{voucherNo}
     */
    public function destroy($voucherNo)
    {
        $transactions = $this->transactionModel->getByVoucherNo($voucherNo, false);

        if (empty($transactions)) {
            Response::json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
            return;
        }

        // Check if approved
        if ($transactions[0]['IsAppove']) {
            Response::json([
                'success' => false,
                'message' => 'Cannot delete approved voucher'
            ], 422);
            return;
        }

        $this->transactionModel->deleteByVoucherNo($voucherNo);

        Response::json([
            'success' => true,
            'message' => 'Voucher deleted successfully'
        ]);
    }
}

