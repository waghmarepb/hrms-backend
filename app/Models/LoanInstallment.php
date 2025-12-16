<?php

class LoanInstallment
{
    private $db;
    private $table = 'loan_installment';
    private $primaryKey = 'loan_inst_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    li.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                    CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name) as receiver_name
                FROM {$this->table} li
                LEFT JOIN employee_history e ON li.employee_id = e.employee_id
                LEFT JOIN employee_history r ON li.received_by = r.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['loan_id'])) {
            $sql .= " AND li.loan_id = ?";
            $params[] = $filters['loan_id'];
        }
        
        if (isset($filters['employee_id'])) {
            $sql .= " AND li.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        $sql .= " ORDER BY li.date DESC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    li.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                    CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name) as receiver_name
                FROM {$this->table} li
                LEFT JOIN employee_history e ON li.employee_id = e.employee_id
                LEFT JOIN employee_history r ON li.received_by = r.employee_id
                WHERE li.{$this->primaryKey} = ?
                LIMIT 1";
        
        return $this->db->selectOne($sql, [$id]);
    }

    public function findByLoan($loanId)
    {
        return $this->getAll(['loan_id' => $loanId]);
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

    public function getNextInstallmentNo($loanId)
    {
        $result = $this->db->selectOne(
            "SELECT MAX(installment_no) as max_no FROM {$this->table} WHERE loan_id = ?",
            [$loanId]
        );
        
        return ($result['max_no'] ?? 0) + 1;
    }
}

