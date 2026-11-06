<?php

/**
 * Abstract base for WidgetEditor (v2) field types
 *
 * Holds the settings every field shares and the fluent methods that set them:
 *
 *     Text::make('title')->label('Title')->required()->tip('Shown as the h2')
 *
 * A type implements getType() and adds its own settings through config().
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Field;

use Nails\Cms\WidgetEditor\Interfaces;

abstract class Base implements Interfaces\Field
{
    /** Keys are stored as JSON object keys and read by PHP, so they follow the same shape as a PHP variable */
    private const KEY_PATTERN = '/^[A-Za-z][A-Za-z0-9_]*$/';

    // --------------------------------------------------------------------------

    protected string $label = '';
    protected string $tip = '';
    protected string $subtitle = '';
    protected string $info = '';
    protected string $infoClass = '';
    protected bool $required = false;
    protected bool $wide = false;

    /** @var array<string, mixed> */
    protected array $visibleWhen = [];

    protected mixed $default = null;
    protected bool $hasDefault = false;

    // --------------------------------------------------------------------------

    /**
     * @throws \InvalidArgumentException
     */
    final public function __construct(protected readonly string $key)
    {
        if (!preg_match(self::KEY_PATTERN, $key)) {
            throw new \InvalidArgumentException(sprintf(
                'Field key "%s" must start with a letter and use only letters, digits and underscores',
                $key
            ));
        }
    }

    // --------------------------------------------------------------------------

    /**
     * @throws \InvalidArgumentException
     */
    public static function make(string $key): static
    {
        return new static($key);
    }

    // --------------------------------------------------------------------------

    public static function getAssets(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function tip(string $tip): static
    {
        $this->tip = $tip;
        return $this;
    }

    /**
     * Quiet text under the label. Use info() for a box under the control.
     */
    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * Extra context under the control. $class is added to the info element, for example 'alert alert-info'.
     */
    public function info(string $info, string $class = ''): static
    {
        $this->info      = $info;
        $this->infoClass = $class;
        return $this;
    }

    /**
     * Marks the field with an asterisk. It never blocks a save.
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    /**
     * Shows the control as a one-line summary in the inspector, with the full control in a modal
     */
    public function wide(bool $wide = true): static
    {
        $this->wide = $wide;
        return $this;
    }

    /**
     * Shows the field only while every named sibling has the given value, compared strictly
     *
     * @param array<string, mixed> $conditions Sibling key => value
     */
    public function visibleWhen(array $conditions): static
    {
        $this->visibleWhen = $conditions;
        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default    = $default;
        $this->hasDefault = true;
        return $this;
    }

    // --------------------------------------------------------------------------

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label !== '' ? $this->label : $this->key;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVisibleWhen(): array
    {
        return $this->visibleWhen;
    }

    public function getDefault(): mixed
    {
        return $this->hasDefault ? $this->default : $this->defaultValue();
    }

    public function fill(mixed $value): mixed
    {
        return $value;
    }

    public function getCanvasEdit(): ?string
    {
        return null;
    }

    public function validate(): void
    {
    }

    // --------------------------------------------------------------------------

    public function toArray(): array
    {
        return array_merge(
            [
                'key'         => $this->key,
                'type'        => static::getType(),
                'label'       => $this->getLabel(),
                'tip'         => $this->tip,
                'subtitle'    => $this->subtitle,
                'info'        => $this->info,
                'infoClass'   => $this->infoClass,
                'required'    => $this->required,
                'wide'        => $this->wide,
                'visibleWhen' => $this->visibleWhen,
                'default'     => $this->getDefault(),
            ],
            $this->config()
        );
    }

    // --------------------------------------------------------------------------

    /**
     * The default when none is set
     */
    protected function defaultValue(): mixed
    {
        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * The type's own settings, added to toArray()
     *
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return [];
    }
}
