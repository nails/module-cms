<?php

namespace Nails\Cms\Admin\Permission\Page;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete pages';
    }

    public function group(): string
    {
        return 'Pages';
    }
}
