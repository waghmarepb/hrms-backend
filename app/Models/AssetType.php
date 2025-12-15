<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory;

    protected $table = 'equipment_type';
    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $fillable = [
        'type_name',
    ];

    /**
     * Get all equipment of this type
     */
    public function equipment()
    {
        return $this->hasMany(Asset::class, 'type_id', 'type_id');
    }

    /**
     * Get count of equipment for this type
     */
    public function getEquipmentCountAttribute()
    {
        return $this->equipment()->count();
    }
}

