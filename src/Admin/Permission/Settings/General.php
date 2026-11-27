<?php

namespace Nails\Cms\Admin\Permission\Settings;

use Nails\Admin\Interfaces\Permission;

class General implements Permission
{
    public function label(): string
    {
        return 'Can manage CMS settings';
    }

    public function group(): string
    {
        return 'Settings';
    }
}
