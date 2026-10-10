<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

use Natnaeln4d\MailCustomizer\Rendering\Css;

class DividerBlock extends Block
{
    public function render(array $element): string
    {
        $styles = $this->styles($element);
        $color = Css::color($styles['border-color'] ?? $styles['color'] ?? null, '#cccccc');

        // A 1px-high cell with a background: <hr> renders inconsistently across clients.
        $line = '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"><tr>'
            . '<td height="1" bgcolor="' . e($color) . '" style="font-size:1px;line-height:1px;height:1px;">&nbsp;</td></tr></table>';

        return $this->row($line, ['padding' => $styles['padding'] ?? '10px 20px']);
    }

    public function toText(array $element): string
    {
        return '--------';
    }
}
