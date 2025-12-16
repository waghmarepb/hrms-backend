<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $table = 'equipment';
    protected $primaryKey = 'equipment_id';
    public $timestamps = false;

    protected $fillable = [
        'equipment_name',
        'type_id',
        'model',
        'serial_no',
        'is_assign',
    ];

    protected $casts = [
        'is_assign' => 'boolean',
    ];

    // Assignment Status Constants
    const STATUS_AVAILABLE = 0;
    const STATUS_ASSIGNED = 1;

    /**
     * Get the asset type
     */
    public function type()
    {
        return $this->belongsTo(AssetType::class, 'type_id', 'type_id');
    }

    /**
     * Get all assignments for this asset
     */
    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class, 'equipment_id', 'equipment_id');
    }

    /**
     * Get current assignment
     */
    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class, 'equipment_id', 'equipment_id')
            ->whereNull('return_date')
            ->orWhere('return_date', '');
    }

    /**
     * Get assignment history
     */
    public function assignmentHistory()
    {
        return $this->hasMany(AssetAssignment::class, 'equipment_id', 'equipment_id')
            ->whereNotNull('return_date')
            ->where('return_date', '!=', '')
            ->orderBy('return_date', 'DESC');
    }

    /**
     * Scope for available (unassigned) assets
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_assign', self::STATUS_AVAILABLE);
    }

    /**
     * Scope for assigned assets
     */
    public function scopeAssigned($query)
    {
        return $query->where('is_assign', self::STATUS_ASSIGNED);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Search assets by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('equipment_name', 'LIKE', "%{$term}%")
            ->orWhere('model', 'LIKE', "%{$term}%")
            ->orWhere('serial_no', 'LIKE', "%{$term}%");
    }
}


