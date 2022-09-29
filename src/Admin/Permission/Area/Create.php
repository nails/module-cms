<?php

namespace Nails\Cms\Admin\Permission\Area;

use Nails\Admin\Interfaces\Permission;

class Create implements Permission
{
    public function label(): string
    {
        return 'Can create areas';
    }

    public function group(): string
    {
        return 'Areas';
    }
}
