<?php

namespace Nails\Cms\Admin\Permission\Menu;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete menus';
    }

    public function group(): string
    {
        return 'Menus';
    }
}
