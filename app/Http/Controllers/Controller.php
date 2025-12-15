<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="HRMS REST API",
 *      description="Human Resource Management System API Documentation",
 *      @OA\Contact(
 *          email="admin@hrms.com"
 *      )
 * )
 *
 * @OA\Server(
 *      url="http://localhost:8001",
 *      description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for authentication"
 * )
 *
 * @OA\Tag(
 *     name="Employees",
 *     description="API endpoints for employee management"
 * )
 *
 * @OA\Tag(
 *     name="Departments",
 *     description="API endpoints for department management"
 * )
 *
 * @OA\Tag(
 *     name="Leave Management",
 *     description="API endpoints for leave applications and approvals"
 * )
 *
 * @OA\Tag(
 *     name="Attendance",
 *     description="API endpoints for attendance tracking"
 * )
 *
 * @OA\Tag(
 *     name="Payroll",
 *     description="API endpoints for payroll management"
 * )
 *
 * @OA\Tag(
 *     name="Recruitment",
 *     description="API endpoints for recruitment and job applications"
 * )
 *
 * @OA\Tag(
 *     name="Notices",
 *     description="API endpoints for noticeboard and announcements"
 * )
 *
 * @OA\Tag(
 *     name="Reports",
 *     description="API endpoints for HR reports and analytics"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
