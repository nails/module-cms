<?php

namespace Nails\Cms\Traits\Database\Seeder;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Common\Exception\Console\SeederExistsException;
use Nails\Common\Exception\NailsException;

/**
 * Trait Cms
 *
 * @package Nails\Cms\Traits\Database\Seeder
 */
trait Cms
{
    /*
     * Supported widget candidates and their associated fields/values.
     */
    protected $aCmsWidgetData = [
        'blockquote' => [
            'quote'     => 'loremSentence',
            'cite_text' => 'loremWord',
            'cite_url'  => 'url',
        ],
        'richtext'   => [
            'body' => 'loremHtml',
        ],
    ];

    // --------------------------------------------------------------------------

    /**
     * Returns random CMS widget data
     *
     * @param int   $iMax     The maximum number of widgets to return
     * @param array $aWidgets The widgets to consider
     *
     * @return string
     * @throws NotFoundException
     */
    protected function randomCmsWidgets(int $iMax = 1, array $aWidgets = []): string
    {
        $aWidgets    = empty($aWidgets) ? array_keys($this->aCmsWidgetData) : $aWidgets;
        $aCandidates = [];

        while (count($aCandidates) < $iMax) {
            $aCandidates[] = $aWidgets[array_rand($aWidgets)];
        }

        $aOut = [];
        foreach ($aCandidates as $sSlug) {
            $aOut[] = [
                'slug' => $sSlug,
                'data' => $this->getCmsWidgetData($sSlug),
            ];
        }

        return json_encode($aOut);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the data for a given widget
     *
     * @param string $sSlug The widget to generate data for
     *
     * @return array
     * @throws NotFoundException
     */
    protected function getCmsWidgetData(string $sSlug): array
    {
        if (!array_key_exists($sSlug, $this->aCmsWidgetData)) {
            throw new NotFoundException(sprintf(
                'Sample data has not been defined for widget "%s"',
                $sSlug
            ));
        }

        $aOut = [];

        foreach ($this->aCmsWidgetData[$sSlug] as $sKey => $mValue) {
            if (is_callable($mValue)) {
                $aOut[$sKey] = call_user_func($mValue);

            } elseif (is_string($mValue) && is_callable([$this, $mValue])) {
                $aOut[$sKey] = call_user_func([$this, $mValue]);

            } else {
                $aOut[$sKey] = $mValue;
            }
        }

        return $aOut;
    }
}
