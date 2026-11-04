<?php

/**
 * The WidgetEditor (v2) widget contract
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Interface
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Interfaces;

use Nails\Cms\WidgetEditor\Status;

interface Widget
{
    // Identity and catalogue

    /**
     * The widget's stored slug: the FQCN with "." for "\", or the root slug of an overridden widget
     */
    public function getSlug(): string;

    /**
     * Previous slugs this widget answers to
     *
     * @return string[]
     */
    public static function getAliases(): array;

    public function getLabel(): string;

    /** One line, for the organiser list */
    public function getSummary(): string;

    /** Longer text, for the organiser detail panel */
    public function getDescription(): string;

    /** Font Awesome class, or an SVG/URL */
    public function getIcon(): string;

    /** @return string[] */
    public function getGroups(): array;

    /** @return string[] */
    public function getKeywords(): array;

    /**
     * URL strings are used as given; integers are CDN object ids, cropped by the catalogue
     *
     * @return array<int, string|int>
     */
    public function getScreenshots(): array;

    public static function getStatus(): Status;

    public static function getAlternative(): ?string;

    // Data

    /** @return array<string, mixed> */
    public function getDefaults(): array;

    /**
     * Fills defaults and coerces data for render, preview and inspector. Pure; never saved.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function normalise(array $data): array;

    // Output

    /** @param array<string, mixed> $data */
    public function render(array $data): string;

    /**
     * The editor canvas markup. Defaults to render().
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public function preview(array $data, array $context = []): string;

    // Editing

    /**
     * The fields schema, or null when the widget uses an HTML form
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getFields(): ?array;

    /**
     * The HTML form inspector, or null when the widget uses a fields schema
     *
     * @param array<string, mixed> $data
     */
    public function getEditorForm(array $data): ?string;

    // Nesting

    /**
     * Named child lists: name => ['label' => string, 'max' => ?int]. Empty for leaf widgets.
     *
     * @return array<string, array{label: string, max: ?int}>
     */
    public function getSlots(): array;

    // Assets

    /**
     * Assets by use: 'render' (front end) and 'editor' (inspector). Each is a list of URLs or [url, type] pairs.
     *
     * @return array{render: array, editor: array}
     */
    public function getAssets(): array;
}
