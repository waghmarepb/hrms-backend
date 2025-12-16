<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Templates",
 *     description="Email and document template management endpoints"
 * )
 */
class TemplateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/templates",
     *     summary="Get all templates",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type (email, document, sms, notification)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (0=Inactive, 1=Active)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or subject",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of templates")
     * )
     */
    public function index(Request $request)
    {
        $query = Template::orderBy('template_id', 'DESC');

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $templates = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/templates/{id}",
     *     summary="Get template details",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Template details"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function show($id)
    {
        $template = Template::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/templates",
     *     summary="Create template",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"template_name","template_subject","template_body","template_type"},
     *             @OA\Property(property="template_name", type="string", example="Welcome Email"),
     *             @OA\Property(property="template_subject", type="string", example="Welcome to {company_name}"),
     *             @OA\Property(property="template_body", type="string", example="Hello {employee_name}, Welcome to our company!"),
     *             @OA\Property(property="template_type", type="string", example="email"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Template created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255|unique:email_template,template_name',
            'template_subject' => 'required|string|max:500',
            'template_body' => 'required|string',
            'template_type' => 'required|string|in:email,document,sms,notification',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $template = Template::create([
            'template_name' => $request->template_name,
            'template_subject' => $request->template_subject,
            'template_body' => $request->template_body,
            'template_type' => $request->template_type,
            'status' => $request->status ?? 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/templates/{id}",
     *     summary="Update template",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="template_name", type="string"),
     *             @OA\Property(property="template_subject", type="string"),
     *             @OA\Property(property="template_body", type="string"),
     *             @OA\Property(property="template_type", type="string"),
     *             @OA\Property(property="status", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Template updated"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $template = Template::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255|unique:email_template,template_name,' . $id . ',template_id',
            'template_subject' => 'required|string|max:500',
            'template_body' => 'required|string',
            'template_type' => 'required|string|in:email,document,sms,notification',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $template->update([
            'template_name' => $request->template_name,
            'template_subject' => $request->template_subject,
            'template_body' => $request->template_body,
            'template_type' => $request->template_type,
            'status' => $request->status ?? $template->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/templates/{id}",
     *     summary="Delete template",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Template deleted"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function destroy($id)
    {
        $template = Template::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/templates/{id}/render",
     *     summary="Render template with variables",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"variables"},
     *             @OA\Property(
     *                 property="variables",
     *                 type="object",
     *                 example={"employee_name": "John Doe", "company_name": "ABC Corp"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rendered template")
     * )
     */
    public function render(Request $request, $id)
    {
        $template = Template::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'variables' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rendered = $template->render($request->variables);

        return response()->json([
            'success' => true,
            'data' => [
                'template_id' => $template->template_id,
                'template_name' => $template->template_name,
                'rendered_subject' => $rendered['subject'],
                'rendered_body' => $rendered['body'],
                'variables_used' => $request->variables
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/templates/active",
     *     summary="Get active templates",
     *     tags={"Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of active templates")
     * )
     */
    public function active()
    {
        $templates = Template::active()->orderBy('template_name')->get();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }
}


