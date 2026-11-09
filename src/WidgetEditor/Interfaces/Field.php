<?php

/**
 * The WidgetEditor (v2) field type contract
 *
 * A field type is a class. A widget's getFields() returns instances of them, each bound to one data key. Types are
 * discovered from each component's `Cms\Fields` namespace, so a module or the app can add one. The inspector
 * control for a type is registered in JavaScript under the same type name; see getAssets().
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Interface
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Interfaces;

interface Field
{
    /**
     * The type name the editor registers the control under, e.g. `text` or `cdn_object`
     */
    public static function getType(): string;

    /**
     * Editor assets which register the type's control. Each is a URL, or a [file, component slug] pair as the
     * Asset service takes. Empty when the control ships in the editor bundle.
     *
     * @return array<int, string|array{0: string, 1?: string}>
     */
    public static function getAssets(): array;

    /**
     * The data key the field is stored under
     */
    public function getKey(): string;

    /**
     * The value used when the data has none
     */
    public function getDefault(): mixed;

    /**
     * Fills what a stored value is missing, e.g. the defaults of a repeater row. Pure: the result is never saved.
     */
    public function fill(mixed $value): mixed;

    /**
     * How the canvas edits the field in place: `text`, `rich`, `rich-basic`, or null when it is edited only in
     * the inspector
     */
    public function getCanvasEdit(): ?string;

    /**
     * Throws when the field is configured in a way the editor cannot use
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void;

    /**
     * The field as the inspector reads it: type, key, label, tip, required, wide, visibleWhen, default, and the
     * type's own settings
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
