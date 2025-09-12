<?php

namespace Natnaeln4d\MailCustomizer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class MailTemplate extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'content',
        'structure', 
        'styles',
        'images',
        'layout'
    ];

    protected $casts = [
        'styles' => AsArrayObject::class,
        'images' => AsArrayObject::class,
        'structure' => AsArrayObject::class, 
    ];
}