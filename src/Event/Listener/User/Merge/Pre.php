<?php

/**
 * Keeps WidgetEditor favourites when user accounts are merged
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Event Listener
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Event\Listener\User\Merge;

use Nails\Cms\Constants;
use Nails\Common\Events\Subscription;
use Nails\Factory;

class Pre extends Subscription
{
    public function __construct()
    {
        //  Auth is not a hard dependency of this module. With no user model there is nothing to merge.
        if (!class_exists(\Nails\Auth\Model\User::class)) {
            return;
        }

        $user = Factory::model('User', \Nails\Auth\Constants::MODULE_SLUG);
        $this
            ->setEvent(\Nails\Auth\Events::USER_MERGE_PRE)
            ->setNamespace($user::getEventNamespace())
            ->setCallback([$this, 'execute']);
    }

    // --------------------------------------------------------------------------

    /**
     * @param int   $keepId
     * @param int[] $mergeIds
     */
    public function execute(int $keepId, array $mergeIds): void
    {
        Factory::service('WidgetEditorFavourite', Constants::MODULE_SLUG)
            ->reassign($keepId, $mergeIds);
    }
}
