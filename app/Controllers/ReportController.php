<?php

class ReportController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function dashboard()
    {
        try {
            // Get dashboard statistics
            $stats = [
                'total_employees' => $this->getTotalEmployees(),
                'total_departments' => $this->getTotalDepartments(),
                'pending_leaves' => $this->getPendingLeaves(),
                'present_today' => $this->getPresentToday(),
                'total_notices' => $this->getTotalNotices(),
                'open_positions' => $this->getOpenPositions(),
            ];

            Response::json([
                'success' => true,
                'data' => $stats
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch dashboard data: ' . $e->getMessage(), 500);
        }
    }

    private function getTotalEmployees()
    {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM employee_history");
        return $result['count'] ?? 0;
    }

    private function getTotalDepartments()
    {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM department");
        return $result['count'] ?? 0;
    }

    private function getPendingLeaves()
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM leave_apply 
             WHERE approved_by IS NULL"
        );
        return $result['count'] ?? 0;
    }

    private function getPresentToday()
    {
        $today = date('Y-m-d');
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM attendance_history 
             WHERE date = ? AND status = 'Present'",
            [$today]
        );
        return $result['count'] ?? 0;
    }

    private function getTotalNotices()
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM notice_board 
             WHERE status = 'active' AND expire_date >= CURDATE()"
        );
        return $result['count'] ?? 0;
    }

    private function getOpenPositions()
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM recruitment 
             WHERE status = 'open'"
        );
        return $result['count'] ?? 0;
    }

    public function employeeReport()
    {
        try {
            $sql = "SELECT 
                        e.employee_id,
                        CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as full_name,
                        e.email,
                        e.phone,
                        d.department_name,
                        p.position_name,
                        e.hire_date
                    FROM employee_history e
                    LEFT JOIN department d ON e.dept_id = d.dept_id
                    LEFT JOIN position p ON e.pos_id = p.pos_id
                    ORDER BY e.first_name";
            
            $employees = $this->db->select($sql);

            Response::json([
                'success' => true,
                'data' => $employees
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate employee report: ' . $e->getMessage(), 500);
        }
    }

    public function attendanceReport()
    {
        try {
            $fromDate = Request::input('from_date', date('Y-m-01'));
            $toDate = Request::input('to_date', date('Y-m-d'));

            $sql = "SELECT 
                        e.employee_id,
                        CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                        COUNT(a.id) as total_days,
                        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
                        SUM(a.work_hours) as total_hours
                    FROM employee_history e
                    LEFT JOIN attendance_history a ON e.employee_id = a.employee_id 
                        AND a.date BETWEEN ? AND ?
                    GROUP BY e.employee_id, employee_name";
            
            $report = $this->db->select($sql, [$fromDate, $toDate]);

            Response::json([
                'success' => true,
                'data' => $report,
                'filters' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate
                ]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate attendance report: ' . $e->getMessage(), 500);
        }
    }

    public function leaveReport()
    {
        try {
            $year = Request::input('year', date('Y'));

            $sql = "SELECT 
                        e.employee_id,
                        CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                        COUNT(l.leave_appl_id) as total_applications,
                        SUM(CASE WHEN l.approved_by IS NOT NULL THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN l.approved_by IS NULL THEN 1 ELSE 0 END) as pending,
                        SUM(l.apply_day) as total_days
                    FROM employee_history e
                    LEFT JOIN leave_apply l ON e.employee_id = l.employee_id 
                        AND YEAR(l.apply_date) = ?
                    GROUP BY e.employee_id, employee_name";
            
            $report = $this->db->select($sql, [$year]);

            Response::json([
                'success' => true,
                'data' => $report,
                'filters' => ['year' => $year]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate leave report: ' . $e->getMessage(), 500);
        }
    }

    public function payrollReport()
    {
        try {
            $month = Request::input('month', date('m'));
            $year = Request::input('year', date('Y'));

            $sql = "SELECT 
                        p.*,
                        CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as employee_name,
                        d.department_name
                    FROM payroll p
                    LEFT JOIN employee_history e ON p.employee_id = e.employee_id
                    LEFT JOIN department d ON e.dept_id = d.dept_id
                    WHERE p.month = ? AND p.year = ?
                    ORDER BY e.first_name";
            
            $report = $this->db->select($sql, [$month, $year]);

            // Calculate totals
            $totals = [
                'total_basic_salary' => array_sum(array_column($report, 'basic_salary')),
                'total_allowances' => array_sum(array_column($report, 'allowances')),
                'total_deductions' => array_sum(array_column($report, 'deductions')),
                'total_tax' => array_sum(array_column($report, 'tax')),
                'total_net_salary' => array_sum(array_column($report, 'net_salary')),
            ];

            Response::json([
                'success' => true,
                'data' => $report,
                'totals' => $totals,
                'filters' => [
                    'month' => $month,
                    'year' => $year
                ]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate payroll report: ' . $e->getMessage(), 500);
        }
    }
}

