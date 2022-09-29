<?php

namespace Nails\Cms\Admin\Permission\Menu;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse menus';
    }

    public function group(): string
    {
        return 'Menus';
    }
}
