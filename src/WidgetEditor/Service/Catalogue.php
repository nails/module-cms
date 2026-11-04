<?php

/**
 * Builds the WidgetEditor catalogue: the widgets an editor may place, and how to present them
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

use Nails\Cms\WidgetEditor\Interfaces\Widget;

class Catalogue
{
    /** Widgets with no groups of their own are listed under this label, as in the v1 editor */
    public const GENERIC_GROUP = 'Generic';

    public const SCREENSHOT_LIST   = 120;
    public const SCREENSHOT_DETAIL = 400;

    /** @var callable(int, int, int): ?string */
    private $crop;

    /**
     * @param callable(int, int, int): ?string|null $crop Resolves a CDN object id and a size to a URL
     */
    public function __construct(?callable $crop = null)
    {
        $this->crop = $crop ?? static function (int $id, int $width, int $height): ?string {
            if (!function_exists('cdnCrop')) {
                return null;
            }

            $url = cdnCrop($id, $width, $height);
            return $url === null ? null : (string) $url;
        };
    }

    // --------------------------------------------------------------------------

    /**
     * The catalogue payload
     *
     * Disabled widgets are absent. Hidden and deprecated widgets are included, with their status.
     * `$rules` is `['allowed' => string[], 'disallowed' => string[]]`. Either key may be omitted.
     * An entry is a slug (aliases included) or `group:<label>`. `allowed` is applied first.
     *
     * `revision` is a hash of the rest of the payload. The API router sends `Cache-Control: no-store`,
     * so the editor compares this instead of relying on an HTTP cache.
     *
     * @param array{allowed?: string[], disallowed?: string[]} $rules
     * @param string[]                                         $favourites
     * @param callable(string): ?int                           $groupOrder
     * @param string[]                                         $canvasAssets
     *
     * @return array<string, mixed>
     */
    public function build(
        Registry $registry,
        array $rules,
        array $favourites,
        callable $groupOrder,
        int $maxDepth,
        int $maxInstances,
        array $canvasAssets
    ): array {
        $widgets = [];
        $grouped = [];

        foreach ($registry->all() as $slug => $widget) {
            if (!$this->permits($registry, $widget, $rules)) {
                continue;
            }

            $widgets[$slug] = $this->describe($registry, $widget);

            foreach ($this->groupLabels($widget) as $label) {
                $grouped[$label][] = $slug;
            }
        }

        $groups = [];
        foreach ($grouped as $label => $slugs) {
            usort($slugs, fn(string $a, string $b) => strcmp($widgets[$a]['label'], $widgets[$b]['label']));
            $groups[] = [
                'label' => $label,
                'order' => $groupOrder($label),
                'slugs' => array_values($slugs),
            ];
        }

        usort($groups, [$this, 'compareGroups']);

        $payload = [
            'widgets'       => $widgets,
            'groups'        => $groups,
            'favourites'    => array_values($favourites),
            'limits'        => [
                'max_depth'               => $maxDepth,
                'max_instances_per_area'  => $maxInstances,
            ],
            'canvas_assets' => array_values($canvasAssets),
        ];

        $payload['revision'] = hash(
            'sha256',
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $payload;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the area rules let this widget be offered
     *
     * @param array{allowed?: string[], disallowed?: string[]} $rules
     */
    public function permits(Registry $registry, Widget $widget, array $rules): bool
    {
        $allowed = $rules['allowed'] ?? null;
        if (is_array($allowed) && !$this->matches($registry, $widget, $allowed)) {
            return false;
        }

        $disallowed = $rules['disallowed'] ?? null;
        if (is_array($disallowed) && $this->matches($registry, $widget, $disallowed)) {
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * @param string[] $entries
     */
    private function matches(Registry $registry, Widget $widget, array $entries): bool
    {
        $identities = $registry->slugsFor($widget->getSlug());
        $groups     = $this->groupLabels($widget);

        foreach ($entries as $entry) {
            if (!is_string($entry) || $entry === '') {
                continue;
            }

            if (str_starts_with($entry, 'group:')) {
                if (in_array(substr($entry, 6), $groups, true)) {
                    return true;
                }
                continue;
            }

            if (in_array($entry, $identities, true)) {
                return true;
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * @return string[]
     */
    private function groupLabels(Widget $widget): array
    {
        $labels = $widget->getGroups();
        return $labels === [] ? [self::GENERIC_GROUP] : $labels;
    }

    // --------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function describe(Registry $registry, Widget $widget): array
    {
        $slug   = $widget->getSlug();
        $fields = $widget->getFields();
        $assets = $widget->getAssets();

        return [
            'slug'        => $slug,
            'label'       => $widget->getLabel(),
            'summary'     => $widget->getSummary(),
            'description' => $widget->getDescription(),
            'icon'        => $widget->getIcon(),
            'groups'      => $widget->getGroups(),
            'keywords'    => $widget->getKeywords(),
            'status'      => $widget::getStatus()->value,
            'alternative' => $widget::getAlternative(),
            'aliases'     => array_values(array_diff($registry->slugsFor($slug), [$slug])),
            'screenshots' => $this->screenshots($widget),
            'slots'       => $widget->getSlots(),
            'inspector'   => is_array($fields)
                ? ['type' => 'fields', 'fields' => $fields]
                : ['type' => 'html'],
            'assets'      => [
                'render' => array_values($assets['render'] ?? []),
                'editor' => array_values($assets['editor'] ?? []),
            ],
            'defaults'    => $widget->getDefaults(),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * A URL string is used as given. An integer is a CDN object id, cropped to the two organiser sizes.
     *
     * @return array<int, array{list: string, detail: string}>
     */
    private function screenshots(Widget $widget): array
    {
        $out = [];

        foreach ($widget->getScreenshots() as $shot) {
            if (is_string($shot) && $shot !== '') {
                $out[] = ['list' => $shot, 'detail' => $shot];
                continue;
            }

            if (!is_int($shot) || $shot < 1) {
                continue;
            }

            $list   = ($this->crop)($shot, self::SCREENSHOT_LIST, self::SCREENSHOT_LIST);
            $detail = ($this->crop)($shot, self::SCREENSHOT_DETAIL, self::SCREENSHOT_DETAIL);

            if (is_string($list) && $list !== '' && is_string($detail) && $detail !== '') {
                $out[] = ['list' => $list, 'detail' => $detail];
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Null orders sort last. Equal orders sort by label.
     *
     * @param array{label: string, order: ?int} $a
     * @param array{label: string, order: ?int} $b
     */
    private function compareGroups(array $a, array $b): int
    {
        if ($a['order'] === null && $b['order'] === null) {
            return strcmp($a['label'], $b['label']);
        } elseif ($a['order'] === null) {
            return 1;
        } elseif ($b['order'] === null) {
            return -1;
        } elseif ($a['order'] !== $b['order']) {
            return $a['order'] <=> $b['order'];
        }

        return strcmp($a['label'], $b['label']);
    }
}
