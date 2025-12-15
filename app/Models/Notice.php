<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $table = 'notice_board';
    protected $primaryKey = 'notice_id';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'notice_date',
        'expire_date',
        'posted_by',
        'status',
        'priority',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'expire_date' => 'date',
    ];
}


