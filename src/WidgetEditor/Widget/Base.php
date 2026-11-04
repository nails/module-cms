<?php

/**
 * Abstract base for WidgetEditor (v2) widgets
 *
 * Every method has a default; a widget overrides what it needs. Constructors must have no side effects,
 * because the catalogue instantiates widgets on every request.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Widget;

use Nails\Cms\WidgetEditor\Exception\RegistryException;
use Nails\Cms\WidgetEditor\Interfaces;
use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Cms\WidgetEditor\Status;

abstract class Base implements Interfaces\Widget
{
    /** The widget's status; see Status */
    const STATUS = Status::ACTIVE;

    /** Previous slugs this widget answers to */
    const ALIASES = [];

    /** The replacement named in the deprecation warning */
    const ALTERNATIVE = null;

    // --------------------------------------------------------------------------

    protected string $label = '';
    protected string $summary = '';
    protected string $description = '';
    protected string $icon = '';

    /** @var string[] */
    protected array $groups = [];

    /** @var string[] */
    protected array $keywords = [];

    /** @var array<int, string|int> */
    protected array $screenshots = [];

    /** @var array<string, mixed> */
    protected array $defaults = [];

    /** @var array{render: array, editor: array} */
    protected array $assets = ['render' => [], 'editor' => []];

    // --------------------------------------------------------------------------

    /** Set by the registry; see attach() */
    private ?string $slug = null;
    private ?Registry $registry = null;
    private string $mode = Registry::MODE_RENDER;
    private int $depth = 0;

    /** @var array<string, mixed> */
    private array $context = [];

    // --------------------------------------------------------------------------

    /**
     * Binds the instance to the registry and to the render call it is serving
     *
     * Called by the registry; widgets do not call this.
     *
     * @internal
     */
    public function attach(Registry $registry, string $slug, string $mode, int $depth, array $context): void
    {
        $this->registry = $registry;
        $this->slug     = $slug;
        $this->mode     = $mode;
        $this->depth    = $depth;
        $this->context  = $context;
    }

    // --------------------------------------------------------------------------

    /**
     * The canonical slug of the widget (the root slug, for an overridden widget)
     */
    final public function getSlug(): string
    {
        return $this->slug ?? Registry::slugFor(static::class);
    }

    // --------------------------------------------------------------------------

    public static function getAliases(): array
    {
        return static::ALIASES;
    }

    // --------------------------------------------------------------------------

    public static function getStatus(): Status
    {
        return static::STATUS;
    }

    // --------------------------------------------------------------------------

    public static function getAlternative(): ?string
    {
        return static::ALTERNATIVE;
    }

    // --------------------------------------------------------------------------

    public function getLabel(): string
    {
        return $this->label !== '' ? $this->label : basename(str_replace('\\', '/', static::class));
    }

    // --------------------------------------------------------------------------

    public function getSummary(): string
    {
        return $this->summary;
    }

    // --------------------------------------------------------------------------

    public function getDescription(): string
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    public function getIcon(): string
    {
        return $this->icon !== '' ? $this->icon : 'fa-cube';
    }

    // --------------------------------------------------------------------------

    public function getGroups(): array
    {
        return $this->groups;
    }

    // --------------------------------------------------------------------------

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    // --------------------------------------------------------------------------

    public function getScreenshots(): array
    {
        return $this->screenshots;
    }

    // --------------------------------------------------------------------------

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    // --------------------------------------------------------------------------

    /**
     * Fills any keys the data is missing from getDefaults(). Pure: the result is never saved.
     */
    public function normalise(array $data): array
    {
        return array_replace($this->getDefaults(), $data);
    }

    // --------------------------------------------------------------------------

    /**
     * The canvas markup. Widgets whose render() would look wrong without JavaScript or the front-end
     * request override this.
     */
    public function preview(array $data, array $context = []): string
    {
        return $this->render($data);
    }

    // --------------------------------------------------------------------------

    public function getFields(): ?array
    {
        return null;
    }

    // --------------------------------------------------------------------------

    public function getEditorForm(array $data): ?string
    {
        return null;
    }

    // --------------------------------------------------------------------------

    public function getSlots(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    public function getAssets(): array
    {
        return $this->assets;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the children stored in a named slot, in order, in the same mode as this call
     *
     * @param array<string, mixed> $data The widget's data, as passed to render() or preview()
     *
     * @throws RegistryException
     */
    protected function renderSlot(string $name, array $data): string
    {
        if ($this->registry === null) {
            throw new RegistryException(sprintf(
                'renderSlot() on "%s" must be called through the registry',
                static::class
            ));
        }

        $output = '';
        foreach ((array) ($data[$name] ?? []) as $child) {
            $output .= $this->registry->renderNode(
                $child,
                $this->mode,
                $this->depth + 1,
                $this->context
            );
        }

        return $output;
    }
}
