<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

use Natnaeln4d\MailCustomizer\Rendering\Css;

class ImageBlock extends Block
{
    public function render(array $element): string
    {
        $props = $this->props($element);
        $styles = $this->styles($element);

        $src = trim((string) ($props['src'] ?? ''));
        if ($src === '' || $src === '{{image_placeholder}}') {
            return '';
        }

        $max = $this->renderer->contentWidth();
        $width = Css::px($props['width'] ?? null, 0);
        $width = $width > 0 ? min($width, $max) : $max;
        $height = Css::px($props['height'] ?? null, 0);
        $align = Css::align($props['alignment'] ?? null, 'center');

        $img = '<img src="' . e(Css::url($src)) . '"'
            . ' alt="' . e($props['alt'] ?? '') . '"'
            . ' width="' . $width . '"'
            . ($height ? ' height="' . $height . '"' : '')
            . ' style="' . e(Css::inline([
                'display' => 'block',
                'border' => '0',
                'outline' => 'none',
                'text-decoration' => 'none',
                'width' => $width . 'px',
                'max-width' => '100%',
                'height' => $height ? $height . 'px' : 'auto',
            ])) . '">';

        if (! empty($props['href'])) {
            $img = '<a href="' . e(Css::url($props['href'])) . '" target="_blank" style="text-decoration:none;">' . $img . '</a>';
        }

        // Alignment via the align attribute on a nested table: floats don't survive Outlook.
        $inner = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" align="' . $align . '"><tr><td>' . $img . '</td></tr></table>';

        return $this->row(
            $inner,
            ['padding' => $styles['padding'] ?? '10px 20px', 'background-color' => $styles['background-color'] ?? null],
            'align="' . $align . '"'
        );
    }

    public function toText(array $element): string
    {
        $alt = trim((string) ($this->props($element)['alt'] ?? ''));

        return $alt !== '' ? "[{$alt}]" : '';
    }
}
