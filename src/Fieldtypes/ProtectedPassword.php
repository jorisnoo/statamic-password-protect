<?php

namespace Noo\PasswordProtect\Fieldtypes;

use Statamic\Fieldtypes\Text;

class ProtectedPassword extends Text
{
    public const UNCHANGED = '••••••••••••••••••••••••••••••••';

    protected $component = 'text';

    protected $selectable = false;

    public function preProcess($data)
    {
        return filled($data) ? self::UNCHANGED : null;
    }
}
