<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

use Natnaeln4d\MailCustomizer\Rendering\Css;

class TextBlock extends Block
{
    /** Tags allowed in rich text; everything else is stripped. */
    protected const ALLOWED = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><h1><h2><h3><h4><span><blockquote>';

    public function render(array $element): string
    {
        $styles = $this->styles($element);

        $html = $this->sanitize((string) ($element['content'] ?? ''));

        // Explicit margins on block tags: Outlook ignores the browser defaults.
        $html = preg_replace('/<(p|h[1-4]|ul|ol|blockquote)(\s[^>]*)?>/i', '<$1$2 style="margin:0 0 12px 0;">', $html);
        $html = preg_replace('/<a\s([^>]*)>/i', '<a $1 style="color:inherit;text-decoration:underline;">', $html);

        return $this->row($html, [
            'font-family' => $styles['font-family'] ?? 'Arial, Helvetica, sans-serif',
            'font-size' => $styles['font-size'] ?? '14px',
            'line-height' => $styles['line-height'] ?? '1.5',
            'color' => Css::color($styles['color'] ?? null, '#333333'),
            'text-align' => Css::align($styles['text-align'] ?? null),
            'background-color' => $styles['background-color'] ?? null,
            'padding' => $styles['padding'] ?? '10px 20px',
        ]);
    }

    public function toText(array $element): string
    {
        $html = preg_replace('/<\/(p|h[1-4]|li|blockquote)>|<br\s*\/?>/i', "\n", (string) ($element['content'] ?? ''));

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }

    protected function sanitize(string $html): string
    {
        $html = strip_tags($html, self::ALLOWED);

        // Drop inline event handlers and any style/class/id the author pasted in.
        $html = preg_replace('/\s+(on\w+|style|class|id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Neutralise javascript:/data: links.
        return preg_replace_callback('/href\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', function ($m) {
            $url = ($m[1] ?? '') !== '' ? $m[1] : ($m[2] ?? '');

            return 'href="' . e(Css::url(html_entity_decode($url))) . '"';
        }, $html);
    }
}
