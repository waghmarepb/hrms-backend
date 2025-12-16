<?php

class Leave
{
    private $db;
    private $table = 'leave_apply';
    private $primaryKey = 'leave_appl_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        $sql = "SELECT 
                    l.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} l
                LEFT JOIN employee_history e ON l.employee_id = e.employee_id
                ORDER BY l.apply_date DESC";
        
        $results = $this->db->select($sql);
        
        return array_map(function($row) {
            return $this->formatLeave($row);
        }, $results);
    }

    public function findById($id)
    {
        $sql = "SELECT 
                    l.*,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name
                FROM {$this->table} l
                LEFT JOIN employee_history e ON l.employee_id = e.employee_id
                WHERE l.{$this->primaryKey} = ?
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

    public function approve($id, $approvedBy)
    {
        return $this->update($id, [
            'approved_by' => $approvedBy,
            'approve_date' => now()
        ]);
    }

    public function reject($id, $rejectedBy)
    {
        return $this->update($id, [
            'approved_by' => $rejectedBy,
            'approve_date' => now(),
            'leave_type' => 'Rejected'
        ]);
    }

    private function formatLeave($row)
    {
        $status = 'pending';
        if (!empty($row['approved_by']) && !empty($row['approve_date'])) {
            $status = 'approved';
        }
        if (isset($row['leave_type']) && $row['leave_type'] === 'Rejected') {
            $status = 'rejected';
        }

        return [
            'id' => $row['leave_appl_id'],
            'employee_id' => $row['employee_id'],
            'employee_name' => $row['employee_name'] ?? 'Unknown',
            'leave_type' => $row['leave_type'] ?? 'General Leave',
            'start_date' => $row['apply_strt_date'],
            'end_date' => $row['apply_end_date'],
            'days' => $row['apply_day'] ?? 0,
            'reason' => $row['reason'] ?? '',
            'status' => $status,
            'apply_date' => $row['apply_date'],
            'approved_by' => $row['approved_by'],
            'approve_date' => $row['approve_date'],
        ];
    }

    public function calculateDays($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        return $end->diff($start)->days + 1;
    }
}

