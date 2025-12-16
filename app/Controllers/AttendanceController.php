<?php

class AttendanceController
{
    private $attendanceModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/attendance",
     *     tags={"Attendance"},
     *     summary="Get attendance records",
     *     description="Retrieve attendance records with optional filters",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter by specific date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-15")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter from date (use with to_date)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter to date (use with from_date)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance records retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_id", type="integer", example=1),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="clock_in", type="string", format="time", example="09:00:00"),
     *                     @OA\Property(property="clock_out", type="string", format="time", example="17:00:00")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $filters = [];
            
            if (Request::has('employee_id')) {
                $filters['employee_id'] = Request::input('employee_id');
            }
            
            if (Request::has('date')) {
                $filters['date'] = Request::input('date');
            }
            
            if (Request::has('from_date') && Request::has('to_date')) {
                $filters['from_date'] = Request::input('from_date');
                $filters['to_date'] = Request::input('to_date');
            }

            $attendance = $this->attendanceModel->getAll($filters);

            Response::json([
                'success' => true,
                'data' => $attendance
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/attendance/clock-in",
     *     tags={"Attendance"},
     *     summary="Clock in",
     *     description="Record employee clock-in time",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id"},
     *             @OA\Property(property="employee_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Started work")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clock-in successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Clocked in successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     */
    public function clockIn()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'employee_id' => 'required|integer',
            ])->validate();

            $today = date('Y-m-d');
            
            // Check if already clocked in today
            $existing = $this->attendanceModel->findTodayAttendance($data['employee_id'], $today);

            if ($existing) {
                Response::error('Already clocked in today', 400);
            }

            $attendanceData = [
                'employee_id' => $data['employee_id'],
                'date' => $today,
                'time_in' => date('H:i:s'),
                'status' => 'Present',
            ];

            $attendanceId = $this->attendanceModel->create($attendanceData);

            Response::json([
                'success' => true,
                'message' => 'Clocked in successfully',
                'data' => ['id' => $attendanceId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to clock in: ' . $e->getMessage(), 500);
        }
    }

    public function clockOut()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'employee_id' => 'required|integer',
            ])->validate();

            $today = date('Y-m-d');
            
            // Find today's attendance record
            $attendance = $this->attendanceModel->findTodayAttendance($data['employee_id'], $today);

            if (!$attendance) {
                Response::error('No clock-in record found for today', 404);
            }

            $timeOut = date('H:i:s');
            $workHours = $this->attendanceModel->calculateWorkHours(
                $today . ' ' . $attendance['time_in'],
                $today . ' ' . $timeOut
            );

            $this->attendanceModel->update($attendance['id'], [
                'time_out' => $timeOut,
                'work_hours' => $workHours
            ]);

            Response::json([
                'success' => true,
                'message' => 'Clocked out successfully',
                'data' => [
                    'time_out' => $timeOut,
                    'work_hours' => $workHours
                ]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to clock out: ' . $e->getMessage(), 500);
        }
    }

    public function report()
    {
        try {
            $filters = [];
            
            if (Request::has('from_date') && Request::has('to_date')) {
                $filters['from_date'] = Request::input('from_date');
                $filters['to_date'] = Request::input('to_date');
            }

            $report = $this->attendanceModel->getReport($filters);

            Response::json([
                'success' => true,
                'data' => $report
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }
}

