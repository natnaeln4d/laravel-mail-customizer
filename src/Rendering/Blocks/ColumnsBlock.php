<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

/**
 * Side-by-side columns. Expects properties.columns = [[element, ...], [element, ...]].
 * Columns sit in a nested table; on narrow screens the "mc-stack" class (see the
 * wrapper's media query) makes them full-width.
 */
class ColumnsBlock extends Block
{
    public function render(array $element): string
    {
        $columns = $this->props($element)['columns'] ?? [];
        if (! is_array($columns) || ! $columns) {
            return '';
        }

        $columns = array_slice(array_values($columns), 0, 4);
        $width = (int) floor($this->renderer->contentWidth() / count($columns));

        $cells = '';
        foreach ($columns as $children) {
            $cells .= '<td class="mc-stack" width="' . $width . '" valign="top" style="width:' . $width . 'px;">'
                . '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">'
                . $this->renderer->renderBlocks((array) $children)
                . '</table></td>';
        }

        return '<tr><td><table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"><tr>' . $cells . '</tr></table></td></tr>';
    }

    public function toText(array $element): string
    {
        $parts = [];
        foreach ((array) ($this->props($element)['columns'] ?? []) as $children) {
            $parts[] = $this->renderer->toPlainText((array) $children);
        }

        return implode("\n\n", array_filter($parts));
    }
}
