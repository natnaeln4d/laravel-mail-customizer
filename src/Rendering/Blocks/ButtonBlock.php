<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

use Natnaeln4d\MailCustomizer\Rendering\Css;

/**
 * "Bulletproof" button: a padded table cell with a background colour, plus a
 * VML fallback so Outlook (Word engine) draws the rounded rectangle too.
 */
class ButtonBlock extends Block
{
    public function render(array $element): string
    {
        [$label, $href] = $this->labelAndHref($element);
        $styles = $this->styles($element);

        $bg = Css::color($styles['background-color'] ?? null, '#007bff');
        $color = Css::color($styles['color'] ?? null, '#ffffff');
        $radius = Css::px($styles['border-radius'] ?? null, 4);
        $align = Css::align($this->props($element)['alignment'] ?? ($styles['text-align'] ?? null), 'center');
        $font = $styles['font-family'] ?? 'Arial, Helvetica, sans-serif';
        $fontSize = Css::px($styles['font-size'] ?? null, 16);

        $url = e(Css::url($href));
        $text = e($label);
        $width = max(120, strlen($label) * 10 + 48);

        $vml = '<!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"'
            . ' href="' . $url . '" style="height:44px;v-text-anchor:middle;width:' . $width . 'px;"'
            . ' arcsize="' . min(50, (int) round($radius / 44 * 100)) . '%" stroke="f" fillcolor="' . e($bg) . '">'
            . '<w:anchorlock/><center style="color:' . e($color) . ';font-family:Arial,sans-serif;font-size:' . $fontSize . 'px;font-weight:bold;">' . $text . '</center>'
            . '</v:roundrect><![endif]-->';

        $anchor = '<!--[if !mso]><!--><a href="' . $url . '" target="_blank" style="' . e(Css::inline([
            'display' => 'inline-block',
            'background-color' => $bg,
            'color' => $color,
            'font-family' => $font,
            'font-size' => $fontSize . 'px',
            'font-weight' => 'bold',
            'line-height' => '44px',
            'text-align' => 'center',
            'text-decoration' => 'none',
            'padding' => '0 24px',
            'border-radius' => $radius . 'px',
        ])) . '">' . $text . '</a><!--<![endif]-->';

        $inner = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" align="' . $align . '"><tr>'
            . '<td align="center" bgcolor="' . e($bg) . '" style="border-radius:' . $radius . 'px;">' . $vml . $anchor . '</td></tr></table>';

        return $this->row($inner, ['padding' => $styles['padding'] ?? '10px 20px'], 'align="' . $align . '"');
    }

    public function toText(array $element): string
    {
        [$label, $href] = $this->labelAndHref($element);

        return $href !== '' && $href !== '#' ? "{$label}: {$href}" : $label;
    }

    /**
     * The builder stores label/href in properties; older templates only have an <a> in content.
     */
    protected function labelAndHref(array $element): array
    {
        $props = $this->props($element);
        $label = trim((string) ($props['text'] ?? ''));
        $href = trim((string) ($props['href'] ?? ''));

        $content = (string) ($element['content'] ?? '');
        if ($label === '' && $content !== '') {
            $label = trim(html_entity_decode(strip_tags($content)));
        }
        if ($href === '' && preg_match('/href\s*=\s*["\']([^"\']*)["\']/i', $content, $m)) {
            $href = html_entity_decode($m[1]);
        }

        return [$label !== '' ? $label : 'Button', $href !== '' ? $href : '#'];
    }
}
