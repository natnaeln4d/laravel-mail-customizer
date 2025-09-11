<?php

namespace Natnaeln4d\MailCustomizer\Facades;

use Illuminate\Support\Facades\Facade;

class MailCustomizer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mail-customizer';
    }
}