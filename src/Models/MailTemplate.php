<?php

namespace Natnaeln4d\MailCustomizer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Natnaeln4d\MailCustomizer\Mail\CustomMailable;

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

    /**
     * Build a mailable for this template: Mail::to($user)->send($template->mailable([...])).
     */
    public function mailable(array $data = []): CustomMailable
    {
        return new CustomMailable($this, $data);
    }

    /**
     * Find an active template by name, e.g. MailTemplate::named('Welcome').
     */
    public static function named(string $name): self
    {
        return static::query()->where('name', $name)->where('is_active', true)->firstOrFail();
    }
}