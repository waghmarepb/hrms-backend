<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $table = 'recruitment';
    protected $primaryKey = 'recruitment_id';
    public $timestamps = false;

    protected $fillable = [
        'position',
        'description',
        'requirements',
        'location',
        'salary_range',
        'employment_type',
        'status',
        'posted_date',
        'closing_date',
        'posted_by',
    ];

    protected $casts = [
        'posted_date' => 'date',
        'closing_date' => 'date',
    ];

    /**
     * Get applications for this job
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'recruitment_id', 'recruitment_id');
    }
}


