<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoticeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/notices",
     *     tags={"Notices"},
     *     summary="Get list of notices",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", example="active")
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        $query = Notice::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get active notices (not expired)
        if ($request->get('active_only', false)) {
            $query->where('expire_date', '>=', now())
                  ->where('status', 'active');
        }

        $notices = $query->orderBy('notice_date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notices
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notices",
     *     tags={"Notices"},
     *     summary="Create notice",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","description"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="priority", type="string", example="high"),
     *             @OA\Property(property="expire_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Notice created")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'priority' => 'nullable|string',
            'expire_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $notice = Notice::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->get('priority', 'normal'),
            'notice_date' => now(),
            'expire_date' => $request->expire_date,
            'posted_by' => $request->user()->id,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notice created successfully',
            'data' => $notice
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/notices/{id}",
     *     tags={"Notices"},
     *     summary="Delete notice",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notice deleted")
     * )
     */
    public function destroy($id)
    {
        $notice = Notice::find($id);

        if (!$notice) {
            return response()->json([
                'success' => false,
                'message' => 'Notice not found'
            ], 404);
        }

        $notice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notice deleted successfully'
        ]);
    }
}



