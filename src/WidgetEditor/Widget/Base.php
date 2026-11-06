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
use Nails\Cms\WidgetEditor\Service\Fields;
use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Cms\WidgetEditor\Status;

abstract class Base implements Interfaces\Widget
{
    /**
     * The context key holding the path of the node being rendered, relative to its top-level instance.
     * Each segment is a slot name or an index, e.g. ['columns', '1']. Top-level instances have an empty path.
     */
    public const PATH_KEY = '_cms_path';

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

    /**
     * The data a new instance starts with: each field's default, then $defaults over them.
     */
    public function getDefaults(): array
    {
        $fields = $this->getFields();

        return is_array($fields)
            ? array_replace(Fields::defaults(Fields::validate($fields)), $this->defaults)
            : $this->defaults;
    }

    // --------------------------------------------------------------------------

    /**
     * Upgrades the data, then fills any keys it is missing from getDefaults(). Pure: the result is never saved.
     */
    public function normalise(array $data): array
    {
        $data   = array_replace($this->getDefaults(), $this->upgrade($data));
        $fields = $this->getFields();

        return is_array($fields) ? Fields::fill(Fields::validate($fields), $data) : $data;
    }

    // --------------------------------------------------------------------------

    /**
     * A widget whose stored shape changed (for example from its v1 widget) overrides this. Data that is already
     * in the current shape must come back unchanged.
     */
    public function upgrade(array $data): array
    {
        return $data;
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
     * Scripts the editor runs around the inspector: ['dropped' => ..., 'removed' => ...]. Only v1 bridges have
     * them (see Traits\Legacy); a v2 widget has none.
     *
     * @return array{dropped: string, removed: string}|array{}
     */
    public function getCallbacks(): array
    {
        return [];
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
        foreach (array_values((array) ($data[$name] ?? [])) as $index => $child) {
            $context                = $this->context;
            $context[self::PATH_KEY] = [...$this->nodePath(), $name, (string) $index];

            $output .= $this->registry->renderNode(
                $child,
                $this->mode,
                $this->depth + 1,
                $context
            );
        }

        return $output;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether this instance is being rendered for the canvas (preview) rather than the public site
     */
    protected function isPreview(): bool
    {
        return $this->mode === Registry::MODE_PREVIEW;
    }

    // --------------------------------------------------------------------------

    /**
     * The path of this instance, relative to its top-level instance
     *
     * @return string[]
     */
    private function nodePath(): array
    {
        return array_values((array) ($this->context[self::PATH_KEY] ?? []));
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a field's value in an element, and marks it for inline editing in the canvas
     *
     * Use it in render() so that the same markup serves the public site and the canvas. Outside the canvas
     * the element carries no editor attributes. In the canvas it carries data-cms-field, data-cms-edit and
     * data-cms-path, which the canvas uses to write edits back to the right instance.
     *
     * When getFields() declares the key, the field type decides how it is edited, and $edit may be omitted. A
     * type that is not edited on the canvas (a textarea, a select), or an $edit that disagrees with the type,
     * throws.
     *
     * @param string               $field The data key the value is stored under
     * @param string               $tag   The element to render, e.g. h2
     * @param mixed                $value The field's value. For 'text' it is escaped; for 'rich' it is HTML
     * @param array<string, mixed> $attrs Extra attributes for the element
     * @param string|null          $edit  'text' (plain text), 'rich' (HTML, full menu) or 'rich-basic' (HTML,
     *                                    formatting only). Defaults to the field type's, or 'text'.
     *
     * @throws \InvalidArgumentException
     */
    protected function editable(
        string $field,
        string $tag,
        mixed $value,
        array $attrs = [],
        ?string $edit = null
    ): string {
        $edit = $this->canvasEdit($field, $edit);
        if (!in_array($edit, ['text', 'rich', 'rich-basic'], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown editable type "%s"', $edit));
        }
        if (!preg_match('/^[a-z][a-z0-9]*$/', $tag)) {
            throw new \InvalidArgumentException(sprintf('Invalid tag "%s"', $tag));
        }

        $content = (string) $value;
        if ($edit === 'text') {
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        if ($this->isPreview()) {
            $attrs['data-cms-field'] = $field;
            $attrs['data-cms-edit']  = $edit;
            $attrs['data-cms-path']  = implode('/', $this->nodePath());
        }

        $attributes = '';
        foreach ($attrs as $name => $attrValue) {
            $attributes .= ' ' . $name . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<' . $tag . $attributes . '>' . $content . '</' . $tag . '>';
    }

    // --------------------------------------------------------------------------

    /**
     * How editable() edits a key: the declared field's mode, checked against $edit when both are given
     *
     * @throws \InvalidArgumentException
     */
    private function canvasEdit(string $key, ?string $edit): string
    {
        $fields   = $this->getFields();
        $declared = is_array($fields) ? Fields::find(Fields::validate($fields), $key) : null;

        if ($declared === null) {
            return $edit ?? 'text';
        }

        $mode = $declared->getCanvasEdit();
        if ($mode === null) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is a %s, which is edited in the inspector, not on the canvas',
                $key,
                $declared::getType()
            ));
        }

        if ($edit !== null && $edit !== $mode) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is edited as "%s" on the canvas, not "%s"',
                $key,
                $mode,
                $edit
            ));
        }

        return $mode;
    }
}
