<?php

class Payroll
{
    private $db;
    private $table = 'payroll';
    private $primaryKey = 'payroll_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    p.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} p
                LEFT JOIN employee_history e ON p.employee_id = e.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['employee_id'])) {
            $sql .= " AND p.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (isset($filters['month'])) {
            $sql .= " AND p.month = ?";
            $params[] = $filters['month'];
        }
        
        if (isset($filters['year'])) {
            $sql .= " AND p.year = ?";
            $params[] = $filters['year'];
        }
        
        $sql .= " ORDER BY p.year DESC, p.month DESC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    p.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} p
                LEFT JOIN employee_history e ON p.employee_id = e.employee_id
                WHERE p.{$this->primaryKey} = ?
                LIMIT 1";
        
        return $this->db->selectOne($sql, [$id]);
    }

    public function findExisting($employeeId, $month, $year)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE employee_id = ? AND month = ? AND year = ?
                LIMIT 1";
        
        return $this->db->selectOne($sql, [$employeeId, $month, $year]);
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

    public function markAsPaid($id)
    {
        return $this->update($id, [
            'status' => 'Paid',
            'payment_date' => now()
        ]);
    }

    public function calculatePayroll($basicSalary)
    {
        $allowances = $basicSalary * 0.1; // 10% allowances
        $deductions = $basicSalary * 0.05; // 5% deductions
        $tax = $basicSalary * 0.1; // 10% tax
        $netSalary = $basicSalary + $allowances - $deductions - $tax;

        return [
            'basic_salary' => round($basicSalary, 2),
            'allowances' => round($allowances, 2),
            'deductions' => round($deductions, 2),
            'tax' => round($tax, 2),
            'net_salary' => round($netSalary, 2)
        ];
    }
}

