<?php

namespace Natnaeln4d\MailCustomizer\Rendering\Blocks;

use Natnaeln4d\MailCustomizer\Rendering\Css;

class SpacerBlock extends Block
{
    public function render(array $element): string
    {
        $height = max(1, min(Css::px($this->props($element)['height'] ?? null, 20), 400));

        return '<tr><td height="' . $height . '" style="height:' . $height . 'px;font-size:' . $height . 'px;line-height:' . $height . 'px;">&nbsp;</td></tr>';
    }
}
