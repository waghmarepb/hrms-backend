<?php

class Attendance
{
    private $db;
    private $table = 'attendance_history';
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
        
        if (isset($filters['date'])) {
            $sql .= " AND a.date = ?";
            $params[] = $filters['date'];
        }
        
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $sql .= " AND a.date BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }
        
        $sql .= " ORDER BY a.date DESC, a.time_in DESC";
        
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

    public function findTodayAttendance($employeeId, $date)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE employee_id = ? AND date = ? AND time_out IS NULL
                LIMIT 1";
        
        return $this->db->selectOne($sql, [$employeeId, $date]);
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

    public function calculateWorkHours($timeIn, $timeOut)
    {
        $start = new DateTime($timeIn);
        $end = new DateTime($timeOut);
        $interval = $start->diff($end);
        
        return round($interval->h + ($interval->i / 60), 2);
    }

    public function getReport($filters = [])
    {
        $sql = "SELECT 
                    e.employee_id,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_days,
                    SUM(a.work_hours) as total_hours
                FROM employee_history e
                LEFT JOIN {$this->table} a ON e.employee_id = a.employee_id";
        
        $params = [];
        $where = [];
        
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $where[] = "a.date BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " GROUP BY e.employee_id, employee_name";
        
        return $this->db->select($sql, $params);
    }
}

