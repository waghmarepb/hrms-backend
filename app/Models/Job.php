<?php

class Job
{
    private $db;
    private $table = 'recruitment';
    private $primaryKey = 'recruitment_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    r.*,
                    COUNT(ja.job_application_id) as applications_count
                FROM {$this->table} r
                LEFT JOIN job_application ja ON r.recruitment_id = ja.recruitment_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " GROUP BY r.recruitment_id ORDER BY r.posted_date DESC";
        
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

    public function getApplications($jobId)
    {
        $sql = "SELECT * FROM job_application WHERE recruitment_id = ? ORDER BY applied_date DESC";
        return $this->db->select($sql, [$jobId]);
    }
}

