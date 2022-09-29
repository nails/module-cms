<?php

namespace Nails\Cms\Admin\Permission\Page;

use Nails\Admin\Interfaces\Permission;

class Create implements Permission
{
    public function label(): string
    {
        return 'Can create pages';
    }

    public function group(): string
    {
        return 'Pages';
    }
}
