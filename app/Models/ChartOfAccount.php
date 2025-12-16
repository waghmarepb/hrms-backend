<?php

class ChartOfAccount
{
    private $db;
    protected $table = 'acc_coa';
    protected $primaryKey = 'HeadCode';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all chart of accounts with optional filters
     */
    public function all($filters = [])
    {
        $query = $this->db->table($this->table);

        if (isset($filters['is_active'])) {
            $query->where('IsActive', $filters['is_active']);
        }

        if (isset($filters['is_transaction'])) {
            $query->where('IsTransaction', $filters['is_transaction']);
        }

        if (isset($filters['head_type'])) {
            $query->where('HeadType', $filters['head_type']);
        }

        if (isset($filters['head_level'])) {
            $query->where('HeadLevel', $filters['head_level']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereRaw("(HeadName LIKE ? OR HeadCode LIKE ?)", ["%$search%", "%$search%"]);
        }

        return $query->orderBy('HeadCode')->get();
    }

    /**
     * Get active accounts
     */
    public function getActive()
    {
        return $this->db->table($this->table)
            ->where('IsActive', 1)
            ->orderBy('HeadCode')
            ->get();
    }

    /**
     * Get transaction accounts
     */
    public function getTransactionAccounts()
    {
        return $this->db->table($this->table)
            ->where('IsActive', 1)
            ->where('IsTransaction', 1)
            ->orderBy('HeadName')
            ->get();
    }

    /**
     * Get accounts by type
     */
    public function getByType($type)
    {
        return $this->db->table($this->table)
            ->where('IsActive', 1)
            ->where('HeadType', $type)
            ->orderBy('HeadName')
            ->get();
    }

    /**
     * Find account by HeadCode
     */
    public function find($headCode)
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, $headCode)
            ->first();
    }

    /**
     * Get children of an account
     */
    public function getChildren($headName)
    {
        return $this->db->table($this->table)
            ->where('PHeadName', $headName)
            ->orderBy('HeadCode')
            ->get();
    }

    /**
     * Get parent of an account
     */
    public function getParent($pHeadName)
    {
        return $this->db->table($this->table)
            ->where('HeadName', $pHeadName)
            ->first();
    }

    /**
     * Check if account has transactions
     */
    public function hasTransactions($headCode)
    {
        $count = $this->db->table('acc_transaction')
            ->where('COAID', $headCode)
            ->count();
        return $count > 0;
    }

    /**
     * Check if account has children
     */
    public function hasChildren($headName)
    {
        $count = $this->db->table($this->table)
            ->where('PHeadName', $headName)
            ->count();
        return $count > 0;
    }

    /**
     * Create new account
     */
    public function create($data)
    {
        // Add audit fields
        $data['CreateDate'] = date('Y-m-d H:i:s');
        
        // Set defaults
        $data['IsActive'] = $data['IsActive'] ?? 1;
        $data['IsTransaction'] = $data['IsTransaction'] ?? 0;
        $data['IsGL'] = $data['IsGL'] ?? 0;
        $data['IsBudget'] = $data['IsBudget'] ?? 0;
        $data['IsDepreciation'] = $data['IsDepreciation'] ?? 0;
        $data['DepreciationRate'] = $data['DepreciationRate'] ?? 0;

        $this->db->table($this->table)->insert($data);
        return $this->find($data['HeadCode']);
    }

    /**
     * Update account
     */
    public function update($headCode, $data)
    {
        $data['UpdateDate'] = date('Y-m-d H:i:s');
        
        $this->db->table($this->table)
            ->where($this->primaryKey, $headCode)
            ->update($data);
            
        return $this->find($headCode);
    }

    /**
     * Delete account
     */
    public function delete($headCode)
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, $headCode)
            ->delete();
    }

    /**
     * Check if HeadCode exists
     */
    public function exists($headCode)
    {
        $result = $this->db->table($this->table)
            ->where($this->primaryKey, $headCode)
            ->first();
        return $result !== null;
    }
}

