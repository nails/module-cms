<?php

/**
 * This class represents objects dispensed by the MenuItem model
 *
 * @package  Nails\Cms\Resource\Menu
 * @category resource
 */

namespace Nails\Cms\Resource\Menu;

use Nails\Cms\Constants;
use Nails\Cms\Resource\Menu;
use Nails\Cms\Resource\Page;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Model\Expand;
use Nails\Common\Resource\Entity;
use Nails\Common\Resource\ExpandableField;
use Nails\Factory;

/**
 * Class Item
 *
 * @package Nails\Cms\Resource\Menu
 */
class Item extends Entity
{
    /** @var int */
    public $menu_id;

    /** @var Menu|null */
    public $menu;

    /** @var int */
    public $parent_id;

    /** @var Item|null */
    public $parent;

    /** @var int */
    public $order;

    /** @var int */
    public $page_id;

    /** @var Page */
    public $page;

    /** @var string */
    public $url;

    /** @var string */
    public $label;

    /** @var ExpandableField|null */
    public $children;

    // --------------------------------------------------------------------------

    /**
     * Returns the item's children
     *
     * @return ExpandableField|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function children(): ?ExpandableField
    {
        if (empty($this->children)) {

            /** @var \Nails\Cms\Model\Menu\Item $oModel */
            $oModel = Factory::model('MenuItem', Constants::MODULE_SLUG);
            $oItem  = $oModel->getById($this->id, [new Expand('children')]);

            $this->children = $oItem->children;
        }

        return $this->children;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the item's page
     *
     * @return Page|null
     * @throws FactoryException
     */
    public function page(): ?Page
    {
        if (empty($this->page) && !empty($this->page_id)) {

            /** @var \Nails\Cms\Model\Menu\Item $oModel */
            $oModel = Factory::model('MenuItem', Constants::MODULE_SLUG);
            $oItem  = $oModel->getById($this->id, [new Expand('page')]);

            $this->page = $oItem->page;
        }

        return $this->page;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the menu items URL
     *
     * @return string|null
     * @throws FactoryException
     */
    public function getUrl(): ?string
    {
        if ($this->page_id || $this->page) {
            $oPage = $this->page();
        }

        return $oPage->published->url ?? $this->url;
    }
}
