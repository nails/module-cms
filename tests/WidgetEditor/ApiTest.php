<?php

namespace Tests\WidgetEditor\Api;

use Nails\Cms\WidgetEditor\Service\Catalogue;
use Nails\Cms\WidgetEditor\Service\Favourite;
use Nails\Cms\WidgetEditor\Service\Inspector;
use Nails\Cms\WidgetEditor\Service\Preview;
use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Cms\WidgetEditor\Status;
use Nails\Cms\WidgetEditor\Widget\Base;
use PHPUnit\Framework\TestCase;

class HeadingWidget extends Base
{
    const ALIASES = ['legacy-heading'];

    protected string $label = 'Heading';
    protected array $groups = ['Text'];
    protected array $screenshots = ['https://example.test/h.png', 7];
    protected array $defaults = ['sText' => ''];

    public function getFields(): ?array
    {
        return [['key' => 'sText', 'type' => 'text', 'label' => 'Text']];
    }

    public function getEditorForm(array $data): ?string
    {
        return 'SHOULD NOT';
    }

    public function render(array $data): string
    {
        return '<h1>' . $data['sText'] . '</h1>';
    }
}

class NoteWidget extends Base
{
    const ALIASES = [];

    protected string $label = 'Note';
    protected array $assets = ['render' => [], 'editor' => ['note.js']];

    public function getEditorForm(array $data): ?string
    {
        return '<input value="' . ($data['sText'] ?? '') . '">';
    }

    public function render(array $data): string
    {
        return 'note';
    }
}

class HiddenWidget extends Base
{
    const STATUS  = Status::HIDDEN;
    const ALIASES = [];

    protected string $label = 'Hidden';

    public function render(array $data): string
    {
        return 'hidden';
    }
}

class DisabledWidget extends Base
{
    const STATUS  = Status::DISABLED;
    const ALIASES = [];

    public function render(array $data): string
    {
        return 'disabled';
    }
}

class BoomWidget extends Base
{
    const ALIASES = [];

    public function preview(array $data, array $context = []): string
    {
        throw new \RuntimeException('preview failed');
    }

    public function render(array $data): string
    {
        return '';
    }
}

class ContextWidget extends Base
{
    const ALIASES = [];

    public function preview(array $data, array $context = []): string
    {
        return (string) ($context['locale'] ?? '');
    }

    public function render(array $data): string
    {
        return '';
    }
}

class ApiTest extends TestCase
{
    private const HEADING = 'Tests.WidgetEditor.Api.HeadingWidget';
    private const NOTE    = 'Tests.WidgetEditor.Api.NoteWidget';

    private function registry(): Registry
    {
        return new Registry([
            HeadingWidget::class  => 1,
            NoteWidget::class     => 1,
            HiddenWidget::class   => 1,
            DisabledWidget::class => 1,
            BoomWidget::class     => 1,
            ContextWidget::class  => 1,
        ]);
    }

    private function catalogue(array $rules = [], array $favourites = ['legacy-heading']): array
    {
        return (new Catalogue(fn(int $id, int $width, int $height) => "crop-$id-$width"))->build(
            $this->registry(),
            $rules,
            $favourites,
            fn(string $label): ?int => $label === 'Text' ? 0 : null,
            8,
            200,
            ['site.css']
        );
    }

    public function testCatalogueIncludesHiddenAndOmitsDisabled(): void
    {
        $payload = $this->catalogue();

        self::assertArrayHasKey($this::HEADING, $payload['widgets']);
        self::assertArrayHasKey('Tests.WidgetEditor.Api.HiddenWidget', $payload['widgets']);
        self::assertArrayNotHasKey('Tests.WidgetEditor.Api.DisabledWidget', $payload['widgets']);
        self::assertSame('hidden', $payload['widgets']['Tests.WidgetEditor.Api.HiddenWidget']['status']);
        self::assertSame(['legacy-heading'], $payload['widgets'][$this::HEADING]['aliases']);
        self::assertSame('fields', $payload['widgets'][$this::HEADING]['inspector']['type']);
        self::assertSame('html', $payload['widgets'][$this::NOTE]['inspector']['type']);
    }

