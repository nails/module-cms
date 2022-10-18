<?php

namespace Nails\Cms\Admin\Permission\Block;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
