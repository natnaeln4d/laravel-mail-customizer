<?php

namespace Natnaeln4d\MailCustomizer\Rendering;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\Block;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\ButtonBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\ColumnsBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\DividerBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\ImageBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\SpacerBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\TextBlock;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Produces email-safe HTML (table layout, inline styles, fixed-width wrapper,
 * Outlook fallbacks) from either:
 *
 *  - the visual editor's output  -> renderHtml($html, $css)
 *  - a JSON block tree           -> render($structure)
 */
class EmailRenderer
{
    /** Style properties that Outlook ignores on <div>/<p>/<a> but honours on <td>. */
    protected const BOX_PROPERTY = '/^(padding|background|border|box-shadow)/';

    /** @var array<string, class-string<Block>> */
    protected array $blocks = [
        'text' => TextBlock::class,
        'image' => ImageBlock::class,
        'button' => ButtonBlock::class,
        'divider' => DividerBlock::class,
        'spacer' => SpacerBlock::class,
        'columns' => ColumnsBlock::class,
    ];

    /** @var array<string, Block> */
    protected array $instances = [];

    /**
     * Register (or override) a block type for the JSON block-tree renderer.
     *
     * @param  class-string<Block>  $class
     */
    public function registerBlock(string $type, string $class): static
    {
        if (! is_subclass_of($class, Block::class)) {
            throw new InvalidArgumentException("{$class} must extend " . Block::class);
        }

        $this->blocks[$type] = $class;
        unset($this->instances[$type]);

        return $this;
    }

    public function blockTypes(): array
    {
        return array_keys($this->blocks);
    }

    public function contentWidth(): int
    {
        return max(320, (int) config('mail-customizer.renderer.width', 600));
    }

    // ---------------------------------------------------------------------
    // Visual editor output  ->  email HTML
    // ---------------------------------------------------------------------

    /**
     * Turn the visual editor's HTML + CSS into a hardened, inlined email document.
     *
     * @param  array  $options  fonts[], preheader, title, lang, background, content_background
     */
    public function renderHtml(string $html, string $css = '', array $options = []): string
    {
        $html = $this->sanitizeHtml($html);
        [$css, $mediaCss] = $this->splitMediaQueries($this->sanitizeCss($css));

        $inlined = (new CssToInlineStyles())->convert(
            '<html><body><div id="mc-root">' . $html . '</div></body></html>',
            $css
        );

        $content = $this->harden($inlined, $mediaCss);

        return $this->shell('<tr><td>' . $content . '</td></tr>', $options + ['extra_css' => $mediaCss]);
    }

