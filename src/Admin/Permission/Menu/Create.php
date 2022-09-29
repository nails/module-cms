<?php

namespace Nails\Cms\Admin\Permission\Menu;

use Nails\Admin\Interfaces\Permission;

class Create implements Permission
{
    public function label(): string
    {
        return 'Can create menus';
    }

    public function group(): string
    {
        return 'Menus';
    }
}
