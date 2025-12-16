<?php

class Asset
{
    private $db;
    private $table = 'equipment';
    private $primaryKey = 'equipment_id';

    const STATUS_AVAILABLE = 0;
    const STATUS_ASSIGNED = 1;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    e.*,
                    t.type_name
                FROM {$this->table} e
                LEFT JOIN equipment_type t ON e.type_id = t.type_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['is_assign'])) {
            $sql .= " AND e.is_assign = ?";
            $params[] = $filters['is_assign'];
        }
        
        if (isset($filters['type_id'])) {
            $sql .= " AND e.type_id = ?";
            $params[] = $filters['type_id'];
        }
        
        $sql .= " ORDER BY e.equipment_name ASC";
        
        return $this->db->select($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    e.*,
                    t.type_name
                FROM {$this->table} e
                LEFT JOIN equipment_type t ON e.type_id = t.type_id
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

    public function getAvailable()
    {
        return $this->getAll(['is_assign' => self::STATUS_AVAILABLE]);
    }
}

