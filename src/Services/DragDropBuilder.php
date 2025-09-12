<?php
// src/Services/DragDropBuilder.php

namespace Natnaeln4d\MailCustomizer\Services;

use Natnaeln4d\MailCustomizer\Models\MailTemplate;
use Illuminate\Support\Facades\Storage;

class DragDropBuilder
{
    protected $elements = [
        'text' => [
            'type' => 'text',
            'label' => 'Text',
            'icon' => 'fa-font',
            'defaultContent' => '<p>Your text here</p>',
            'editable' => true,
            'styles' => [
                'font-family' => 'Arial, sans-serif',
                'font-size' => '14px',
                'color' => '#333333',
                'padding' => '10px'
            ]
        ],
        'image' => [
            'type' => 'image',
            'label' => 'Image',
            'icon' => 'fa-image',
            'defaultContent' => '<img src="{{image_placeholder}}" style="max-width: 100%; height: auto;" />',
            'editable' => false,
            'properties' => [
                'src', 'alt', 'width', 'height', 'alignment'
            ]
        ],
        'button' => [
            'type' => 'button',
            'label' => 'Button',
            'icon' => 'fa-square',
            'defaultContent' => '<a href="#" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;">Button</a>',
            'editable' => true,
            'styles' => [
                'background-color' => '#007bff',
                'color' => '#ffffff',
                'padding' => '10px 20px',
                'border-radius' => '4px'
            ]
        ],
        'divider' => [
            'type' => 'divider',
            'label' => 'Divider',
            'icon' => 'fa-minus',
            'defaultContent' => '<hr style="border: none; border-top: 1px solid #cccccc; margin: 20px 0;" />',
            'editable' => false
        ],
        'spacer' => [
            'type' => 'spacer',
            'label' => 'Spacer',
            'icon' => 'fa-arrows-alt-v',
            'defaultContent' => '<div style="height: 20px;"></div>',
            'editable' => true,
            'properties' => ['height']
        ]
    ];

    public function getElements()
    {
        return $this->elements;
    }

    public function generateHtmlFromStructure($structure, $data = [])
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body>';
        
        foreach ($structure as $element) {
            $html .= $this->renderElement($element, $data);
        }
        
        $html .= '</body></html>';
        return $html;
    }

    protected function renderElement($element, $data)
    {
        $content = $element['content'] ?? $this->elements[$element['type']]['defaultContent'];
        
        $styles = array_merge(
            $this->elements[$element['type']]['styles'] ?? [],
            $element['styles'] ?? []
        );
        
        $styleString = '';
        foreach ($styles as $property => $value) {
            $styleString .= "$property: $value; ";
        }
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        if ($element['type'] === 'image' && isset($element['properties']['src'])) {
            $content = str_replace('{{image_placeholder}}', $element['properties']['src'], $content);
            
            if (isset($element['properties']['alignment'])) {
                $alignmentStyle = '';
                switch ($element['properties']['alignment']) {
                    case 'left':
                        $alignmentStyle = 'float: left; margin-right: 15px;';
                        break;
                    case 'right':
                        $alignmentStyle = 'float: right; margin-left: 15px;';
                        break;
                    case 'center':
                        $alignmentStyle = 'display: block; margin: 0 auto;';
                        break;
                }
                $content = str_replace('style="', 'style="' . $alignmentStyle . ' ', $content);
            }
        }
        
        if ($element['type'] === 'spacer' && isset($element['properties']['height'])) {
            $content = str_replace('height: 20px', 'height: ' . $element['properties']['height'] . 'px', $content);
        }
        
        return '<div style="' . $styleString . '">' . $content . '</div>';
    }

    public function parseHtmlToStructure($html)
    {
        $structure = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            foreach ($body->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $element = $this->parseNodeToElement($node);
                    if ($element) {
                        $structure[] = $element;
                    }
                }
            }
        }
        
        return $structure;
    }

    protected function parseNodeToElement($node)
    {
        $element = [
            'type' => 'text', 
            'content' => $node->ownerDocument->saveHTML($node),
            'styles' => []
        ];
        
        if (strpos($element['content'], '<img') !== false) {
            $element['type'] = 'image';
        } elseif (strpos($element['content'], '<a') !== false && strpos($element['content'], 'background-color') !== false) {
            $element['type'] = 'button';
        } elseif (strpos($element['content'], '<hr') !== false) {
            $element['type'] = 'divider';
        } elseif (strpos($element['content'], 'height:') !== false && strpos($element['content'], 'px') !== false) {
            $element['type'] = 'spacer';
        }
        
        return $element;
    }
}