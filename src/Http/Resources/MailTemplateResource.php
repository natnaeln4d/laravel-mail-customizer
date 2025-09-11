<?php
// src/Http/Resources/MailTemplateResource.php

namespace Natnaeln4d\MailCustomizer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MailTemplateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'content' => $this->content,
            'images' => $this->images,
            'styles' => $this->styles,
            'layout' => $this->layout,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}