<?php

class AssetType
{
    private $db;
    private $table = 'equipment_type';
    private $primaryKey = 'type_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        $sql = "SELECT 
                    t.*,
                    COUNT(e.equipment_id) as equipment_count
                FROM {$this->table} t
                LEFT JOIN equipment e ON t.type_id = e.type_id
                GROUP BY t.type_id
                ORDER BY t.type_name ASC";
        
        return $this->db->select($sql);
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
}

