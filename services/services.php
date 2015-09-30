<?php

return array(
    'factories' => array(
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
