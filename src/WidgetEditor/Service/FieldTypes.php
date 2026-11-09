<?php

/**
 * The WidgetEditor (v2) field types installed in this app
 *
 * Types are discovered from each component's `Cms\Fields` namespace. A type name belongs to one class: a class
 * that extends another type and keeps its name replaces it when it comes from a higher-precedence component (the
 * app first). Two unrelated classes with one name is a developer error.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

use Nails\Cms\WidgetEditor\Interfaces\Field;
use Nails\Components;

class FieldTypes
{
    const DISCOVERY_NAMESPACE = 'Cms\\Fields';

    /** @var array<string, class-string<Field>> Type name => class */
    private array $types = [];

    // --------------------------------------------------------------------------

    /**
     * @param array<class-string<Field>, int> $candidates Class => component precedence (higher wins)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $candidates = [])
    {
        arsort($candidates);

        $precedence = [];
        foreach ($candidates as $class => $rank) {
            $class = ltrim($class, '\\');
            $type  = $class::getType();

            if (!array_key_exists($type, $this->types)) {
                $this->types[$type] = $class;
                $precedence[$type]  = $rank;
                continue;
            }

            $held = $this->types[$type];
            if ($rank < $precedence[$type] && is_subclass_of($held, $class)) {
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                'Field type "%s" is declared by %s and %s. Extend the other class to replace it.',
                $type,
                $held,
                $class
            ));
        }

        ksort($this->types);
    }

    // --------------------------------------------------------------------------

    /**
     * Finds every field type class in the installed components
     *
     * Components::available() lists the app first, so the earlier a component is listed the higher its precedence.
     *
     * @return array<class-string<Field>, int> Class => precedence (higher wins)
     */
    public static function discover(): array
    {
        $components = Components::available();
        $count      = count($components);
        $out        = [];

        foreach (array_values($components) as $index => $component) {
            $classes = $component
                ->findClasses(static::DISCOVERY_NAMESPACE)
                ->whichImplement(Field::class)
                ->whichCanBeInstantiated();

            foreach ($classes as $class) {
                $out[ltrim($class, '\\')] = $count - $index;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * @return array<string, class-string<Field>> Type name => class
     */
    public function all(): array
    {
        return $this->types;
    }

    // --------------------------------------------------------------------------

    /**
     * The editor assets of every type, without duplicates, in type-name order
     *
     * @return array<int, string|array{0: string, 1?: string}>
     */
    public function assets(): array
    {
        $out  = [];
        $seen = [];

        foreach ($this->types as $class) {
            foreach ($class::getAssets() as $asset) {
                $id = json_encode($asset);
                if (!array_key_exists($id, $seen)) {
                    $seen[$id] = true;
                    $out[]     = $asset;
                }
            }
        }

        return $out;
    }
}
