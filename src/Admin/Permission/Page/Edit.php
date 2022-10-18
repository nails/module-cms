<?php

namespace Nails\Cms\Admin\Permission\Page;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit pages';
    }

    public function group(): string
    {
        return 'Pages';
    }
}
