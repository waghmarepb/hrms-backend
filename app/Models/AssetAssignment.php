<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $table = 'employee_equipment';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'equipment_id',
        'employee_id',
        'issue_date',
        'return_date',
        'damarage_desc',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * Get the asset
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'equipment_id', 'equipment_id');
    }

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Scope for active assignments (not returned)
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('return_date')
              ->orWhere('return_date', '')
              ->orWhere('return_date', '0000-00-00');
        });
    }

    /**
     * Scope for returned assignments
     */
    public function scopeReturned($query)
    {
        return $query->whereNotNull('return_date')
            ->where('return_date', '!=', '')
            ->where('return_date', '!=', '0000-00-00');
    }

    /**
     * Scope by employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope by asset
     */
    public function scopeForAsset($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * Check if assignment is active
     */
    public function isActive()
    {
        return empty($this->return_date) || 
               $this->return_date == '0000-00-00' || 
               $this->return_date == null;
    }

    /**
     * Check if asset has been returned
     */
    public function isReturned()
    {
        return !$this->isActive();
    }
}


