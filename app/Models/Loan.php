<?php

class Loan
{
    private $db;
    private $table = 'grand_loan';
    private $primaryKey = 'loan_id';

    // Loan Status Constants
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_COMPLETED = 3;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    l.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                    CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) as supervisor_name
                FROM {$this->table} l
                LEFT JOIN employee_history e ON l.employee_id = e.employee_id
                LEFT JOIN employee_history s ON l.permission_by = s.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['employee_id'])) {
            $sql .= " AND l.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND l.loan_status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY l.loan_id DESC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    l.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                    CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) as supervisor_name
                FROM {$this->table} l
                LEFT JOIN employee_history e ON l.employee_id = e.employee_id
                LEFT JOIN employee_history s ON l.permission_by = s.employee_id
                WHERE l.{$this->primaryKey} = ?
                LIMIT 1";
        
        return $this->db->selectOne($sql, [$id]);
    }

    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        return $this->db->update(
            $this->table,
            $data,
            "WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function delete($id)
    {
        return $this->db->delete(
            $this->table,
            "WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function approve($id, $supervisorId)
    {
        return $this->update($id, [
            'loan_status' => self::STATUS_APPROVED,
            'permission_by' => $supervisorId,
            'date_of_approve' => today()
        ]);
    }

    public function reject($id)
    {
        return $this->update($id, [
            'loan_status' => self::STATUS_REJECTED
        ]);
    }

    public function getTotalPaid($loanId)
    {
        $result = $this->db->selectOne(
            "SELECT SUM(payment) as total FROM loan_installment WHERE loan_id = ?",
            [$loanId]
        );
        return $result['total'] ?? 0;
    }

    public function getRemainingBalance($loanId)
    {
        $loan = $this->findById($loanId);
        if (!$loan) return 0;
        
        $totalPaid = $this->getTotalPaid($loanId);
        return $loan['repayment_amount'] - $totalPaid;
    }

    public function getSummary()
    {
        $sql = "SELECT 
                    COUNT(*) as total_loans,
                    SUM(CASE WHEN loan_status = " . self::STATUS_PENDING . " THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN loan_status = " . self::STATUS_APPROVED . " THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN loan_status = " . self::STATUS_COMPLETED . " THEN 1 ELSE 0 END) as completed,
                    SUM(amount) as total_amount,
                    SUM(repayment_amount) as total_repayment
                FROM {$this->table}";
        
        return $this->db->selectOne($sql);
    }
}

