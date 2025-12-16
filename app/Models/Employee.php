<?php

class Employee
{
    private $db;
    private $table = 'employee_history';
    private $primaryKey = 'emp_his_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        $sql = "SELECT 
                    e.*,
                    d.department_name,
                    p.position_name
                FROM {$this->table} e
                LEFT JOIN department d ON e.dept_id = d.dept_id
                LEFT JOIN position p ON e.pos_id = p.pos_id
                ORDER BY e.first_name ASC";
        
        $results = $this->db->select($sql);
        
        return array_map(function($row) {
            return $this->formatEmployee($row);
        }, $results);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    e.*,
                    d.department_name,
                    p.position_name
                FROM {$this->table} e
                LEFT JOIN department d ON e.dept_id = d.dept_id
                LEFT JOIN position p ON e.pos_id = p.pos_id
                WHERE e.{$this->primaryKey} = ?
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

    public function getFullName($employee)
    {
        return trim(
            ($employee['first_name'] ?? '') . ' ' .
            ($employee['middle_name'] ?? '') . ' ' .
            ($employee['last_name'] ?? '')
        );
    }

    private function formatEmployee($row)
    {
        return [
            'id' => $row['emp_his_id'],
            'employee_id' => $row['employee_id'],
            'full_name' => $this->getFullName($row),
            'fullName' => $this->getFullName($row),
            'email' => $row['email'],
            'phone' => $row['phone'],
            'designation' => $row['position_name'] ?? 'Employee',
            'department_name' => $row['department_name'] ?? 'No Department',
            'departmentName' => $row['department_name'] ?? 'No Department',
            'hire_date' => $row['hire_date'],
            'status' => 'active',
            'picture' => $row['picture'],
        ];
    }

    public function formatDetailedEmployee($row)
    {
        if (!$row) {
            return null;
        }
        
        return [
            'id' => $row['emp_his_id'],
            'employee_id' => $row['employee_id'],
            'full_name' => $this->getFullName($row),
            'fullName' => $this->getFullName($row),
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'alter_phone' => $row['alter_phone'],
            'designation' => $row['position_name'] ?? 'Employee',
            'department_name' => $row['department_name'] ?? 'No Department',
            'departmentName' => $row['department_name'] ?? 'No Department',
            'dept_id' => $row['dept_id'],
            'pos_id' => $row['pos_id'],
            'present_address' => $row['present_address'],
            'permanent_address' => $row['parmanent_address'],
            'hire_date' => $row['hire_date'],
            'dob' => $row['dob'],
            'gender' => $row['gender'],
            'marital_status' => $row['marital_status'],
            'status' => 'active',
            'picture' => $row['picture'],
        ];
    }
}

