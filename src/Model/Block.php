<?php

/**
 * This model handle CMS Blocks
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Cms\Constants;
use Nails\Common\Helper\Form;
use Nails\Common\Model\Base;
use Nails\Config;

/**
 * Class Block
 *
 * @package Nails\Cms\Model
 */
class Block extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'cms_block';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Block';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    /**
     * Whether to automatically set slugs or not
     *
     * @var bool
     */
    const AUTO_SET_SLUG = true;

    // --------------------------------------------------------------------------

    /**
     * The various block types
     */
    const TYPE_PLAINTEXT = 'plaintext';
    const TYPE_RICHTEXT  = 'richtext';
    const TYPE_IMAGE     = 'image';
    const TYPE_FILE      = 'file';
    const TYPE_NUMBER    = 'number';
    const TYPE_URL       = 'url';
    const TYPE_EMAIL     = 'email';

    // --------------------------------------------------------------------------

    /**
     * Block constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->searchableFields = ['label', 'value', 'located', 'description'];
    }

    // --------------------------------------------------------------------------

    /**
     * Get the supported types with human labels
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return [
            static::TYPE_PLAINTEXT => 'Plain Text',
            static::TYPE_RICHTEXT  => 'Rich Text',
            static::TYPE_IMAGE     => 'Image (*.jpg, *.png, *.gif)',
            static::TYPE_FILE      => 'File (*.*)',
            static::TYPE_NUMBER    => 'Number',
            static::TYPE_URL       => 'URL',
            static::TYPE_EMAIL     => 'Email',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function describeFields($sTable = null)
    {
        $aFields = parent::describeFields($sTable);

        $aFields['type']->info       = 'Cannot be changed once block is created.';
        $aFields['type']->info_class = 'alert alert-warning';
        $aFields['type']->options    = $this->getTypes();

        $aFields['value']->type       = Form::FIELD_TEXT;
        $aFields['value']->info       = 'Block\'s value can be set once block is created.';
        $aFields['value']->info_class = 'alert alert-warning';

        return $aFields;
    }
}
