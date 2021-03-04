<?php

/**
 * This class provides CMS Block management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

use Nails\Common\Exception\NailsException;
use Nails\Admin\Controller\DefaultController;
use Nails\Cms\Constants;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Resource;
use Nails\Common\Service\Input;
use Nails\Factory;

/**
 * Class Block
 *
 * @package Nails\Admin\Cms
 */
class Block extends DefaultController
{
    const CONFIG_MODEL_NAME           = 'Block';
    const CONFIG_MODEL_PROVIDER       = Constants::MODULE_SLUG;
    const CONFIG_PERMISSION           = 'cms:blocks';
    const CONFIG_SIDEBAR_GROUP        = 'CMS';
    const CONFIG_SIDEBAR_ICON         = 'fa-file-alt';
    const CONFIG_INDEX_FIELDS         = [
        'Label'    => null,
        'Located'  => 'located',
        'Type'     => null,
        'Value'    => null,
        'Modified' => 'modified',
    ];
    const CONFIG_EDIT_READONLY_FIELDS = [
        'type',
    ];
    const CONFIG_CREATE_READONLY_FIELDS = [
        'value',
    ];

    // --------------------------------------------------------------------------

    /**
     * Block constructor.
     *
     * @throws NailsException
     */
    public function __construct()
    {
        parent::__construct();

        /** @var \Nails\Cms\Model\Block $oModel */
        $oModel = static::getModel();

        $this->aConfig['INDEX_FIELDS']['Label'] = function (\Nails\Cms\Resource\Block $oBlock) {
            return sprintf(
                '%s (<code>%s</code>)<small>%s</small>',
                $oBlock->label,
                $oBlock->slug,
                $oBlock->description
            );
        };

        $this->aConfig['INDEX_FIELDS']['Type'] = function (\Nails\Cms\Resource\Block $oBlock) use ($oModel) {
            return $oModel->getTypes()[$oBlock->type] ?? $oBlock->type;
        };

        $this->aConfig['INDEX_FIELDS']['Value'] = function (\Nails\Cms\Resource\Block $oBlock) use ($oModel) {
            switch ($oBlock->type) {
                case $oModel::TYPE_IMAGE:
                    return img(cdnCrop((int) $oBlock->value ?: null, 50, 50));
                    break;

                case $oModel::TYPE_FILE:
                    return anchor(cdnServe((int) $oBlock->value ?: null, true), 'Download', 'class="btn btn-xs btn-default"');
                    break;

                default:
                    return character_limiter(strip_tags($oBlock->value), 100);
                    break;
            }
        };
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    protected function loadEditViewData(Resource $oItem = null): void
    {
        parent::loadEditViewData($oItem);
        $this->data['aTypes'] = static::getModel()->getTypes();
    }

    // --------------------------------------------------------------------------

    protected function runFormValidation(string $sMode, array $aOverrides = []): void
    {
        if ($sMode === static::EDIT_MODE_EDIT) {

            /** @var Input $oInput */
            $oInput = Factory::service('Input');
            /** @var \Nails\Cms\Model\Block $oModel */
            $oModel = static::getModel();

            switch ($oInput->post('type')) {
                case $oModel::TYPE_EMAIL:
                    if (!valid_email($oInput->post('value'))) {
                        throw new ValidationException('Block must contain a valid email address.');
                    }
                    break;

                case $oModel::TYPE_URL:
                    if (!filter_var($oInput->post('value'), FILTER_VALIDATE_URL)) {
                        throw new ValidationException('Block must contain a valid URL.');
                    }
                    break;

                case $oModel::TYPE_FILE:
                case $oModel::TYPE_IMAGE:
                case $oModel::TYPE_NUMBER:
                    if (!is_numeric($oInput->post('value'))) {
                        throw new ValidationException('Block must be a numeric.');
                    }
                    break;

                default:
                    break;
            }

        } else {
            parent::runFormValidation($sMode, $aOverrides);
        }
    }

    // --------------------------------------------------------------------------

    protected function getPostObject(): array
    {
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        return $oInput->post('mode') === 'edit'
            ? ['value' => $oInput->post('value')]
            : parent::getPostObject();
    }
}
