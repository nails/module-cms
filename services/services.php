<?php

return array(
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
