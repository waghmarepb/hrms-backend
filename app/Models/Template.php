<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $table = 'email_template';
    protected $primaryKey = 'template_id';
    public $timestamps = false;

    protected $fillable = [
        'template_name',
        'template_subject',
        'template_body',
        'template_type',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Template Types
    const TYPE_EMAIL = 'email';
    const TYPE_DOCUMENT = 'document';
    const TYPE_SMS = 'sms';
    const TYPE_NOTIFICATION = 'notification';

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Search templates
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('template_name', 'LIKE', "%{$term}%")
            ->orWhere('template_subject', 'LIKE', "%{$term}%");
    }

    /**
     * Replace template variables
     */
    public function render($variables = [])
    {
        $body = $this->template_body;
        $subject = $this->template_subject;

        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $body = str_replace($placeholder, $value, $body);
            $subject = str_replace($placeholder, $value, $subject);
        }

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
}

