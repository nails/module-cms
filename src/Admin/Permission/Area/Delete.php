<?php

namespace Nails\Cms\Admin\Permission\Area;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete areas';
    }

    public function group(): string
    {
        return 'Areas';
    }
}
