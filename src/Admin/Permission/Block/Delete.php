<?php

namespace Nails\Cms\Admin\Permission\Block;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
