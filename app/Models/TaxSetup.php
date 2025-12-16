<?php

class TaxSetup
{
    private $db;
    private $table = 'tax_setup';
    private $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        return $this->db->select("SELECT * FROM {$this->table} ORDER BY min_amount ASC");
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

    public function calculateTax($income)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ? >= min_amount AND ? <= max_amount 
                LIMIT 1";
        
        $bracket = $this->db->selectOne($sql, [$income, $income]);
        
        if ($bracket) {
            return ($income * $bracket['rate']) / 100;
        }
        
        return 0;
    }
}

