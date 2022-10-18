<?php

namespace Nails\Cms\Admin\Permission\Menu;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit menus';
    }

    public function group(): string
    {
        return 'Menus';
    }
}
