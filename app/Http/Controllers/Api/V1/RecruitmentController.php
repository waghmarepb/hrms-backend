<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecruitmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/jobs",
     *     tags={"Recruitment"},
     *     summary="Get list of job postings",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (open/closed)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function jobs(Request $request)
    {
        $query = Job::withCount('applications');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->orderBy('posted_date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/jobs",
     *     tags={"Recruitment"},
     *     summary="Create job posting",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"position","description"},
     *             @OA\Property(property="position", type="string", example="Senior Software Engineer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="requirements", type="string"),
     *             @OA\Property(property="location", type="string", example="Mumbai"),
     *             @OA\Property(property="salary_range", type="string", example="10-15 LPA"),
     *             @OA\Property(property="employment_type", type="string", example="Full-time"),
     *             @OA\Property(property="closing_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Job created")
     * )
     */
    public function createJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'position' => 'required|string|max:200',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'location' => 'nullable|string',
            'salary_range' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'closing_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $job = Job::create([
            'position' => $request->position,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'location' => $request->location,
            'salary_range' => $request->salary_range,
            'employment_type' => $request->employment_type,
            'closing_date' => $request->closing_date,
            'posted_date' => now(),
            'posted_by' => $request->user()->id,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job posting created',
            'data' => $job
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/jobs/{id}/applications",
     *     tags={"Recruitment"},
     *     summary="Get applications for a job",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function jobApplications($id)
    {
        $job = Job::with('applications')->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job,
                'applications' => $job->applications
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/jobs/{id}/apply",
     *     tags={"Recruitment"},
     *     summary="Apply for a job",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"applicant_name","email","phone"},
     *             @OA\Property(property="applicant_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="resume", type="string"),
     *             @OA\Property(property="cover_letter", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Application submitted")
     * )
     */
    public function apply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'applicant_name' => 'required|string|max:200',
            'email' => 'required|email',
            'phone' => 'required|string',
            'resume' => 'nullable|string',
            'cover_letter' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $application = JobApplication::create([
            'recruitment_id' => $id,
            'applicant_name' => $request->applicant_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'resume' => $request->resume,
            'cover_letter' => $request->cover_letter,
            'application_date' => now(),
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully',
            'data' => $application
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/applications/{id}/status",
     *     tags={"Recruitment"},
     *     summary="Update application status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="shortlisted"),
     *             @OA\Property(property="interview_date", type="string", format="date-time"),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated")
     * )
     */
    public function updateApplicationStatus(Request $request, $id)
    {
        $application = JobApplication::find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        }

        $application->update($request->only(['status', 'interview_date', 'remarks']));

        return response()->json([
            'success' => true,
            'message' => 'Application status updated',
            'data' => $application
        ]);
    }
}



