<?php


namespace Natnaeln4d\MailCustomizer\Models;

use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    protected $table = 'mail_templates';
    protected $fillable = [
        'name', 
        'subject', 
        'content', 
        'styles', 
        'images', 
        'layout', 
        'is_active'
    ];

    protected $casts = [
        'styles' => 'array',
        'images' => 'array', 
        'is_active' => 'boolean'
    ];
}