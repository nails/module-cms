<?php

namespace Nails\Cms\Admin\Permission\Page;

use Nails\Admin\Interfaces\Permission;

class Preview implements Permission
{
    public function label(): string
    {
        return 'Can preview pages';
    }

    public function group(): string
    {
        return 'Pages';
    }
}