    protected function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|style|link|meta|base)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = preg_replace('#</?(script|iframe|object|embed|form|style|link|meta|base)\b[^>]*>#i', '', $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/\b(href|src)\s*=\s*(["\'])\s*(javascript|vbscript|data:text)[^"\']*\2/i', '$1=$2#$2', $html);

        return $html;
    }

    protected function sanitizeCss(string $css): string
    {
        $css = preg_replace('/@import[^;]*;/i', '', $css);

        return preg_replace('/expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding/i', '', $css);
    }

    /**
     * Inliners ignore @media; keep those rules for a <style> block and force
     * them to win over the inline styles with !important.
     *
     * @return array{0: string, 1: string}  [css without media rules, media rules]
     */
    protected function splitMediaQueries(string $css): array
    {
        $media = '';
        $css = preg_replace_callback('/@media[^{]+\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', function ($m) use (&$media) {
            $media .= $m[0];

            return '';
        }, $css);

        $media = preg_replace_callback('/\{([^{}]*)\}/', function ($m) {
            $decls = array_filter(array_map('trim', explode(';', $m[1])));
            $decls = array_map(fn ($d) => stripos($d, '!important') === false ? $d . ' !important' : $d, $decls);

            return '{' . implode(';', $decls) . '}';
        }, $media);

        return [$css, $media];
    }

    protected function harden(string $inlinedDocument, string $mediaCss): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $inlinedDocument, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xp = new DOMXPath($dom);
        $root = $xp->query('//*[@id="mc-root"]')->item(0);
        if (! $root instanceof DOMElement) {
            return '';
        }

        // Elements targeted by @media rules still need their id after cleanup.
        preg_match_all('/#([A-Za-z0-9_-]+)/', $mediaCss, $m);
        $keepIds = array_flip($m[1] ?? []);

        $this->removeUnfilledImages($xp, $root);

        // Deepest first, so wrapping an ancestor never invalidates a descendant.
        $boxes = array_reverse(iterator_to_array($xp->query(
            './/*[self::div or self::p or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::a[@data-mc="button"]]',
            $root
        )));
        foreach ($boxes as $el) {
            $this->boxify($dom, $el);
        }

        foreach (iterator_to_array($xp->query('.//img', $root)) as $img) {
            $this->hardenImage($img);
        }
        foreach (iterator_to_array($xp->query('.//a[@href]', $root)) as $a) {
            $a->setAttribute('href', $this->absolutize($a->getAttribute('href')));
            if (! $a->hasAttribute('target')) {
                $a->setAttribute('target', '_blank');
            }
        }

        $this->cleanAttributes($xp, $root, $keepIds);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    /**
     * An Image block whose picture was never chosen still carries the editor's
     * grey placeholder icon. Drop it (and its wrapper) rather than email it.
     */
    protected function removeUnfilledImages(DOMXPath $xp, DOMElement $root): void
    {
        foreach (iterator_to_array($xp->query('.//img', $root)) as $img) {
            if (! preg_match('#^data:image/svg\+xml;base64,(.+)$#', $img->getAttribute('src'), $m)) {
                continue;
            }
            if (! str_contains((string) base64_decode($m[1]), 'M8.5 13.5l2.5 3 3.5-4.5')) {
                continue;
            }

            $parent = $img->parentNode;
            $remove = $parent instanceof DOMElement && $parent->getAttribute('data-mc') === 'image-wrap' && $parent->childNodes->length === 1
                ? $parent
                : $img;
            $remove->parentNode->removeChild($remove);
        }
    }

    /**
     * Outlook ignores padding/background/border on div, p, h* and a. Move those
     * onto a wrapping table cell (which every client understands).
     */
    protected function boxify(DOMDocument $dom, DOMElement $el): void
    {
        $kind = $el->getAttribute('data-mc');
        $style = $this->parseStyle($el->getAttribute('style'));

        if ($kind === 'spacer') {
            $this->replaceWithSpacer($dom, $el, $style);

            return;
        }

        $box = array_filter($style, fn ($k) => preg_match(self::BOX_PROPERTY, $k), ARRAY_FILTER_USE_KEY);
        if (! $box) {
            return;
        }

        foreach (array_keys($box) as $k) {
            unset($style[$k]);
        }
        $this->setStyle($el, $style);

        $isButton = $kind === 'button';

        $table = $dom->createElement('table');
        foreach (['role' => 'presentation', 'border' => '0', 'cellpadding' => '0', 'cellspacing' => '0'] as $k => $v) {
            $table->setAttribute($k, $v);
        }
        // "separate" so border-radius works on the cell.
        if ($isButton) {
            $table->setAttribute('align', $this->inheritedAlign($el));
            $table->setAttribute('style', 'border-collapse:separate;');
        } else {
            $table->setAttribute('width', '100%');
            $table->setAttribute('style', 'width:100%;border-collapse:separate;');
        }

        $td = $dom->createElement('td');
        $td->setAttribute('style', $this->styleString($box));

        $bg = $box['background-color'] ?? '';
        if ($bg !== '' && preg_match('/^(#[0-9a-f]{3,8}|[a-z]+)$/i', $bg)) {
            $td->setAttribute('bgcolor', $bg);
        }
        if (preg_match('/url\(\s*[\'"]?([^\'")]+)/i', $box['background-image'] ?? '', $um)) {
            $td->setAttribute('background', $this->absolutize($um[1]));
        }
        if (isset($style['text-align'])) {
            $td->setAttribute('align', $style['text-align']);
        }

        $tr = $dom->createElement('tr');
        $el->parentNode->replaceChild($table, $el);
        $table->appendChild($tr);
        $tr->appendChild($td);
        $td->appendChild($el);
    }

    protected function replaceWithSpacer(DOMDocument $dom, DOMElement $el, array $style): void
    {
        $height = max(1, min(Css::px($style['height'] ?? null, 20), 400));

        $table = $dom->createElement('table');
        foreach (['role' => 'presentation', 'border' => '0', 'cellpadding' => '0', 'cellspacing' => '0', 'width' => '100%'] as $k => $v) {
            $table->setAttribute($k, $v);
        }
        $tr = $dom->createElement('tr');
        $td = $dom->createElement('td');
        $td->setAttribute('height', (string) $height);
        $td->setAttribute('style', "height:{$height}px;font-size:1px;line-height:1px;");
        $td->appendChild($dom->createTextNode("\u{00A0}"));
        $tr->appendChild($td);
        $table->appendChild($tr);
        $el->parentNode->replaceChild($table, $el);
    }

    protected function hardenImage(DOMElement $img): void
    {
        $img->setAttribute('src', $this->absolutize($img->getAttribute('src')));
        if (! $img->hasAttribute('alt')) {
            $img->setAttribute('alt', '');
        }
        $img->setAttribute('border', '0');

        $style = $this->parseStyle($img->getAttribute('style'));
        $width = $style['width'] ?? '';
        $max = $style['max-width'] ?? '';

        if (! $img->hasAttribute('width')) {
            if (preg_match('/^\d+(\.\d+)?px$/i', $width)) {
                $img->setAttribute('width', (string) Css::px($width));
            } elseif (preg_match('/^\d+(\.\d+)?px$/i', $max)) {
                $img->setAttribute('width', (string) Css::px($max));
            }
        }

        $style += ['display' => 'block'];
        $this->setStyle($img, $style);
    }

    /**
     * Strip editor-only attributes; keep only the classes email CSS needs (mc-*).
     */
    protected function cleanAttributes(DOMXPath $xp, DOMElement $root, array $keepIds): void
    {
        foreach (iterator_to_array($xp->query('.//*', $root)) as $el) {
            $id = $el->getAttribute('id');
            if ($id !== '' && ! isset($keepIds[$id])) {
                $el->removeAttribute('id');
            }

            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = $attr->nodeName;
                if (str_starts_with($name, 'data-') || in_array($name, ['contenteditable', 'draggable', 'spellcheck'], true)) {
                    $el->removeAttribute($name);
                }
            }

            if ($el->hasAttribute('class')) {
                $keep = array_filter(preg_split('/\s+/', $el->getAttribute('class')), fn ($c) => str_starts_with($c, 'mc-'));
                $keep ? $el->setAttribute('class', implode(' ', $keep)) : $el->removeAttribute('class');
            }
        }
    }

    protected function inheritedAlign(DOMElement $el): string
    {
        for ($n = $el->parentNode; $n instanceof DOMElement; $n = $n->parentNode) {
            $align = $this->parseStyle($n->getAttribute('style'))['text-align'] ?? null;
            if (in_array($align, ['left', 'center', 'right'], true)) {
                return $align;
            }
        }

        return 'left';
    }

    protected function absolutize(string $url): string
    {
        $url = trim($url);

        return preg_match('#^/(?!/)#', $url) ? url($url) : $url;
    }

    /**
     * Split a style attribute into declarations without being fooled by ';'
     * inside url("data:...;base64,...") or quotes.
     *
     * @return array<string, string>
     */
    protected function parseStyle(string $style): array
    {
        $out = [];
        $buf = '';
        $depth = 0;
        $quote = null;

        $flush = function () use (&$buf, &$out) {
            $pos = strpos($buf, ':');
            if ($pos !== false) {
                $k = strtolower(trim(substr($buf, 0, $pos)));
                $v = trim(substr($buf, $pos + 1));
                if ($k !== '' && $v !== '') {
                    $out[$k] = $v;
                }
            }
            $buf = '';
        };

        foreach (str_split($style) as $ch) {
            if ($quote) {
                $quote = $ch === $quote ? null : $quote;
            } elseif ($ch === '"' || $ch === "'") {
                $quote = $ch;
            } elseif ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth = max(0, $depth - 1);
            } elseif ($ch === ';' && $depth === 0) {
                $flush();
                continue;
            }
            $buf .= $ch;
        }
        $flush();

        return $out;
    }

    protected function styleString(array $style): string
    {
        $out = '';
        foreach ($style as $k => $v) {
            $out .= $k . ':' . $v . ';';
        }

        return $out;
    }

    protected function setStyle(DOMElement $el, array $style): void
    {
        $style ? $el->setAttribute('style', $this->styleString($style)) : $el->removeAttribute('style');
    }

    // ---------------------------------------------------------------------
    // JSON block tree  ->  email HTML
    // ---------------------------------------------------------------------

    /**
     * @param  array  $structure  list of builder elements
     * @param  array  $options    preheader, title, lang, background, content_background
     */
    public function render(array $structure, array $options = []): string
    {
        $font = config('mail-customizer.renderer.font_family', 'Arial, Helvetica, sans-serif');
        $rows = $this->renderBlocks($structure)
            ?: '<tr><td style="padding:20px;font-family:' . e($font) . ';font-size:14px;color:#666666;">&nbsp;</td></tr>';

        return $this->shell($rows, $options);
    }

    /**
     * The document: doctype, head, preheader and the fixed-width container.
     *
     * @param  string  $rows  <tr> rows for inside the container table
     */
    protected function shell(string $rows, array $options = []): string
    {
        $width = $this->contentWidth();
        $bg = Css::color($options['background'] ?? config('mail-customizer.renderer.background'), '#f4f4f4');
        $contentBg = Css::color($options['content_background'] ?? config('mail-customizer.renderer.content_background'), '#ffffff');
        $font = config('mail-customizer.renderer.font_family', 'Arial, Helvetica, sans-serif');
        $lang = e($options['lang'] ?? 'en');
        $title = e($options['title'] ?? '');

        $preheader = '';
        if (! empty($options['preheader'])) {
            // Hidden text shown by inboxes as the preview line; padded so body text doesn't leak in.
            $preheader = '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
                . e($options['preheader']) . str_repeat('&#847;&zwnj;&nbsp;', 40) . '</div>';
        }

        return '<!DOCTYPE html>'
            . '<html lang="' . $lang . '" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">'
            . '<head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta http-equiv="X-UA-Compatible" content="IE=edge">'
            . '<meta name="x-apple-disable-message-reformatting">'
            . '<meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark">'
            . '<title>' . $title . '</title>'
            . $this->fontLinks((array) ($options['fonts'] ?? []))
            . '<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->'
            // Progressive enhancement only: the email must look right with this block stripped.
            . '<style>body{margin:0;padding:0;}img{-ms-interpolation-mode:bicubic;}table{border-collapse:collapse;}'
            . '@media only screen and (max-width:' . $width . 'px){.mc-container{width:100%!important;}.mc-stack{display:block!important;width:100%!important;}}'
            . ($options['extra_css'] ?? '')
            . '</style>'
            . '</head><body>'
            . $preheader
            . '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="' . e($bg) . '" style="background-color:' . e($bg) . ';"><tr><td align="center" style="padding:20px 10px;">'
            . '<!--[if mso]><table role="presentation" width="' . $width . '" align="center" border="0" cellpadding="0" cellspacing="0"><tr><td><![endif]-->'
            . '<table role="presentation" class="mc-container" width="' . $width . '" border="0" cellpadding="0" cellspacing="0" bgcolor="' . e($contentBg) . '" style="width:' . $width . 'px;max-width:100%;background-color:' . e($contentBg) . ';font-family:' . e($font) . ';">'
            . $rows
            . '</table>'
            . '<!--[if mso]></td></tr></table><![endif]-->'
            . '</td></tr></table>'
            . '</body></html>';
    }

    /**
     * Google Fonts <link> for the fonts a template uses. Clients that don't
     * load web fonts fall back to the stack in the inline style.
     */
    protected function fontLinks(array $fonts): string
    {
        $families = [];
        foreach ($fonts as $font) {
            $font = trim((string) $font);
            if (preg_match('/^[A-Za-z0-9 ]{2,40}$/', $font)) {
                $families[] = 'family=' . str_replace(' ', '+', $font) . ':wght@400;700';
            }
        }

        if (! $families) {
            return '';
        }

        return '<link href="https://fonts.googleapis.com/css2?' . implode('&amp;', array_unique($families)) . '&amp;display=swap" rel="stylesheet">';
    }

    /**
     * Render a list of elements to <tr> rows (no wrapper). Used for nesting.
     */
    public function renderBlocks(array $structure): string
    {
        $html = '';

        foreach ($structure as $element) {
            if (is_array($element)) {
                $html .= $this->renderElement($element);
            }
        }

        return $html;
    }

    public function renderElement(array $element): string
    {
        return $this->resolve($element)?->render($element) ?? '';
    }

    /**
     * Plain-text alternative for clients that don't render HTML.
     */
    public function toPlainText(array $structure): string
    {
        $parts = [];

        foreach ($structure as $element) {
            if (! is_array($element)) {
                continue;
            }
            $text = trim((string) $this->resolve($element)?->toText($element));
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    protected function resolve(array $element): ?Block
    {
        $type = (string) ($element['type'] ?? 'text');

        // Unknown types degrade to text so older/custom templates still show their content.
        $key = isset($this->blocks[$type]) ? $type : 'text';

        return $this->instances[$key] ??= new $this->blocks[$key]($this);
    }
}
