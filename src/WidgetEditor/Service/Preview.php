<?php

/**
 * Renders a batch of widget previews for the WidgetEditor canvas
 *
 * One failure does not fail the batch. Nothing here loads assets: the canvas has its own stylesheets.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

class Preview
{
    public const MAX_ITEMS = 1000;

    /**
     * @param array<int, mixed>    $items   Each item is ['slug' => string, 'data' => array]
     * @param array<string, mixed> $context The preview context (D21). Widgets read what they need.
     *
     * @return array<int, array{slug: string, html: string, error: ?string}>
     */
    public function render(Registry $registry, array $items, array $context = []): array
    {
        $out = [];

        foreach ($items as $item) {
            $slug = is_array($item) ? (string) ($item['slug'] ?? '') : '';
            $data = is_array($item) && array_key_exists('data', $item) ? $item['data'] : [];

            try {
                if ($slug === '') {
                    throw new \InvalidArgumentException('Each item needs a slug');
                }

                $out[] = [
                    'slug'  => $slug,
                    'html'  => $registry->renderNode(
                        ['slug' => $slug, 'data' => $data],
                        Registry::MODE_PREVIEW,
                        0,
                        $context
                    ),
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $out[] = [
                    'slug'  => $slug,
                    'html'  => '',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $out;
    }
}
