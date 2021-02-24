<?php

use Nails\Cms\Model;
use Nails\Cms\Resource;
use Nails\Cms\Service;

return [
    'services'  => [
        'Widget'   => function (): Service\Widget {
            if (class_exists('\App\Cms\Service\Widget')) {
                return new \App\Cms\Service\Widget();
            } else {
                return new Service\Widget();
            }
        },
        'Template' => function (): Service\Template {
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
        'Slider'      => function (): Model\Slider {
            if (class_exists('\App\Cms\Model\Slider')) {
                return new \App\Cms\Model\Slider();
            } else {
                return new Model\Slider();
            }
        },
    ],
    'resources' => [
        'Menu'     => function ($mObj): Resource\Menu {
            if (class_exists('\App\Invoice\Resource\Menu')) {
                return new \App\Invoice\Resource\Menu($mObj);
            } else {
                return new Resource\Menu($mObj);
            }
        },
        'MenuItem' => function ($mObj): Resource\Menu\Item {
            if (class_exists('\App\Invoice\Resource\Menu\Item')) {
                return new \App\Invoice\Resource\Menu\Item($mObj);
            } else {
                return new Resource\Menu\Item($mObj);
            }
        },
        'Page'     => function ($mObj): Resource\Page {
            if (class_exists('\App\Invoice\Resource\Page')) {
                return new \App\Invoice\Resource\Page($mObj);
            } else {
                return new Resource\Page($mObj);
            }
        },
    ],
    'factories' => [
        'TemplateGroup'  => function () {
            if (class_exists('\App\Cms\Template\TemplateGroup')) {
                return new \App\Cms\Template\TemplateGroup();
            } else {
                return new \Nails\Cms\Template\TemplateGroup();
            }
        },
        'TemplateArea'   => function () {
            if (class_exists('\App\Cms\Template\TemplateArea')) {
                return new \App\Cms\Template\TemplateArea();
            } else {
                return new \Nails\Cms\Template\TemplateArea();
            }
        },
        'TemplateOption' => function () {
            if (class_exists('\App\Cms\Template\TemplateOption')) {
                return new \App\Cms\Template\TemplateOption();
            } else {
                return new \Nails\Cms\Template\TemplateOption();
            }
        },
        'WidgetGroup'    => function () {
            if (class_exists('\App\Cms\Widget\WidgetGroup')) {
                return new \App\Cms\Widget\WidgetGroup();
            } else {
                return new \Nails\Cms\Widget\WidgetGroup();
            }
        },
    ],
];
