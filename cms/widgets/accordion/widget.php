<?php

/**
 * This class is the "Accordion" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class Accordion extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Accordion';
        $this->icon        = 'fa-list-alt';
        $this->description = 'A collapsible accordion component.';
        $this->keywords    = 'accordion';
        $this->data        = [
            'title' => [],
            'body'  => [],
            'state' => [],
        ];
    }

    // --------------------------------------------------------------------------

    protected function populateWidgetData(array &$aWidgetData)
    {
        parent::populateWidgetData($aWidgetData);

        $aWidgetData['sUuid'] = '_' . md5(microtime(true) + rand(1, 1000));

        if (!empty($aWidgetData['panels'])) {

            //  Developer provided panels
            $aWidgetData['aPanels'] = array_values($aWidgetData['panels']);

        } else {

            //  CMS provided panels
            $aWidgetData['aPanels'] = [];
            for ($i = 0; $i < count($aWidgetData['title']); $i++) {

                $aWidgetData['aPanels'][] = [
                    'title' => getFromArray($i, $aWidgetData['title']),
                    'body'  => getFromArray($i, $aWidgetData['body']),
                    'state' => getFromArray($i, $aWidgetData['state'], 'CLOSED'),
                ];
            }
        }
    }
}
