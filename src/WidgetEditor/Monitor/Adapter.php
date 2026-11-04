<?php

/**
 * Presents a WidgetEditor (v2) widget to the v1 widget monitors
 *
 * App monitors type-hint the v1 widget interface, which PHP will not let us widen, so the monitor service
 * passes them this adapter. It is only used for counting usages: the v1 members which need v1 state
 * (static flags, rendering, editor HTML) are inert.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Monitor
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Monitor;

use Nails\Cms\Interfaces;
use Nails\Cms\WidgetEditor\Interfaces\Widget as WidgetInterface;
use Nails\Cms\WidgetEditor\Status;

class Adapter implements Interfaces\Widget
{
    /**
     * @param string   $slug    The canonical slug
     * @param string[] $aliases Earlier slugs which count towards the same widget
     */
    public function __construct(
        private WidgetInterface $widget,
        private string $slug,
        private array $aliases = []
    ) {
    }

    // --------------------------------------------------------------------------

    /**
     * The widget this adapter presents
     */
    public function getWidget(): WidgetInterface
    {
        return $this->widget;
    }

    // --------------------------------------------------------------------------

    /**
     * One adapter per slug (canonical first, then aliases), so a monitor can count each of them
     *
     * The service runs each monitor once per slug and sums the results (D-monitors: aliases count
     * until data is remapped).
     *
     * @return self[]
     */
    public function getMonitoredWidgets(): array
    {
        return array_map(
            fn(string $slug) => new self($this->widget, $slug),
            array_values(array_unique(array_merge([$this->slug], $this->aliases)))
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the widget is deprecated. Use this instance method: the static flag cannot see the widget.
     */
    public function isWidgetDeprecated(): bool
    {
        return $this->widget::getStatus() === Status::DEPRECATED;
    }

    // --------------------------------------------------------------------------

    public function getWidgetAlternative(): string
    {
        return $this->widget::getAlternative() ?? '';
    }

    // --------------------------------------------------------------------------

    public static function isDisabled(): bool
    {
        return false;
    }

    public static function isHidden(): bool
    {
        return false;
    }

    public static function isDeprecated(): bool
    {
        return false;
    }

    public static function alternative(): string
    {
        return '';
    }

    public static function detectPath(): ?string
    {
        return null;
    }

    public static function getFilePath($sFile)
    {
        return null;
    }

    public function getLabel(): string
    {
        return $this->widget->getLabel();
    }

    public function getIcon(): string
    {
        return $this->widget->getIcon();
    }

    public function getDescription(): string
    {
        return $this->widget->getDescription();
    }

    public function getKeywords(): string
    {
        return implode(',', $this->widget->getKeywords());
    }

    public function getGrouping(): string
    {
        return $this->widget->getGroups()[0] ?? '';
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getScreenshot(): string
    {
        foreach ($this->widget->getScreenshots() as $screenshot) {
            if (is_string($screenshot)) {
                return $screenshot;
            }
        }

        return '';
    }

    public function getPath(): string
    {
        return '';
    }

    public function getRenderAssets(): array
    {
        return [];
    }

    public function getEditorAssets(): array
    {
        return [];
    }

    public function getCallbacks(string $sType = '')
    {
        return $sType === '' ? (object) ['dropped' => '', 'removed' => ''] : '';
    }

    public function getEditor(array $aWidgetData = []): string
    {
        return '';
    }

    public function render(array $aWidgetData = []): string
    {
        return '';
    }

    public function toJson(int $iJsonOptions = 0, int $iJsonDepth = 512)
    {
        return json_encode($this->toArray(), $iJsonOptions, $iJsonDepth);
    }

    public function toArray(): array
    {
        return [
            'label'         => $this->getLabel(),
            'slug'          => $this->slug,
            'is_deprecated' => $this->isWidgetDeprecated(),
            'alternative'   => $this->getWidgetAlternative(),
        ];
    }
}
