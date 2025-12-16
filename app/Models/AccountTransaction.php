<?php

class AccountTransaction
{
    private $db;
    protected $table = 'acc_transaction';
    protected $primaryKey = 'ID';

    // Voucher Types Constants
    const VTYPE_DEBIT = 'DV';
    const VTYPE_CREDIT = 'CV';
    const VTYPE_CONTRA = 'ContV';
    const VTYPE_JOURNAL = 'JV';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get vouchers with summary (grouped by VNo)
     */
    public function getVouchersSummary($filters = [])
    {
        $sql = "SELECT VNo, Vtype, VDate, IsAppove, CreateDate,
                       SUM(Debit) as total_debit,
                       SUM(Credit) as total_credit,
                       MAX(Narration) as narration
                FROM {$this->table}
                WHERE 1=1";
        
        $params = [];

        if (isset($filters['vtype'])) {
            $sql .= " AND Vtype = ?";
            $params[] = $filters['vtype'];
        }

        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $sql .= " AND VDate BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }

        if (isset($filters['is_approved'])) {
            $sql .= " AND IsAppove = ?";
            $params[] = $filters['is_approved'];
        }

        $sql .= " GROUP BY VNo, Vtype, VDate, IsAppove, CreateDate";
        $sql .= " ORDER BY VDate DESC, VNo DESC";
        $sql .= " LIMIT 50";

        return $this->db->query($sql, $params);
    }

    /**
     * Get transactions by voucher number
     */
    public function getByVoucherNo($voucherNo, $withAccount = true)
    {
        if ($withAccount) {
            $sql = "SELECT t.*, a.HeadName, a.HeadCode, a.HeadType
                    FROM {$this->table} t
                    LEFT JOIN acc_coa a ON t.COAID = a.HeadCode
                    WHERE t.VNo = ?
                    ORDER BY t.ID";
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE VNo = ? ORDER BY ID";
        }

        return $this->db->query($sql, [$voucherNo]);
    }

    /**
     * Create transaction entry
     */
    public function create($data)
    {
        $data['CreateDate'] = date('Y-m-d H:i:s');
        $data['IsPosted'] = $data['IsPosted'] ?? 1;
        $data['IsAppove'] = $data['IsAppove'] ?? 0;

        return $this->db->table($this->table)->insert($data);
    }

    /**
     * Update transactions by voucher number
     */
    public function updateByVoucherNo($voucherNo, $data)
    {
        $data['UpdateDate'] = date('Y-m-d H:i:s');
        
        return $this->db->table($this->table)
            ->where('VNo', $voucherNo)
            ->update($data);
    }

    /**
     * Delete transactions by voucher number
     */
    public function deleteByVoucherNo($voucherNo)
    {
        return $this->db->table($this->table)
            ->where('VNo', $voucherNo)
            ->delete();
    }

    /**
     * Generate voucher number
     */
    public function generateVoucherNumber($type)
    {
        $result = $this->db->table($this->table)
            ->where('Vtype', $type)
            ->orderBy('VNo', 'DESC')
            ->first();

        if ($result) {
            // Extract number from last voucher and increment
            $lastNo = (int) preg_replace('/\D/', '', $result['VNo']);
            $newNo = $lastNo + 1;
        } else {
            $newNo = 1;
        }

        return $type . '-' . str_pad($newNo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get transactions by date range
     */
    public function getByDateRange($fromDate, $toDate, $coaId = null)
    {
        $query = $this->db->table($this->table)
            ->whereBetween('VDate', [$fromDate, $toDate]);

        if ($coaId) {
            $query->where('COAID', $coaId);
        }

        return $query->orderBy('VDate')->orderBy('VNo')->get();
    }

    /**
     * Get debit/credit summary for an account
     */
    public function getAccountSummary($coaId, $fromDate = null, $toDate = null)
    {
        $sql = "SELECT 
                    SUM(Debit) as total_debit,
                    SUM(Credit) as total_credit,
                    COUNT(*) as transaction_count
                FROM {$this->table}
                WHERE COAID = ?";
        
        $params = [$coaId];

        if ($fromDate && $toDate) {
            $sql .= " AND VDate BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
        }

        $result = $this->db->query($sql, $params);
        return $result[0] ?? null;
    }
}

