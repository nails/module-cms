<?php

use Nails\Cms\Factory;
use Nails\Cms\Model;
use Nails\Cms\Resource;
use Nails\Cms\Service;

return [
    'services'  => [
        'MonitorCdn'      => function (): Service\Monitor\Cdn {
            if (class_exists('\App\Cms\Service\Monitor\Cdn')) {
                return new \App\Cms\Service\Monitor\Cdn();
            } else {
                return new Service\Monitor\Cdn();
            }
        },
        'MonitorTemplate' => function (): Service\Monitor\Template {
            if (class_exists('\App\Cms\Service\Monitor\Template')) {
                return new \App\Cms\Service\Monitor\Template();
            } else {
                return new Service\Monitor\Template();
            }
        },
        'MonitorWidget'   => function (): Service\Monitor\Widget {
            if (class_exists('\App\Cms\Service\Monitor\Widget')) {
                return new \App\Cms\Service\Monitor\Widget();
            } else {
                return new Service\Monitor\Widget();
            }
        },
        'Widget'          => function (): Service\Widget {
            if (class_exists('\App\Cms\Service\Widget')) {
                return new \App\Cms\Service\Widget();
            } else {
                return new Service\Widget();
            }
        },
        'Template'        => function (): Service\Template {
            if (class_exists('\App\Cms\Service\Template')) {
                return new \App\Cms\Service\Template();
            } else {
                return new Service\Template();
            }
        },
    ],
    'models'    => [
        'Area'        => function (): Model\Area {
            if (class_exists('\App\Cms\Model\Area')) {
                return new \App\Cms\Model\Area();
            } else {
                return new Model\Area();
            }
        },
        'Block'       => function (): Model\Block {
            if (class_exists('\App\Cms\Model\Block')) {
                return new \App\Cms\Model\Block();
            } else {
                return new Model\Block();
            }
        },
        'Menu'        => function (): Model\Menu {
            if (class_exists('\App\Cms\Model\Menu')) {
                return new \App\Cms\Model\Menu();
            } else {
                return new Model\Menu();
            }
        },
        'MenuItem'    => function (): Model\Menu\Item {
            if (class_exists('\App\Cms\Model\Menu\Item')) {
                return new \App\Cms\Model\Menu\Item();
            } else {
                return new Model\Menu\Item();
            }
        },
        'Page'        => function (): Model\Page {
            if (class_exists('\App\Cms\Model\Page')) {
                return new \App\Cms\Model\Page();
            } else {
                return new Model\Page();
            }
        },
        'PagePreview' => function (): Model\Page\Preview {
            if (class_exists('\App\Cms\Model\Page\Preview')) {
                return new \App\Cms\Model\Page\Preview();
            } else {
                return new Model\Page\Preview();
            }
        },
    ],
    'resources' => [
        'Area'               => function ($resource, $model): Resource\Area {
            if (class_exists('\App\Cms\Resource\Area')) {
                return new \App\Cms\Resource\Area($resource, $model);
            } else {
                return new Resource\Area($resource, $model);
            }
        },
        'Block'              => function ($resource, $model): Resource\Block {
            if (class_exists('\App\Cms\Resource\Block')) {
                return new \App\Cms\Resource\Block($resource, $model);
            } else {
                return new Resource\Block($resource, $model);
            }
        },
        'Menu'               => function ($resource, $model): Resource\Menu {
            if (class_exists('\App\Cms\Resource\Menu')) {
                return new \App\Cms\Resource\Menu($resource, $model);
            } else {
                return new Resource\Menu($resource, $model);
            }
        },
        'MenuItem'           => function ($resource, $model): Resource\Menu\Item {
            if (class_exists('\App\Cms\Resource\Menu\Item')) {
                return new \App\Cms\Resource\Menu\Item($resource, $model);
            } else {
                return new Resource\Menu\Item($resource, $model);
            }
        },
        'Page'               => function ($resource, $model): Resource\Page {
            if (class_exists('\App\Cms\Resource\Page')) {
                return new \App\Cms\Resource\Page($resource, $model);
            } else {
                return new Resource\Page($resource, $model);
            }
        },
        'PageData'           => function ($resource, $model = null): Resource\Page\Data {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Cms\Resource\Page\Data')) {
                return new \App\Cms\Resource\Page\Data($resource);
            } else {
                return new Resource\Page\Data($resource);
            }
        },
        'PageDataBreadcrumb' => function ($resource, $model = null): Resource\Page\Data\Breadcrumb {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Cms\Resource\Page\Data\Breadcrumb')) {
                return new \App\Cms\Resource\Page\Data\Breadcrumb($resource);
            } else {
                return new Resource\Page\Data\Breadcrumb($resource);
            }
        },
    ],
    'factories' => [
        'ModelFieldWidgets'  => function (): Factory\Model\Field\Widgets {
            if (class_exists('\App\Cms\Factory\Model\Field\Widgets')) {
                return new \App\Cms\Factory\Model\Field\Widgets();
            } else {
                return new Factory\Model\Field\Widgets();
            }
        },
        'MonitorDetail'      => function (string $sSlug, array $aUsages): Factory\Monitor\Detail {
            if (class_exists('\App\Cms\Factory\Monitor\Detail')) {
                return new \App\Cms\Factory\Monitor\Detail($sSlug, $aUsages);
            } else {
                return new Factory\Monitor\Detail($sSlug, $aUsages);
            }
        },
        'MonitorDetailUsage' => function (
            string $sLabel,
            ?string $sUrlView,
            ?string $sUrlEdit
        ): Factory\Monitor\Detail\Usage {
            if (class_exists('\App\Cms\Factory\Monitor\Detail\Usage')) {
                return new \App\Cms\Factory\Monitor\Detail\Usage($sLabel, $sUrlView, $sUrlEdit);
            } else {
                return new Factory\Monitor\Detail\Usage($sLabel, $sUrlView, $sUrlEdit);
            }
        },
        'MonitorItem'        => function (
            string $sSlug,
            string $sLabel,
            string $sDescription,
            int $iUsages,
            bool $bIsDeprecated = false,
            string $sAlternative = ''
        ): Factory\Monitor\Item {
            if (class_exists('\App\Cms\Factory\Monitor\Item')) {
                return new \App\Cms\Factory\Monitor\Item($sSlug, $sLabel, $sDescription, $iUsages, $bIsDeprecated, $sAlternative);
            } else {
                return new Factory\Monitor\Item($sSlug, $sLabel, $sDescription, $iUsages, $bIsDeprecated, $sAlternative);
            }
        },
        'TemplateGroup'      => function (): \Nails\Cms\Template\TemplateGroup {
            if (class_exists('\App\Cms\Template\TemplateGroup')) {
                return new \App\Cms\Template\TemplateGroup();
            } else {
                return new \Nails\Cms\Template\TemplateGroup();
            }
        },
        'TemplateArea'       => function (): \Nails\Cms\Template\TemplateArea {
            if (class_exists('\App\Cms\Template\TemplateArea')) {
                return new \App\Cms\Template\TemplateArea();
            } else {
                return new \Nails\Cms\Template\TemplateArea();
            }
        },
        'TemplateOption'     => function (): \Nails\Cms\Template\TemplateOption {
            if (class_exists('\App\Cms\Template\TemplateOption')) {
                return new \App\Cms\Template\TemplateOption();
            } else {
                return new \Nails\Cms\Template\TemplateOption();
            }
        },
        'WidgetGroup'        => function (): \Nails\Cms\Widget\WidgetGroup {
            if (class_exists('\App\Cms\Widget\WidgetGroup')) {
                return new \App\Cms\Widget\WidgetGroup();
            } else {
                return new \Nails\Cms\Widget\WidgetGroup();
            }
        },
    ],
];
