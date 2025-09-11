<?php
// src/Services/TemplateCompiler.php

namespace Natnaeln4d\MailCustomizer\Services;

use YourVendorName\MailCustomizer\Models\MailTemplate;
use Illuminate\Support\Facades\Storage;
use Natnaeln4d\MailCustomizer\Models\MailTemplate as ModelsMailTemplate;

class TemplateCompiler
{
    public function compile(ModelsMailTemplate $template, $data = [])
    {
        $compiled = $template->content;
        
      
        foreach ($data as $key => $value) {
            $compiled = str_replace("{{ $key }}", $value, $compiled);
        }
        
        $compiled = $this->applyStyles($compiled, $template->styles);
        
        $compiled = $this->processImages($compiled, $template->images);
        
        return $compiled;
    }
    
    protected function applyStyles($content, $styles)
    {
        if (empty($styles)) {
            return $content;
        }
        
        $styledContent = $content;
        
        foreach ($styles as $selector => $rules) {
            if ($selector === 'body') {
                $styledContent = str_replace(
                    '<body>', 
                    '<body style="' . $this->cssArrayToString($rules) . '">', 
                    $styledContent
                );
            } else {
                $styledContent = str_replace(
                    'class="' . $selector . '"', 
                    'class="' . $selector . '" style="' . $this->cssArrayToString($rules) . '"', 
                    $styledContent
                );
            }
        }
        
        return $styledContent;
    }
    
    protected function processImages($content, $images)
    {
        if (empty($images)) {
            return $content;
        }
        
        $processedContent = $content;
        
        foreach ($images as $image) {
            if ($image['type'] === 'background') {
                $backgroundStyle = "background-image: url('{$image['url']}'); ";
                $backgroundStyle .= "background-size: {$image['size']}; ";
                $backgroundStyle .= "background-position: {$image['position']}; ";
                
                if ($image['repeat'] === 'no-repeat') {
                    $backgroundStyle .= "background-repeat: no-repeat; ";
                } else {
                    $backgroundStyle .= "background-repeat: repeat; ";
                }
                
                if (strpos($processedContent, '<body>') !== false) {
                    $processedContent = str_replace(
                        '<body>', 
                        '<body style="' . $backgroundStyle . '">', 
                        $processedContent
                    );
                }
            } else {
                $imgTag = '<img src="' . $image['url'] . '" ';
                $imgTag .= 'alt="' . ($image['alt'] ?? '') . '" ';
                $imgTag .= 'style="';
                
                if ($image['shape'] === 'circle') {
                    $imgTag .= 'border-radius: 50%; ';
                } elseif ($image['shape'] === 'rounded') {
                    $imgTag .= 'border-radius: 10px; ';
                }
                
                if ($image['position'] === 'left') {
                    $imgTag .= 'float: left; margin-right: 15px; ';
                } elseif ($image['position'] === 'right') {
                    $imgTag .= 'float: right; margin-left: 15px; ';
                } elseif ($image['position'] === 'center') {
                    $imgTag .= 'display: block; margin: 0 auto; ';
                }
                
                if (!empty($image['width'])) {
                    $imgTag .= 'width: ' . $image['width'] . '; ';
                }
                if (!empty($image['height'])) {
                    $imgTag .= 'height: ' . $image['height'] . '; ';
                }
                
                $imgTag .= '" />';
                
                $placeholder = '{{image_' . $image['id'] . '}}';
                $processedContent = str_replace($placeholder, $imgTag, $processedContent);
            }
        }
        
        return $processedContent;
    }
    
    protected function cssArrayToString($cssArray)
    {
        $result = '';
        foreach ($cssArray as $property => $value) {
            $result .= "$property: $value; ";
        }
        return trim($result);
    }
}