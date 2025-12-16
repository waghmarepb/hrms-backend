<?php

class JobApplication
{
    private $db;
    private $table = 'job_application';
    private $primaryKey = 'job_application_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (isset($filters['recruitment_id'])) {
            $sql .= " AND recruitment_id = ?";
            $params[] = $filters['recruitment_id'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY applied_date DESC";
        
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
}

