<?php

namespace Nails\Cms\Admin\Permission\Block;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
