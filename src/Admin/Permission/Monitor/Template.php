<?php

namespace Nails\Cms\Admin\Permission\Monitor;

use Nails\Admin\Interfaces\Permission;

class Template implements Permission
{
    public function label(): string
    {
        return 'Can monitor templates';
    }

    public function group(): string
    {
        return 'Monitor';
    }
}
