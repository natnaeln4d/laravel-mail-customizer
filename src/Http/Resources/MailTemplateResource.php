<?php
// src/Http/Resources/MailTemplateResource.php

namespace Natnaeln4d\MailCustomizer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MailTemplateResource extends JsonResource
{
    // The bundled JS clients read the template straight off response.data.
    public static $wrap = null;

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'content' => $this->content,
            'structure' => $this->structure,
            'images' => $this->images,
            'styles' => $this->styles,
            'layout' => $this->layout,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}