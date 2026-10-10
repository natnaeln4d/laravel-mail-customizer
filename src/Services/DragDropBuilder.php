<?php
// src/Services/DragDropBuilder.php

namespace Natnaeln4d\MailCustomizer\Services;

use Natnaeln4d\MailCustomizer\Models\MailTemplate;
use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;
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
            'properties' => ['text' => 'Button', 'href' => '#'],
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
            'properties' => ['height' => '20']
        ]
    ];

    public function getElements()
    {
        return $this->elements;
    }

    public function __construct(protected EmailRenderer $renderer)
    {
    }

    /**
     * Render the builder's block tree to email-safe HTML (tables + inline styles).
     */
    public function generateHtmlFromStructure($structure, $data = [], array $options = [])
    {
        return $this->renderer->render((array) $structure, $options);
    }

    public function toPlainText($structure): string
    {
        return $this->renderer->toPlainText((array) $structure);
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