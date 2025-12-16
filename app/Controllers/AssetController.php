<?php

class AssetController
{
    private $assetModel;
    private $typeModel;
    private $assignmentModel;

    public function __construct()
    {
        $this->assetModel = new Asset();
        $this->typeModel = new AssetType();
        $this->assignmentModel = new AssetAssignment();
    }

    // Asset Type Management
    public function types()
    {
        try {
            $types = $this->typeModel->getAll();
            Response::json(['success' => true, 'data' => $types], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch asset types: ' . $e->getMessage(), 500);
        }
    }

    public function createType()
    {
        try {
            $data = Request::all();
            Validator::make($data, ['type_name' => 'required|string|max:255'])->validate();
            
            $typeId = $this->typeModel->create(['type_name' => $data['type_name']]);
            Response::json(['success' => true, 'message' => 'Asset type created', 'data' => ['id' => $typeId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to create asset type: ' . $e->getMessage(), 500);
        }
    }

    public function showType($id)
    {
        try {
            $type = $this->typeModel->findById($id);
            if (!$type) Response::notFound('Asset type not found');
            Response::json(['success' => true, 'data' => $type], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch asset type: ' . $e->getMessage(), 500);
        }
    }

    public function updateType($id)
    {
        try {
            $type = $this->typeModel->findById($id);
            if (!$type) Response::notFound('Asset type not found');
            
            $data = Request::all();
            $this->typeModel->update($id, $data);
            Response::json(['success' => true, 'message' => 'Asset type updated'], 200);
        } catch (Exception $e) {
            Response::error('Failed to update asset type: ' . $e->getMessage(), 500);
        }
    }

    public function deleteType($id)
    {
        try {
            $type = $this->typeModel->findById($id);
            if (!$type) Response::notFound('Asset type not found');
            
            $this->typeModel->delete($id);
            Response::json(['success' => true, 'message' => 'Asset type deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete asset type: ' . $e->getMessage(), 500);
        }
    }

    // Asset/Equipment Management
    public function index()
    {
        try {
            $assets = $this->assetModel->getAll();
            Response::json(['success' => true, 'data' => $assets], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch assets: ' . $e->getMessage(), 500);
        }
    }

    public function available()
    {
        try {
            $assets = $this->assetModel->getAvailable();
            Response::json(['success' => true, 'data' => $assets], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch available assets: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'equipment_name' => 'required|string',
                'type_id' => 'required|integer',
                'model' => 'string',
                'serial_no' => 'string'
            ])->validate();

            $assetData = [
                'equipment_name' => $data['equipment_name'],
                'type_id' => $data['type_id'],
                'model' => $data['model'] ?? null,
                'serial_no' => $data['serial_no'] ?? null,
                'is_assign' => Asset::STATUS_AVAILABLE
            ];

            $assetId = $this->assetModel->create($assetData);
            Response::json(['success' => true, 'message' => 'Asset created', 'data' => ['id' => $assetId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to create asset: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $asset = $this->assetModel->findById($id);
            if (!$asset) Response::notFound('Asset not found');
            Response::json(['success' => true, 'data' => $asset], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch asset: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $asset = $this->assetModel->findById($id);
            if (!$asset) Response::notFound('Asset not found');
            
            $data = Request::all();
            $this->assetModel->update($id, $data);
            Response::json(['success' => true, 'message' => 'Asset updated'], 200);
        } catch (Exception $e) {
            Response::error('Failed to update asset: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $asset = $this->assetModel->findById($id);
            if (!$asset) Response::notFound('Asset not found');
            
            $this->assetModel->delete($id);
            Response::json(['success' => true, 'message' => 'Asset deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete asset: ' . $e->getMessage(), 500);
        }
    }

    // Asset Assignment Management
    public function assignments()
    {
        try {
            $assignments = $this->assignmentModel->getAll();
            Response::json(['success' => true, 'data' => $assignments], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch assignments: ' . $e->getMessage(), 500);
        }
    }

    public function assignAsset()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'equipment_id' => 'required|integer',
                'employee_id' => 'required|string',
                'issue_date' => 'required|date'
            ])->validate();

            $assignmentData = [
                'equipment_id' => $data['equipment_id'],
                'employee_id' => $data['employee_id'],
                'issue_date' => $data['issue_date']
            ];

            $assignmentId = $this->assignmentModel->create($assignmentData);
            $this->assetModel->update($data['equipment_id'], ['is_assign' => Asset::STATUS_ASSIGNED]);

            Response::json(['success' => true, 'message' => 'Asset assigned', 'data' => ['id' => $assignmentId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to assign asset: ' . $e->getMessage(), 500);
        }
    }

    public function returnAsset()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'assignment_id' => 'required|integer',
                'return_date' => 'required|date',
                'damarage_desc' => 'string'
            ])->validate();

            $assignment = $this->assignmentModel->findById($data['assignment_id']);
            if (!$assignment) Response::notFound('Assignment not found');

            $updateData = [
                'return_date' => $data['return_date'],
                'damarage_desc' => $data['damarage_desc'] ?? null
            ];

            $this->assignmentModel->update($data['assignment_id'], $updateData);
            $this->assetModel->update($assignment['equipment_id'], ['is_assign' => Asset::STATUS_AVAILABLE]);

            Response::json(['success' => true, 'message' => 'Asset returned'], 200);
        } catch (Exception $e) {
            Response::error('Failed to return asset: ' . $e->getMessage(), 500);
        }
    }

    public function employeeAssets($employeeId)
    {
        try {
            $assets = $this->assignmentModel->getEmployeeAssets($employeeId);
            Response::json(['success' => true, 'data' => $assets], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch employee assets: ' . $e->getMessage(), 500);
        }
    }

    public function employeeAssetHistory($employeeId)
    {
        try {
            $history = $this->assignmentModel->getEmployeeHistory($employeeId);
            Response::json(['success' => true, 'data' => $history], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch asset history: ' . $e->getMessage(), 500);
        }
    }
}

