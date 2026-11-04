<?php

/**
 * The WidgetEditor (v2) registry
 *
 * Discovers v2 widgets, applies the override rules, maps slugs (including aliases)
 * to widget classes, and renders stored instances, including nested slots.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

use Nails\Cms\Events;
use Nails\Cms\WidgetEditor\Attribute\DoesNotOverride;
use Nails\Cms\WidgetEditor\Attribute\Overrides;
use Nails\Cms\WidgetEditor\Exception\RegistryException;
use Nails\Cms\WidgetEditor\Interfaces;
use Nails\Cms\WidgetEditor\Widget;
use Nails\Components;
use Nails\Factory;

class Registry
{
    const MODE_RENDER         = 'render';
    const MODE_PREVIEW        = 'preview';
    const DEFAULT_MAX_DEPTH   = 8;
    const DISCOVERY_NAMESPACE = 'Cms\\Widgets';

    /** @var array<string, int> Widget class => component precedence (higher wins) */
    private array $aCandidates;

    /** @var int */
    private int $iMaxDepth;

    /** @var array{leaves: array<string, string>, slugs: array<string, string>, canonical: array<string, string>}|null */
    private ?array $aIndex = null;

    // --------------------------------------------------------------------------

    /**
     * @param array<string, int> $aCandidates Widget class => component precedence (higher wins)
     * @param int                $iMaxDepth   The deepest level of nesting which renders
     */
    public function __construct(array $aCandidates = [], int $iMaxDepth = self::DEFAULT_MAX_DEPTH)
    {
        $this->aCandidates = [];
        foreach ($aCandidates as $sClass => $iPrecedence) {
            $this->aCandidates[ltrim($sClass, '\\')] = (int) $iPrecedence;
        }
        $this->iMaxDepth = $iMaxDepth;
    }

    // --------------------------------------------------------------------------

    /**
     * Finds every v2 widget class in the installed components
     *
     * Components::available() lists the app first, then nails/common, then modules, drivers and skins,
     * so the earlier a component is listed the higher its precedence. The app therefore wins.
     *
     * @return array<string, int> Class => precedence (higher wins)
     */
    public static function discover(): array
    {
        $aComponents = Components::available();
        $iCount      = count($aComponents);
        $aOut        = [];

        foreach (array_values($aComponents) as $iIndex => $oComponent) {
            $oClasses = $oComponent
                ->findClasses(static::DISCOVERY_NAMESPACE)
                ->whichImplement(Interfaces\Widget::class)
                ->whichCanBeInstantiated();

            foreach ($oClasses as $sClass) {
                $aOut[ltrim($sClass, '\\')] = $iCount - $iIndex;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a widget class to its stored slug: `App\Cms\Widgets\Heading` becomes `App.Cms.Widgets.Heading`
     */
    public static function slugFor(string $class): string
    {
        return str_replace('\\', '.', ltrim($class, '\\'));
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a stored slug back to a widget class name
     */
    public static function classFor(string $slug): string
    {
        return '\\' . str_replace('.', '\\', $slug);
    }

    // --------------------------------------------------------------------------

    /**
     * Whether a value is a stored widget instance: an object or array with a string `slug` and a `data` key
     */
    public static function isNode(mixed $value): bool
    {
        if (is_object($value)) {
            return isset($value->slug) && is_string($value->slug) && property_exists($value, 'data');
        } elseif (is_array($value)) {
            return isset($value['slug']) && is_string($value['slug']) && array_key_exists('data', $value);
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Visits every stored instance at any depth, including instances nested in slots.
     *
     * The callback receives the instance and its path, e.g. `0` for the first top-level instance
     * or `0.data.left.2` for the third child of its `left` slot. Child paths extend the parent's
     * path with `.data.`, so the same path format is used by the CDN monitor.
     *
     * @param array|object $nodes
     */
    public static function walk(array|object $nodes, callable $fn): void
    {
        static::visit($nodes, '', $fn);
    }

    // --------------------------------------------------------------------------

    private static function visit(mixed $value, string $path, callable $fn): void
    {
        if (!is_array($value) && !is_object($value)) {
            return;
        }

        if (static::isNode($value)) {
            $fn($value, $path);
            $data = is_object($value) ? $value->data : $value['data'];
            static::visit($data, $path === '' ? 'data' : $path . '.data', $fn);
            return;
        }

        foreach ((array) $value as $key => $child) {
            static::visit($child, $path === '' ? (string) $key : $path . '.' . $key, $fn);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * The maximum nesting depth
     */
    public function getMaxDepth(): int
    {
        return $this->iMaxDepth;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether a stored slug or alias resolves to a widget which renders (not disabled)
     */
    public function has(string $slug): bool
    {
        return $this->resolveCanonical($slug) !== null;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether a stored slug or alias belongs to any v2 widget, whatever its status (disabled included)
     */
    public function knows(string $slug): bool
    {
        return isset($this->index()['slugs'][$slug]);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a widget instance for a stored slug or alias, or null if it does not resolve
     */
    public function resolve(string $slug): ?Interfaces\Widget
    {
        $canonical = $this->resolveCanonical($slug);
        if ($canonical === null) {
            return null;
        }

        return $this->instantiate($canonical, $this->index()['leaves'][$canonical], static::MODE_RENDER, 0, []);
    }

    // --------------------------------------------------------------------------

    /**
     * Every widget which renders, keyed by canonical slug
     *
     * @return array<string, Interfaces\Widget>
     */
    public function all(): array
    {
        $aOut = [];
        foreach ($this->index()['leaves'] as $canonical => $leaf) {
            if ($leaf::getStatus()->renders()) {
                $aOut[$canonical] = $this->instantiate($canonical, $leaf, static::MODE_RENDER, 0, []);
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * The canonical slug of the widget a class belongs to (the root slug, for an overridden class)
     */
    public function canonicalSlugFor(string $class): ?string
    {
        return $this->index()['canonical'][ltrim($class, '\\')] ?? null;
    }

    // --------------------------------------------------------------------------

    /**
     * Every slug which resolves to a canonical slug: the canonical slug first, then its aliases
     *
     * @return string[]
     */
    public function slugsFor(string $canonical): array
    {
        $aSlugs = array_keys(array_filter(
            $this->index()['slugs'],
            fn(string $owner) => $owner === $canonical
        ));

        return array_values(array_unique(array_merge([$canonical], $aSlugs)));
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a stored instance, and its children, in the given mode
     *
     * Unknown and disabled slugs render as an empty string.
     *
     * @param array|object $node    The instance, as {slug, data}
     * @param string       $mode    Registry::MODE_RENDER or Registry::MODE_PREVIEW
     * @param int          $depth   The instance's depth; top-level instances are 0
     * @param array        $context The preview context (D21)
     *
     * @throws RegistryException
     */
    public function renderNode(array|object $node, string $mode = self::MODE_RENDER, int $depth = 0, array $context = []): string
    {
        //  Deeper than the cap: skipped, the same as an unknown slug, so a bad row cannot break the page
        if ($depth > $this->iMaxDepth) {
            return '';
        }

        $aNode     = static::toArray($node);
        $slug      = (string) ($aNode['slug'] ?? '');
        $canonical = $this->resolveCanonical($slug);
        if ($canonical === null) {
            return '';
        }

        $widget = $this->instantiate($canonical, $this->index()['leaves'][$canonical], $mode, $depth, $context);
        $data   = $widget->normalise(static::toArray($aNode['data'] ?? []));

        if ($mode === static::MODE_PREVIEW) {
            return $widget->preview($data, $context);
        }

        $this->loadRenderAssets($widget);

        $output = '';
        $this->fireEvent(Events::WIDGET_RENDER_PRE, $widget, $data, $output);
        $output .= $widget->render($data);
        $this->fireEvent(Events::WIDGET_RENDER_POST, $widget, $data, $output);

        return $output;
    }

    // --------------------------------------------------------------------------

    /**
     * Converts an object or array to a plain array, recursively
     */
    public static function toArray(mixed $value): array
    {
        if (!is_array($value) && !is_object($value)) {
            return [];
        }

        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the canonical slug for a slug or alias, if it renders
     */
    private function resolveCanonical(string $slug): ?string
    {
        $index     = $this->index();
        $canonical = $index['slugs'][$slug] ?? null;

        if ($canonical === null || !$index['leaves'][$canonical]::getStatus()->renders()) {
            return null;
        }

        return $canonical;
    }

    // --------------------------------------------------------------------------

    private function instantiate(string $canonical, string $leaf, string $mode, int $depth, array $context): Interfaces\Widget
    {
        $widget = new $leaf();

        //  Widgets which implement the interface directly do not get a registry; renderSlot() needs Base
        if ($widget instanceof Widget\Base) {
            $widget->attach($this, $canonical, $mode, $depth, $context);
        }

        return $widget;
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a widget's front-end assets, once per asset
     */
    private function loadRenderAssets(Interfaces\Widget $widget): void
    {
        $aAssets = $widget->getAssets()['render'] ?? [];
        if (empty($aAssets)) {
            return;
        }

        $oAsset = Factory::service('Asset');
        foreach ($aAssets as $mAsset) {
            if (is_array($mAsset)) {
                $oAsset->load($mAsset[0], $mAsset[1] ?? null);
            } else {
                $oAsset->load($mAsset);
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fires a v1 render event with the same arguments v1 widgets get
     */
    private function fireEvent(string $event, Interfaces\Widget $widget, array $data, string &$output): void
    {
        /** @var \Nails\Common\Service\Event $oEvent */
        $oEvent = Factory::service('Event');
        $oEvent->trigger(
            $event,
            Events::getEventNamespace(),
            [
                $widget,
                $data,
                &$output,
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Builds the lookup tables: leaves by canonical slug, every slug to its canonical slug,
     * and every class to its canonical slug
     *
     * @return array{leaves: array<string, string>, slugs: array<string, string>, canonical: array<string, string>}
     * @throws RegistryException
     */
    private function index(): array
    {
        if ($this->aIndex !== null) {
            return $this->aIndex;
        }

        //  child => the widget it overrides
        $aOverrides = [];
        foreach ($this->aCandidates as $class => $precedence) {
            $parent = $this->nearestCandidate($class);
            if ($parent === null) {
                if ($this->hasAttribute($class, Overrides::class)) {
                    throw new RegistryException(sprintf('"%s" is marked #[Overrides] but overrides nothing', $class));
                }
                continue;
            }

            if ($this->isOverride($class, $parent)) {
                $aOverrides[$class] = $parent;
            }
        }

        //  parent => the one widget which overrides it
        $aOverriddenBy = [];
        foreach ($aOverrides as $child => $parent) {
            if (isset($aOverriddenBy[$parent])) {
                throw new RegistryException(sprintf(
                    '"%s" and "%s" both override "%s"',
                    $aOverriddenBy[$parent],
                    $child,
                    $parent
                ));
            }
            $aOverriddenBy[$parent] = $child;
        }

        $aLeaves    = [];
        $aSlugs     = [];
        $aCanonical = [];

        foreach (array_keys($this->aCandidates) as $class) {

            if (isset($aOverrides[$class])) {
                //  Reached from its root below
                continue;
            }

            $aChain = [$class];
            while (isset($aOverriddenBy[end($aChain)])) {
                $aChain[] = $aOverriddenBy[end($aChain)];
            }

            $canonical          = static::slugFor($class);
            $aLeaves[$canonical] = end($aChain);

            foreach ($aChain as $member) {
                $this->claim($aSlugs, static::slugFor($member), $canonical, $member);
                $aCanonical[$member] = $canonical;

                foreach ($member::getAliases() as $alias) {
                    $this->claim($aSlugs, (string) $alias, $canonical, $member);
                }
            }
        }

        return $this->aIndex = [
            'leaves'    => $aLeaves,
            'slugs'     => $aSlugs,
            'canonical' => $aCanonical,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @throws RegistryException
     */
    private function claim(array &$aSlugs, string $slug, string $canonical, string $class): void
    {
        if (isset($aSlugs[$slug]) && $aSlugs[$slug] !== $canonical) {
            throw new RegistryException(sprintf(
                'Slug "%s" of "%s" is already used by the widget with slug "%s"',
                $slug,
                $class,
                $aSlugs[$slug]
            ));
        }

        $aSlugs[$slug] = $canonical;
    }

    // --------------------------------------------------------------------------

    private function nearestCandidate(string $class): ?string
    {
        foreach (class_parents($class) as $parent) {
            if (isset($this->aCandidates[$parent])) {
                return $parent;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Applies the D2 rules: an attribute forces the answer; otherwise a widget overrides the
     * widget it extends only when it comes from a higher-precedence component
     *
     * @throws RegistryException
     */
    private function isOverride(string $class, string $parent): bool
    {
        $bForced  = $this->hasAttribute($class, Overrides::class);
        $bDeclined = $this->hasAttribute($class, DoesNotOverride::class);

        if ($bForced && $bDeclined) {
            throw new RegistryException(sprintf(
                '"%s" cannot be both #[Overrides] and #[DoesNotOverride]',
                $class
            ));
        }

        if ($bForced) {
            return true;
        } elseif ($bDeclined) {
            return false;
        }

        return $this->aCandidates[$class] > $this->aCandidates[$parent];
    }

    // --------------------------------------------------------------------------

    private function hasAttribute(string $class, string $attribute): bool
    {
        return !empty((new \ReflectionClass($class))->getAttributes($attribute));
    }
}
