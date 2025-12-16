<?php

class Award
{
    private $db;
    private $table = 'award';
    private $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    a.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} a
                LEFT JOIN employee_history e ON a.employee_id = e.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['employee_id'])) {
            $sql .= " AND a.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        $sql .= " ORDER BY a.award_date DESC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    a.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} a
                LEFT JOIN employee_history e ON a.employee_id = e.employee_id
                WHERE a.{$this->primaryKey} = ?
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

    public function getEmployeeAwards($employeeId)
    {
        return $this->getAll(['employee_id' => $employeeId]);
    }
}

