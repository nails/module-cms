<?php

namespace Nails\Cms\Admin\Permission\Page;

use Nails\Admin\Interfaces\Permission;

class Restore implements Permission
{
    public function label(): string
    {
        return 'Can restore pages';
    }

    public function group(): string
    {
        return 'Pages';
    }
}
