<?php

/**
 * This model handle CMS Areas
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Cms\Helper\Form;
use Nails\Cms\Service\Widget;
use Nails\Common\Model\Base;
use Nails\Environment;
use Nails\Factory;

/**
 * Class Area
 *
 * @package Nails\Cms\Model
 */
class Area extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'cms_area';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Area';

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
     * Area constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->searchableFields[] = 'slug';
        $this->searchableFields[] = 'description';
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function describeFields($sTable = null)
    {
        $aFields = parent::describeFields($sTable);

        $aFields['widget_data']->label = 'Widgets';
        $aFields['widget_data']->type  = Form::FIELD_WIDGETS;

        return $aFields;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a CMS Area
     *
     * @param mixed $mAreaIdSlug The area's ID or slug
     *
     * @return string
     * @deprecated
     */
    public function render($mAreaIdSlug): string
    {
        /** @var \Nails\Cms\Resource\Area $oArea */
        $oArea = $this->getByIdOrSlug($mAreaIdSlug);
        return !empty($oArea)
            ? $oArea->render()
            : '';
    }

    // --------------------------------------------------------------------------

    /**
     * Render an array of widget data
     *
     * @param string|array $mWidgetData The array of data to render, or a JSON string of data
     *
     * @return string
     * @throws NotFoundException
     */
    public function renderWithData($mWidgetData): string
    {
        /** @var \Nails\Cms\Resource\Area $oArea */
        $oArea = Factory::resource('Area', Constants::MODULE_SLUG, ['widget_data' => $mWidgetData]);
        return $oArea->render();
    }
}
