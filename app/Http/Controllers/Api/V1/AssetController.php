<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\AssetAssignment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Assets",
 *     description="Asset/Equipment management endpoints"
 * )
 */
class AssetController extends Controller
{
    // ========== ASSET TYPES ==========

    /**
     * @OA\Get(
     *     path="/api/v1/asset-types",
     *     summary="Get all asset types",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of asset types")
     * )
     */
    public function types()
    {
        $types = AssetType::withCount('equipment')->get();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/asset-types",
     *     summary="Create asset type",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type_name"},
     *             @OA\Property(property="type_name", type="string", example="Laptop")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Asset type created")
     * )
     */
    public function createType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_name' => 'required|string|max:100|unique:equipment_type,type_name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $type = AssetType::create([
            'type_name' => $request->type_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset type created successfully',
            'data' => $type
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/asset-types/{id}",
     *     summary="Get asset type details",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Asset type details")
     * )
     */
    public function showType($id)
    {
        $type = AssetType::with('equipment')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Asset type not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $type
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/asset-types/{id}",
     *     summary="Update asset type",
     *     tags={"Assets"},
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
     *             @OA\Property(property="type_name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Asset type updated")
     * )
     */
    public function updateType(Request $request, $id)
    {
        $type = AssetType::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Asset type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type_name' => 'required|string|max:100|unique:equipment_type,type_name,' . $id . ',type_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $type->update(['type_name' => $request->type_name]);

        return response()->json([
            'success' => true,
            'message' => 'Asset type updated successfully',
            'data' => $type
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/asset-types/{id}",
     *     summary="Delete asset type",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Asset type deleted")
     * )
     */
    public function deleteType($id)
    {
        $type = AssetType::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Asset type not found'
            ], 404);
        }

        // Check if type has equipment
        if ($type->equipment()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete asset type with existing equipment'
            ], 422);
        }

        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset type deleted successfully'
        ]);
    }

    // ========== ASSETS/EQUIPMENT ==========

    /**
     * @OA\Get(
     *     path="/api/v1/assets",
     *     summary="Get all assets",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Filter by type",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_assign",
     *         in="query",
     *         description="Filter by assignment status (0=Available, 1=Assigned)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name, model, or serial number",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of assets")
     * )
     */
    public function index(Request $request)
    {
        $query = Asset::with(['type', 'currentAssignment.employee'])
            ->orderBy('equipment_id', 'DESC');

        // Filter by type
        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Filter by assignment status
        if ($request->has('is_assign')) {
            $query->where('is_assign', $request->is_assign);
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $assets = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/assets/available",
     *     summary="Get available (unassigned) assets",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of available assets")
     * )
     */
    public function available()
    {
        $assets = Asset::with('type')
            ->available()
            ->orderBy('equipment_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/assets/{id}",
     *     summary="Get asset details",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Asset details")
     * )
     */
    public function show($id)
    {
        $asset = Asset::with([
            'type',
            'currentAssignment.employee',
            'assignmentHistory.employee'
        ])->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $asset
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/assets",
     *     summary="Create asset",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"equipment_name","type_id"},
     *             @OA\Property(property="equipment_name", type="string", example="Dell Latitude 5420"),
     *             @OA\Property(property="type_id", type="integer", example=1),
     *             @OA\Property(property="model", type="string", example="Latitude 5420"),
     *             @OA\Property(property="serial_no", type="string", example="SN123456789")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Asset created")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipment_name' => 'required|string|max:100',
            'type_id' => 'required|exists:equipment_type,type_id',
            'model' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100|unique:equipment,serial_no'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $asset = Asset::create([
            'equipment_name' => $request->equipment_name,
            'type_id' => $request->type_id,
            'model' => $request->model ?? '',
            'serial_no' => $request->serial_no ?? '',
            'is_assign' => Asset::STATUS_AVAILABLE
        ]);

        $asset->load('type');

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => $asset
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/assets/{id}",
     *     summary="Update asset",
     *     tags={"Assets"},
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
     *             @OA\Property(property="equipment_name", type="string"),
     *             @OA\Property(property="type_id", type="integer"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="serial_no", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Asset updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'equipment_name' => 'required|string|max:100',
            'type_id' => 'required|exists:equipment_type,type_id',
            'model' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100|unique:equipment,serial_no,' . $id . ',equipment_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $asset->update($request->only([
            'equipment_name', 'type_id', 'model', 'serial_no'
        ]));

        $asset->load('type');

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => $asset
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/assets/{id}",
     *     summary="Delete asset",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Asset deleted")
     * )
     */
    public function destroy($id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        // Check if asset is currently assigned
        if ($asset->is_assign == Asset::STATUS_ASSIGNED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete assigned asset. Return it first.'
            ], 422);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    }

    // ========== ASSET ASSIGNMENTS ==========

    /**
     * @OA\Get(
     *     path="/api/v1/asset-assignments",
     *     summary="Get all asset assignments",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (active/returned)",
     *         @OA\Schema(type="string", enum={"active", "returned"})
     *     ),
     *     @OA\Response(response=200, description="List of assignments")
     * )
     */
    public function assignments(Request $request)
    {
        $query = AssetAssignment::with(['asset.type', 'employee'])
            ->orderBy('id', 'DESC');

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status == 'active') {
                $query->active();
            } elseif ($request->status == 'returned') {
                $query->returned();
            }
        }

        $assignments = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/asset-assignments",
     *     summary="Assign asset(s) to employee",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","equipment_ids"},
     *             @OA\Property(property="employee_id", type="string", example="EMP001"),
     *             @OA\Property(property="equipment_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}),
     *             @OA\Property(property="issue_dates", type="array", @OA\Items(type="string", format="date"), example={"2025-12-15", "2025-12-15"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Assets assigned")
     * )
     */
    public function assignAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string|exists:employee_history,employee_id',
            'equipment_ids' => 'required|array|min:1',
            'equipment_ids.*' => 'required|exists:equipment,equipment_id',
            'issue_dates' => 'nullable|array',
            'issue_dates.*' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $equipmentIds = $request->equipment_ids;
            $issueDates = $request->issue_dates ?? [];
            $assignments = [];

            foreach ($equipmentIds as $index => $equipmentId) {
                // Check if asset is already assigned
                $asset = Asset::find($equipmentId);
                if ($asset->is_assign == Asset::STATUS_ASSIGNED) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Asset '{$asset->equipment_name}' is already assigned"
                    ], 422);
                }

                // Create assignment
                $assignment = AssetAssignment::create([
                    'equipment_id' => $equipmentId,
                    'employee_id' => $request->employee_id,
                    'issue_date' => $issueDates[$index] ?? now()->format('Y-m-d')
                ]);

                // Mark asset as assigned
                $asset->update(['is_assign' => Asset::STATUS_ASSIGNED]);

                $assignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assets assigned successfully',
                'data' => $assignments
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/asset-assignments/return",
     *     summary="Return asset(s)",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","equipment_ids","return_dates"},
     *             @OA\Property(property="employee_id", type="string", example="EMP001"),
     *             @OA\Property(property="equipment_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="return_dates", type="array", @OA\Items(type="string", format="date")),
     *             @OA\Property(property="damage_descriptions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Assets returned")
     * )
     */
    public function returnAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string|exists:employee_history,employee_id',
            'equipment_ids' => 'required|array|min:1',
            'equipment_ids.*' => 'required|exists:equipment,equipment_id',
            'return_dates' => 'required|array',
            'return_dates.*' => 'required|date',
            'damage_descriptions' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $equipmentIds = $request->equipment_ids;
            $returnDates = $request->return_dates;
            $damageDescriptions = $request->damage_descriptions ?? [];

            foreach ($equipmentIds as $index => $equipmentId) {
                // Find active assignment
                $assignment = AssetAssignment::where('employee_id', $request->employee_id)
                    ->where('equipment_id', $equipmentId)
                    ->active()
                    ->first();

                if (!$assignment) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "No active assignment found for equipment ID {$equipmentId}"
                    ], 404);
                }

                // Update assignment with return info
                $assignment->update([
                    'return_date' => $returnDates[$index],
                    'damarage_desc' => $damageDescriptions[$index] ?? null
                ]);

                // Mark asset as available
                $asset = Asset::find($equipmentId);
                $asset->update(['is_assign' => Asset::STATUS_AVAILABLE]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assets returned successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to return assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/asset-assignments/employee/{employeeId}",
     *     summary="Get employee's current assets",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employeeId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Employee's assets")
     * )
     */
    public function employeeAssets($employeeId)
    {
        $assignments = AssetAssignment::with(['asset.type'])
            ->where('employee_id', $employeeId)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/asset-assignments/history/{employeeId}",
     *     summary="Get employee's asset history",
     *     tags={"Assets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employeeId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Employee's asset history")
     * )
     */
    public function employeeAssetHistory($employeeId)
    {
        $assignments = AssetAssignment::with(['asset.type'])
            ->where('employee_id', $employeeId)
            ->returned()
            ->orderBy('return_date', 'DESC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }
}

