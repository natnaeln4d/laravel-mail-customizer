<?php
// src/Services/TemplateCompiler.php

namespace Natnaeln4d\MailCustomizer\Services;

use Natnaeln4d\MailCustomizer\Models\MailTemplate;

class TemplateCompiler
{
    public function compile(MailTemplate $template, $data = [])
    {
        $compiled = (string) $template->content;

        $compiled = $this->replaceVariables($compiled, $data);

        $compiled = $this->applyStyles($compiled, $template->styles);

        $compiled = $this->processImages($compiled, $template->images);

        return $compiled;
    }

    /**
     * Replace {{key}} / {{ key }} placeholders. Values are HTML-escaped so
     * user-supplied data cannot inject markup into the email.
     */
    protected function replaceVariables($content, $data)
    {
        foreach ($data as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $pattern = '/\{\{\s*' . preg_quote((string) $key, '/') . '\s*\}\}/';
            $content = preg_replace_callback(
                $pattern,
                fn () => e((string) $value),
                $content
            );
        }

        return $content;
    }

    protected function applyStyles($content, $styles)
    {
        if (empty($styles) || ! is_iterable($styles)) {
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
        if (empty($images) || ! is_iterable($images)) {
            return $content;
        }

        $processedContent = $content;

        foreach ($images as $image) {
            if (($image['type'] ?? 'inline') === 'background') {
                $backgroundStyle = "background-image: url('" . ($image['url'] ?? '') . "'); ";
                $backgroundStyle .= 'background-size: ' . ($image['size'] ?? 'cover') . '; ';
                $backgroundStyle .= 'background-position: ' . ($image['position'] ?? 'center') . '; ';

                if (($image['repeat'] ?? 'no-repeat') === 'no-repeat') {
                    $backgroundStyle .= 'background-repeat: no-repeat; ';
                } else {
                    $backgroundStyle .= 'background-repeat: repeat; ';
                }

                $processedContent = str_replace(
                    '<body>',
                    '<body style="' . $backgroundStyle . '">',
                    $processedContent
                );
            } else {
                $imgTag = '<img src="' . e($image['url'] ?? '') . '" ';
                $imgTag .= 'alt="' . e($image['alt'] ?? '') . '" ';
                $imgTag .= 'style="';

                $shape = $image['shape'] ?? 'rectangle';
                if ($shape === 'circle') {
                    $imgTag .= 'border-radius: 50%; ';
                } elseif ($shape === 'rounded') {
                    $imgTag .= 'border-radius: 10px; ';
                }

                $position = $image['position'] ?? 'left';
                if ($position === 'left') {
                    $imgTag .= 'float: left; margin-right: 15px; ';
                } elseif ($position === 'right') {
                    $imgTag .= 'float: right; margin-left: 15px; ';
                } elseif ($position === 'center') {
                    $imgTag .= 'display: block; margin: 0 auto; ';
                }

                if (! empty($image['width'])) {
                    $imgTag .= 'width: ' . $image['width'] . '; ';
                }
                if (! empty($image['height'])) {
                    $imgTag .= 'height: ' . $image['height'] . '; ';
                }

                $imgTag .= '" />';

                $placeholder = '{{image_' . ($image['id'] ?? '') . '}}';
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
