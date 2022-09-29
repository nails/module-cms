<?php

namespace Nails\Cms\Admin\Permission\Area;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit areas';
    }

    public function group(): string
    {
        return 'Areas';
    }
}
