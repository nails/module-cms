<?php

return array(
    'factories' => array(
        'TemplateGroup' => function () {
            return new \Nails\Cms\Template\TemplateGroup();
        },
        'TemplateArea' => function () {
            return new \Nails\Cms\Template\TemplateArea();
        },
        'TemplateOption' => function () {
            return new \Nails\Cms\Template\TemplateOption();
        },
        'WidgetGroup' => function () {
            return new \Nails\Cms\Widget\WidgetGroup();
        }
    )
);