    public function testCatalogueScreenshotsAndGroups(): void
    {
        $payload = $this->catalogue();
        $heading = $payload['widgets'][$this::HEADING];

        self::assertSame(
            [
                ['list' => 'https://example.test/h.png', 'detail' => 'https://example.test/h.png'],
                ['list' => 'crop-7-120', 'detail' => 'crop-7-400'],
            ],
            $heading['screenshots']
        );

        self::assertSame('Text', $payload['groups'][0]['label']);
        self::assertSame(0, $payload['groups'][0]['order']);
        self::assertContains($this::NOTE, $this->groupSlugs($payload, Catalogue::GENERIC_GROUP));
        self::assertSame(['site.css'], $payload['canvas_assets']);
        self::assertSame(8, $payload['limits']['max_depth']);
        self::assertSame(200, $payload['limits']['max_instances_per_area']);
    }

    public function testRulesFilterByAliasThenGroupThenDisallow(): void
    {
        $byAlias = $this->catalogue(['allowed' => ['legacy-heading']]);
        self::assertSame([$this::HEADING], array_keys($byAlias['widgets']));

        $byGroup = $this->catalogue(['allowed' => ['group:Text']]);
        self::assertArrayHasKey($this::HEADING, $byGroup['widgets']);
        self::assertArrayNotHasKey($this::NOTE, $byGroup['widgets']);

        $generic = $this->catalogue(['allowed' => ['group:' . Catalogue::GENERIC_GROUP]]);
        self::assertArrayHasKey($this::NOTE, $generic['widgets']);
        self::assertArrayNotHasKey($this::HEADING, $generic['widgets']);

        $removed = $this->catalogue([
            'allowed'    => ['group:Text', 'group:' . Catalogue::GENERIC_GROUP],
            'disallowed' => ['legacy-heading'],
        ]);
        self::assertArrayNotHasKey($this::HEADING, $removed['widgets']);
        self::assertArrayHasKey($this::NOTE, $removed['widgets']);

        $none = $this->catalogue(['allowed' => []]);
        self::assertSame([], $none['widgets']);
    }

    public function testRevisionChangesWhenThePayloadChanges(): void
    {
        $first  = $this->catalogue([], ['legacy-heading']);
        $second = $this->catalogue([], ['legacy-heading']);
        $third  = $this->catalogue([], []);

        self::assertSame($first['revision'], $second['revision']);
        self::assertNotSame($first['revision'], $third['revision']);
        self::assertSame(64, strlen($first['revision']));
    }

    public function testPreviewIsolatesFailuresAndPassesContext(): void
    {
        $preview = new Preview();
        $out     = $preview->render($this->registry(), [
            ['slug' => self::HEADING, 'data' => ['sText' => 'A']],
            ['slug' => 'Tests.WidgetEditor.Api.BoomWidget', 'data' => []],
            ['slug' => 'missing', 'data' => []],
            ['slug' => 'Tests.WidgetEditor.Api.ContextWidget', 'data' => []],
            'not-an-item',
        ], ['locale' => 'en']);

        self::assertSame('<h1>A</h1>', $out[0]['html']);
        self::assertNull($out[0]['error']);
        self::assertSame('', $out[1]['html']);
        self::assertSame('preview failed', $out[1]['error']);
        self::assertSame('', $out[2]['html']);
        self::assertNull($out[2]['error']);
        self::assertSame('en', $out[3]['html']);
        self::assertSame('Each item needs a slug', $out[4]['error']);
    }

    public function testInspectorSkipsFieldsSchemaAndRendersHtmlForm(): void
    {
        $out = (new Inspector())->render($this->registry(), [
            ['slug' => self::HEADING, 'data' => []],
            ['slug' => self::NOTE, 'data' => ['sText' => 'Hi']],
            ['slug' => 'Tests.WidgetEditor.Api.DisabledWidget', 'data' => []],
        ]);

        self::assertSame('fields', $out[0]['type']);
        self::assertNull($out[0]['html']);
        self::assertSame('html', $out[1]['type']);
        self::assertSame('<input value="Hi">', $out[1]['html']);
        self::assertSame(['note.js'], $out[1]['assets']);
        self::assertNull($out[2]['type']);
        self::assertNotNull($out[2]['error']);
    }

    public function testFavouriteListNormalisation(): void
    {
        self::assertSame(['a', 'b'], Favourite::normaliseList(['a', 'a', 'b']));

        $this->expectException(\InvalidArgumentException::class);
        Favourite::normaliseList(['ok' => 'a']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string[]
     */
    private function groupSlugs(array $payload, string $label): array
    {
        foreach ($payload['groups'] as $group) {
            if ($group['label'] === $label) {
                return $group['slugs'];
            }
        }

        return [];
    }

    // --------------------------------------------------------------------------

    public function testFavouriteSlugMustFitTheColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Favourite::checkSlug(str_repeat('a', 256));
    }
}
