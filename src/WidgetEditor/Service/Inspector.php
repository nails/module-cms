<?php

/**
 * Renders HTML-form inspectors for a batch of widgets
 *
 * A fields-schema widget needs no request: the catalogue already carries its fields.
 * When one is sent here, the response says so and returns no HTML.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

class Inspector
{
    public const MAX_ITEMS = 1000;

    /**
     * @param array<int, mixed> $items Each item is ['slug' => string, 'data' => array]
     *
     * @return array<int, array{slug: string, type: ?string, html: ?string, assets: array, error: ?string}>
     */
    public function render(Registry $registry, array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            $slug = is_array($item) ? (string) ($item['slug'] ?? '') : '';

            try {
                if ($slug === '') {
                    throw new \InvalidArgumentException('Each item needs a slug');
                }

                $widget = $registry->resolve($slug);
                if ($widget === null) {
                    throw new \InvalidArgumentException('"' . $slug . '" is not an editable widget');
                }

                $data = $widget->normalise(Registry::toArray(
                    is_array($item) && array_key_exists('data', $item) ? $item['data'] : []
                ));

                if (is_array($widget->getFields())) {
                    $out[] = [
                        'slug'   => $slug,
                        'type'   => 'fields',
                        'html'   => null,
                        'assets' => [],
                        'error'  => null,
                    ];
                    continue;
                }

                $out[] = [
                    'slug'   => $slug,
                    'type'   => 'html',
                    'html'   => $widget->getEditorForm($data) ?? '',
                    'assets' => array_values($widget->getAssets()['editor'] ?? []),
                    'error'  => null,
                ];
            } catch (\Throwable $e) {
                $out[] = [
                    'slug'   => $slug,
                    'type'   => null,
                    'html'   => null,
                    'assets' => [],
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $out;
    }
}
