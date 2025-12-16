<?php

class AssetAssignment
{
    private $db;
    private $table = 'employee_equipment';
    private $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    ea.*,
                    e.equipment_name,
                    t.type_name,
                    CONCAT(emp.first_name, ' ', IFNULL(emp.middle_name, ''), ' ', emp.last_name) as employee_name
                FROM {$this->table} ea
                LEFT JOIN equipment e ON ea.equipment_id = e.equipment_id
                LEFT JOIN equipment_type t ON e.type_id = t.type_id
                LEFT JOIN employee_history emp ON ea.employee_id = emp.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['employee_id'])) {
            $sql .= " AND ea.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (isset($filters['equipment_id'])) {
            $sql .= " AND ea.equipment_id = ?";
            $params[] = $filters['equipment_id'];
        }
        
        if (isset($filters['active_only'])) {
            $sql .= " AND (ea.return_date IS NULL OR ea.return_date = '' OR ea.return_date = '0000-00-00')";
        }
        
        $sql .= " ORDER BY ea.issue_date DESC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1",
            [$id]
        );
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

    public function getEmployeeAssets($employeeId)
    {
        return $this->getAll(['employee_id' => $employeeId, 'active_only' => true]);
    }

    public function getEmployeeHistory($employeeId)
    {
        return $this->getAll(['employee_id' => $employeeId]);
    }
}

