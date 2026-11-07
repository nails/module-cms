<?php

/**
 * The "CMS Area" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `Area` resolves through ALIASES.
 * The area is a select stored under `areaId`; v1 stored it under `iAreaId`, which upgrade() converts.
 *
 * An Area widget whose area an Area widget further up is already rendering renders nothing, so areas that include
 * each other stop instead of recursing forever. That check is a stack of area ids inside render(), outermost first.
 * The canvas does not use it: preview() names the chosen area, including the area being edited.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Widgets;

use Nails\Cms\Cms\Fields;
use Nails\Cms\Constants;
use Nails\Cms\WidgetEditor\Widget\Base;
use Nails\Factory;

class Area extends Base
{
    const ALIASES = ['Area'];

    protected string $label       = 'CMS Area';
    protected string $summary     = 'Renders the contents of a CMS Area';
    protected string $description = 'Renders the contents of a CMS Area';
    protected string $icon        = 'fa-layer-group';
    protected array $keywords     = ['cms', 'area', 'widgets'];

    /** @var array<string, string>|null Area id => label, loaded once per request */
    private static ?array $areas = null;

    /** @var int[] The areas being rendered, outermost first */
    private static array $rendering = [];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        $areas = $this->areaOptions();
        $field = Fields\Select::make('areaId')->label('Area');

        if ($areas === []) {
            return [
                $field
                    ->options(['' => 'No areas available'])
                    ->info('Create an area in the "Areas" section of admin.', 'alert alert-warning'),
            ];
        }

        return [$field->options(['' => 'Choose an area'] + $areas)];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function upgrade(array $data): array
    {
        if (!array_key_exists('areaId', $data) && array_key_exists('iAreaId', $data)) {
            $data['areaId'] = is_scalar($data['iAreaId']) ? (string) $data['iAreaId'] : '';
        }

        unset($data['iAreaId']);

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $areaId = (int) ($data['areaId'] ?? 0);
        if ($areaId <= 0 || in_array($areaId, self::$rendering, true)) {
            return '';
        }

        self::$rendering[] = $areaId;

        try {
            return $this->renderArea($areaId);
        } finally {
            array_pop(self::$rendering);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * The canvas names the chosen area instead of rendering it.
     *
     * @inheritDoc
     */
    public function preview(array $data, array $context = []): string
    {
        $areaId = (int) ($data['areaId'] ?? 0);
        if ($areaId <= 0) {
            return '<div class="cms-widget cms-widget-area"><p class="cms-we-hint">Choose an area in the inspector.</p></div>';
        }

        $label = $this->areaOptions()[(string) $areaId] ?? '';
        $name  = $label !== '' ? htmlspecialchars($label, ENT_QUOTES) : 'Unknown area';
        $note  = $label !== '' ? 'This area will be rendered here.' : 'This area is no longer available.';

        return '<div class="cms-widget cms-widget-area">' . $this->placeholderBox($name, $note) . '</div>';
    }

    // --------------------------------------------------------------------------

    /**
     * The areas an editor can choose, id => label
     *
     * @return array<string, string>
     */
    protected function areaOptions(): array
    {
        if (self::$areas === null) {
            /** @var \Nails\Cms\Model\Area $model */
            $model       = Factory::model('Area', Constants::MODULE_SLUG);
            self::$areas = [];
            foreach ($model->getAllFlat() as $id => $label) {
                self::$areas[(string) $id] = (string) $label;
            }
        }

        return self::$areas;
    }

    // --------------------------------------------------------------------------

    /**
     * The rendered area
     */
    protected function renderArea(int $areaId): string
    {
        /** @var \Nails\Cms\Model\Area $model */
        $model = Factory::model('Area', Constants::MODULE_SLUG);
        return $model->render($areaId);
    }
}
