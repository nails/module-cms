<?php

/**
 * This class represents objects dispensed by the Menu model
 *
 * @package  Nails\Cms\Resource
 * @category resource
 */

namespace Nails\Cms\Resource;

use Nails\Cms\Constants;
use Nails\Cms\Resource\Menu\Item;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Model\Expand;
use Nails\Common\Resource\Entity;
use Nails\Common\Resource\ExpandableField;
use Nails\Factory;

/**
 * Class Menu
 *
 * @package Nails\Cms\Resource
 */
class Menu extends Entity
{
    /** @var string */
    public $slug;

    /** @var string */
    public $label;

    /** @var string */
    public $description;

    /** @var ExpandableField|null */
    public $items;

    // --------------------------------------------------------------------------

    /**
     * Returns the menu's items
     *
     * @return ExpandableField|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function items(): ?ExpandableField
    {
        if (empty($this->items)) {

            /** @var \Nails\Cms\Model\Menu $oModel */
            $oModel = Factory::model('Menu', Constants::MODULE_SLUG);
            $oItem  = $oModel->getById($this->id, [new Expand('items')]);

            $this->items = $oItem->items;
        }

        return $this->items;
    }
}
