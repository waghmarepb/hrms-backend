<?php

class TaxCollection
{
    private $db;
    private $table = 'tax_collection';
    private $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    tc.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} tc
                LEFT JOIN employee_history e ON tc.employee_id = e.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['employee_id'])) {
            $sql .= " AND tc.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (isset($filters['year'])) {
            $sql .= " AND tc.year = ?";
            $params[] = $filters['year'];
        }
        
        $sql .= " ORDER BY tc.collection_date DESC";
        
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

    public function delete($id)
    {
        return $this->db->delete(
            $this->table,
            "WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function getSummary($filters = [])
    {
        $sql = "SELECT 
                    COUNT(*) as total_collections,
                    SUM(tax_amount) as total_amount
                FROM {$this->table}
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['year'])) {
            $sql .= " AND year = ?";
            $params[] = $filters['year'];
        }
        
        return $this->db->selectOne($sql, $params);
    }
}

