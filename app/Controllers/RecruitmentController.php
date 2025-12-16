<?php

class RecruitmentController
{
    private $jobModel;
    private $applicationModel;

    public function __construct()
    {
        $this->jobModel = new Job();
        $this->applicationModel = new JobApplication();
    }

    public function jobs()
    {
        try {
            $filters = [];
            
            if (Request::has('status')) {
                $filters['status'] = Request::input('status');
            }

            $jobs = $this->jobModel->getAll($filters);

            Response::json([
                'success' => true,
                'data' => $jobs
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch jobs: ' . $e->getMessage(), 500);
        }
    }

    public function createJob()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'position' => 'required|string|max:200',
                'description' => 'required|string',
                'requirements' => 'string',
                'location' => 'string',
                'salary_range' => 'string',
                'employment_type' => 'string',
                'closing_date' => 'date',
            ])->validate();

            $user = Auth::user();

            $jobData = [
                'position' => $data['position'],
                'description' => $data['description'],
                'requirements' => $data['requirements'] ?? null,
                'location' => $data['location'] ?? null,
                'salary_range' => $data['salary_range'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'closing_date' => $data['closing_date'] ?? null,
                'posted_date' => today(),
                'posted_by' => $user['id'],
                'status' => 'open',
            ];

            $jobId = $this->jobModel->create($jobData);

            Response::json([
                'success' => true,
                'message' => 'Job posting created',
                'data' => ['id' => $jobId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create job: ' . $e->getMessage(), 500);
        }
    }

    public function jobApplications($id)
    {
        try {
            $job = $this->jobModel->findById($id);

            if (!$job) {
                Response::notFound('Job not found');
            }

            $applications = $this->jobModel->getApplications($id);

            Response::json([
                'success' => true,
                'data' => [
                    'job' => $job,
                    'applications' => $applications
                ]
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch applications: ' . $e->getMessage(), 500);
        }
    }

    public function apply($id)
    {
        try {
            $job = $this->jobModel->findById($id);

            if (!$job) {
                Response::notFound('Job not found');
            }

            if ($job['status'] !== 'open') {
                Response::error('This job is no longer accepting applications', 400);
            }

            $data = Request::all();

            Validator::make($data, [
                'full_name' => 'required|string|max:200',
                'email' => 'required|email',
                'phone' => 'required|string',
                'resume' => 'string',
                'cover_letter' => 'string',
            ])->validate();

            $applicationData = [
                'recruitment_id' => $id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'resume' => $data['resume'] ?? null,
                'cover_letter' => $data['cover_letter'] ?? null,
                'applied_date' => today(),
                'status' => 'pending',
            ];

            $applicationId = $this->applicationModel->create($applicationData);

            Response::json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'data' => ['id' => $applicationId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to submit application: ' . $e->getMessage(), 500);
        }
    }

    public function updateApplicationStatus($id)
    {
        try {
            $application = $this->applicationModel->findById($id);

            if (!$application) {
                Response::notFound('Application not found');
            }

            $data = Request::all();

            Validator::make($data, [
                'status' => 'required|string|in:pending,shortlisted,rejected,hired'
            ])->validate();

            $this->applicationModel->update($id, [
                'status' => $data['status']
            ]);

            Response::json([
                'success' => true,
                'message' => 'Application status updated'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to update status: ' . $e->getMessage(), 500);
        }
    }
}

