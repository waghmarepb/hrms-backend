<?php

class Template
{
    private $db;
    private $table = 'template';
    private $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        return $this->db->select("SELECT * FROM {$this->table} ORDER BY template_name ASC");
    }

    public function getActive()
    {
        return $this->db->select("SELECT * FROM {$this->table} WHERE status = 1 ORDER BY template_name ASC");
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

    public function render($templateContent, $data)
    {
        foreach ($data as $key => $value) {
            $templateContent = str_replace('{{' . $key . '}}', $value, $templateContent);
        }
        return $templateContent;
    }
}

