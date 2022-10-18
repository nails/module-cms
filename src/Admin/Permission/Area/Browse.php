<?php

namespace Nails\Cms\Admin\Permission\Area;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse areas';
    }

    public function group(): string
    {
        return 'Areas';
    }
}
