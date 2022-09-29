<?php

namespace Nails\Cms\Admin\Permission\Block;

use Nails\Admin\Interfaces\Permission;

class Create implements Permission
{
    public function label(): string
    {
        return 'Can create blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
