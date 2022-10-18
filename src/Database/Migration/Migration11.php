<?php

/**
 * Migration: 11
 * Started:   10/08/2022
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Database\Migration;

use Nails\Common\Interfaces;
use Nails\Common\Traits;
use Nails\Cms\Admin\Permission;

/**
 * Class Migration10
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration11 implements Interfaces\Database\Migration
{
    use Traits\Database\Migration;

    // --------------------------------------------------------------------------

    const MAP = [
        'admin:cms:area:browse'      => Permission\Area\Browse::class,
        'admin:cms:area:create'      => Permission\Area\Create::class,
        'admin:cms:area:edit'        => Permission\Area\Edit::class,
        'admin:cms:area:delete'      => Permission\Area\Delete::class,
        'admin:cms:area:restore'     => '',
        'admin:cms:block:browse'     => Permission\Block\Browse::class,
        'admin:cms:block:create'     => Permission\Block\Create::class,
        'admin:cms:block:edit'       => Permission\Block\Edit::class,
        'admin:cms:block:delete'     => Permission\Block\Delete::class,
        'admin:cms:block:restore'    => '',
        'admin:cms:menu:browse'      => Permission\Menu\Browse::class,
        'admin:cms:menu:create'      => Permission\Menu\Create::class,
        'admin:cms:menu:edit'        => Permission\Menu\Edit::class,
        'admin:cms:menu:delete'      => Permission\Menu\Delete::class,
        'admin:cms:menu:restore'     => '',
        'admin:cms:monitor:widget'   => Permission\Monitor\Widget::class,
        'admin:cms:monitor:template' => Permission\Monitor\Template::class,
        'admin:cms:pages:manage'     => Permission\Page\Browse::class,
        'admin:cms:pages:create'     => Permission\Page\Create::class,
        'admin:cms:pages:edit'       => Permission\Page\Edit::class,
        'admin:cms:pages:preview'    => Permission\Page\Preview::class,
        'admin:cms:pages:delete'     => Permission\Page\Delete::class,
        'admin:cms:pages:restore'    => Permission\Page\Restore::class,
        'admin:cms:pages:destroy'    => '',
    ];

    // --------------------------------------------------------------------------

    /**
     * Execute the migration
     */
    public function execute(): void
    {
        $oResult = $this->query('SELECT id, acl FROM `{{NAILS_DB_PREFIX}}user_group`');
        while ($row = $oResult->fetchObject()) {

            $acl = json_decode($row->acl) ?? [];

            foreach ($acl as &$old) {
                $old = self::MAP[$old] ?? $old;
            }

            $acl = array_filter($acl);
            $acl = array_unique($acl);
            $acl = array_values($acl);

            $this
                ->prepare('UPDATE `{{NAILS_DB_PREFIX}}user_group` SET `acl` = :acl WHERE `id` = :id')
                ->execute([
                    ':id'  => $row->id,
                    ':acl' => json_encode($acl),
                ]);
        }
    }
}
