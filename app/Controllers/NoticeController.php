<?php

class NoticeController
{
    private $noticeModel;

    public function __construct()
    {
        $this->noticeModel = new Notice();
    }

    public function index()
    {
        try {
            $filters = [];
            
            if (Request::has('status')) {
                $filters['status'] = Request::input('status');
            }
            
            if (Request::has('active_only')) {
                $filters['active_only'] = Request::input('active_only');
            }

            $notices = $this->noticeModel->getAll($filters);

            Response::json([
                'success' => true,
                'data' => $notices
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch notices: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();

            Validator::make($data, [
                'title' => 'required|string|max:200',
                'description' => 'required|string',
                'priority' => 'string',
                'expire_date' => 'date',
            ])->validate();

            $user = Auth::user();

            $noticeData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'normal',
                'notice_date' => today(),
                'expire_date' => $data['expire_date'] ?? null,
                'posted_by' => $user['id'],
                'status' => 'active',
            ];

            $noticeId = $this->noticeModel->create($noticeData);

            Response::json([
                'success' => true,
                'message' => 'Notice created successfully',
                'data' => ['id' => $noticeId]
            ], 201);
        } catch (Exception $e) {
            Response::error('Failed to create notice: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $notice = $this->noticeModel->findById($id);

            if (!$notice) {
                Response::notFound('Notice not found');
            }

            $this->noticeModel->delete($id);

            Response::json([
                'success' => true,
                'message' => 'Notice deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete notice: ' . $e->getMessage(), 500);
        }
    }
}

