<?php

namespace Nails\Cms\Admin\Permission\Monitor;

use Nails\Admin\Interfaces\Permission;

class Widget implements Permission
{
    public function label(): string
    {
        return 'Can monitor widgets';
    }

    public function group(): string
    {
        return 'Monitor';
    }
}
