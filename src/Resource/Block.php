<?php

/**
 * This class represents objects dispensed by the Block model
 *
 * @package  Nails\Cms\Resource
 * @category resource
 */

namespace Nails\Cms\Resource;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Cms\Model;
use Nails\Cms\Service\Widget;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource\Entity;
use Nails\Environment;
use Nails\Factory;

/**
 * Class Block
 *
 * @package Nails\Cms\Resource
 */
class Block extends Entity
{
    /** @var string */
    public $type;

    /** @var string */
    public $slug;

    /** @var string */
    public $label;

    /** @var string */
    public $description;

    /** @var string */
    public $located;

    /** @var string */
    public $value;

    // --------------------------------------------------------------------------

    /**
     * Renders a blocks
     *
     * @return string
     */
    public function render(): string
    {
        switch ($this->type) {
            case Model\Block::TYPE_FILE:
            case Model\Block::TYPE_IMAGE:
                return cdnServe($this->value);
                break;

            default:
                return $this->value ?? '';
        }
    }
}
