<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $table = 'job_application';
    protected $primaryKey = 'application_id';
    public $timestamps = false;

    protected $fillable = [
        'recruitment_id',
        'applicant_name',
        'email',
        'phone',
        'resume',
        'cover_letter',
        'application_date',
        'status',
        'interview_date',
        'remarks',
    ];

    protected $casts = [
        'application_date' => 'date',
        'interview_date' => 'datetime',
    ];

    /**
     * Get the job posting
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'recruitment_id', 'recruitment_id');
    }
}



