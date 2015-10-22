<?php

return array(
    'models' => array(
        'Area' => function () {
            if (class_exists('\App\Cms\Model\Area')) {
                return new \App\Cms\Model\Area();
            } else {
                return new \Nails\Cms\Model\Area();
            }
        },
        'Block' => function () {
            if (class_exists('\App\Cms\Model\Block')) {
                return new \App\Cms\Model\Block();
            } else {
                return new \Nails\Cms\Model\Block();
            }
        },
        'Menu' => function () {
            if (class_exists('\App\Cms\Model\Menu')) {
                return new \App\Cms\Model\Menu();
            } else {
                return new \Nails\Cms\Model\Menu();
            }
        },
        'Page' => function () {
            if (class_exists('\App\Cms\Model\Page')) {
                return new \App\Cms\Model\Page();
            } else {
                return new \Nails\Cms\Model\Page();
            }
        },
        'Slider' => function () {
            if (class_exists('\App\Cms\Model\Slider')) {
                return new \App\Cms\Model\Slider();
            } else {
                return new \Nails\Cms\Model\Slider();
            }
        }
    ),
    'factories' => array(
        'TemplateGroup' => function () {
            if (class_exists('\App\Cms\Template\TemplateGroup')) {
                return new \App\Cms\Template\TemplateGroup();
            } else {
                return new \Nails\Cms\Template\TemplateGroup();
            }
        },
        'TemplateArea' => function () {
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
        'WidgetGroup' => function () {
            if (class_exists('\App\Cms\Widget\WidgetGroup')) {
                return new \App\Cms\Widget\WidgetGroup();
            } else {
                return new \Nails\Cms\Widget\WidgetGroup();
            }
        }
    )
);
