<?php

class Expense
{
    private $db;
    private $table = 'acc_transaction';
    private $primaryKey = 'ID';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    t.*,
                    c.HeadName as category_name
                FROM {$this->table} t
                LEFT JOIN acc_coa c ON t.COAID = c.HeadCode
                WHERE t.Vtype = 'Expense'";
        
        $params = [];
        
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $sql .= " AND t.VDate BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }
        
        if (isset($filters['category'])) {
            $sql .= " AND t.COAID = ?";
            $params[] = $filters['category'];
        }
        
        $sql .= " ORDER BY t.VDate DESC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    t.*,
                    c.HeadName as category_name
                FROM {$this->table} t
                LEFT JOIN acc_coa c ON t.COAID = c.HeadCode
                WHERE t.{$this->primaryKey} = ? AND t.Vtype = 'Expense'
                LIMIT 1";
        
        return $this->db->selectOne($sql, [$id]);
    }

    public function create($data)
    {
        $data['Vtype'] = 'Expense';
        $data['IsPosted'] = 1;
        $data['IsAppove'] = 1;
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
            "WHERE {$this->primaryKey} = ? AND Vtype = 'Expense'",
            [$id]
        );
    }

    public function getStatement($filters = [])
    {
        $sql = "SELECT 
                    DATE_FORMAT(VDate, '%Y-%m') as month,
                    SUM(Debit) as total_expenses
                FROM {$this->table}
                WHERE Vtype = 'Expense'";
        
        $params = [];
        
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $sql .= " AND VDate BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }
        
        $sql .= " GROUP BY month ORDER BY month DESC";
        
        return $this->db->select($sql, $params);
    }

    public function getSummary($filters = [])
    {
        $sql = "SELECT 
                    c.HeadName as category,
                    COUNT(t.ID) as transaction_count,
                    SUM(t.Debit) as total_amount
                FROM {$this->table} t
                LEFT JOIN acc_coa c ON t.COAID = c.HeadCode
                WHERE t.Vtype = 'Expense'";
        
        $params = [];
        
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $sql .= " AND t.VDate BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }
        
        $sql .= " GROUP BY c.HeadName ORDER BY total_amount DESC";
        
        return $this->db->select($sql, $params);
    }

    public function generateVoucherNo()
    {
        $result = $this->db->selectOne(
            "SELECT MAX(CAST(SUBSTRING(VNo, 2) AS UNSIGNED)) as max_no 
             FROM {$this->table} 
             WHERE Vtype = 'Expense' AND VNo LIKE 'E%'"
        );
        
        $nextNo = ($result['max_no'] ?? 0) + 1;
        return 'E' . str_pad($nextNo, 6, '0', STR_PAD_LEFT);
    }
}

