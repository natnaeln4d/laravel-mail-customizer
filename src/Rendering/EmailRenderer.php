<?php

namespace Natnaeln4d\MailCustomizer\Rendering;

use InvalidArgumentException;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\Block;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\ButtonBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\ColumnsBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\DividerBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\ImageBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\SpacerBlock;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\TextBlock;

/**
 * Turns the builder's JSON block tree into email-safe HTML:
 * table layout, inline styles, fixed-width wrapper, Outlook fallbacks.
 */
class EmailRenderer
{
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
     * Register (or override) a block type.
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

    /**
     * @param  array  $structure  list of builder elements
     * @param  array  $options    preheader, title, lang, background, content_background
     */
    public function render(array $structure, array $options = []): string
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

        $rows = $this->renderBlocks($structure) ?: '<tr><td style="padding:20px;font-family:' . e($font) . ';font-size:14px;color:#666666;">&nbsp;</td></tr>';

        return '<!DOCTYPE html>'
            . '<html lang="' . $lang . '" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">'
            . '<head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta http-equiv="X-UA-Compatible" content="IE=edge">'
            . '<meta name="x-apple-disable-message-reformatting">'
            . '<meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark">'
            . '<title>' . $title . '</title>'
            . '<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->'
            // Progressive enhancement only: the email must look right with this block stripped (Gmail does for some clients).
            . '<style>body{margin:0;padding:0;}img{-ms-interpolation-mode:bicubic;}table{border-collapse:collapse;}'
            . '@media only screen and (max-width:' . $width . 'px){.mc-container{width:100%!important;}.mc-stack{display:block!important;width:100%!important;}}</style>'
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
