<?php

namespace Nails\Cms\Settings;

use Nails\Cms\Model\Page;
use Nails\Cms\Service\Driver;
use Nails\Common\Helper\Form;
use Nails\Common\Interfaces;
use Nails\Common\Service\FormValidation;
use Nails\Components\Setting;
use Nails\Cms\Constants;
use Nails\Factory;

/**
 * Class General
 *
 * @package Nails\Cms\Settings
 */
class General implements Interfaces\Component\Settings
{
    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'CMS';
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getPermissions(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        /** @var Page $oPageModel */
        $oPageModel = Factory::model('Page', Constants::MODULE_SLUG);

        /** @var Setting $oHomepage */
        $oHomepage = Factory::factory('ComponentSetting');
        $oHomepage
            ->setKey('homepage')
            ->setType(Form::FIELD_DROPDOWN)
            ->setLabel('Homepage')
            ->setClass('select2')
            ->setOptions(['' => 'No Homepage Selected'] + $oPageModel->getAllFlat([
                    'where' => [
                        [
                            'is_published',
                            true,
                        ],
                    ],
                ]));

        return [
            $oHomepage,
        ];
    }
}
