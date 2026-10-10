<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

use Natnaeln4d\MailCustomizer\Rendering\Css;
use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;

/**
 * A block turns one builder element into email-safe table rows (<tr>...</tr>).
 *
 * Register your own with EmailRenderer::registerBlock('type', YourBlock::class).
 */
abstract class Block
{
    public function __construct(protected EmailRenderer $renderer)
    {
    }

    /**
     * @param  array  $element  {type, content, properties, styles}
     * @return string  one or more <tr> rows
     */
    abstract public function render(array $element): string;

    /**
     * Plain-text version of the block (used for the text/plain alternative part).
     */
    public function toText(array $element): string
    {
        return '';
    }

    protected function props(array $element): array
    {
        $props = $element['properties'] ?? [];

        return is_array($props) ? $props : [];
    }

    protected function styles(array $element): array
    {
        $styles = $element['styles'] ?? [];

        return is_array($styles) ? $styles : [];
    }

    /**
     * Wrap cell content in a full-width row.
     */
    protected function row(string $inner, array $tdStyles = [], string $tdAttributes = ''): string
    {
        return '<tr><td ' . trim($tdAttributes . ' style="' . e(Css::inline($tdStyles)) . '"') . '>' . $inner . '</td></tr>';
    }
}
