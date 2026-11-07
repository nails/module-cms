<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Cms\Widgets\Accordion;
use Nails\Cms\Cms\Widgets\Area;
use Nails\Cms\Cms\Widgets\Blockquote;
use Nails\Cms\Cms\Widgets\Html;
use Nails\Cms\Cms\Widgets\KitchenSink;
use Nails\Cms\Cms\Widgets\Richtext;
use Nails\Cms\Cms\Widgets\Table;
use Nails\Cms\Cms\Widgets\Tabs;
use Nails\Cms\WidgetEditor\Service\Fields;
use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Cms\WidgetEditor\Traits\Legacy;
use PHPUnit\Framework\TestCase;

/**
 * The shipped widgets ported to WidgetEditor (v2). They are native: each answers to its v1 slug, declares a valid
 * fields schema, upgrades data stored by its v1 widget, and renders what its v1 view rendered.
 */
class ShippedWidgetsTest extends TestCase
{
    public function testRichtextRendersNothingWhenEmpty(): void
    {
        $this->assertSame('', (new Richtext())->render(['body' => '']));
    }

    public function testRichtextPreviewIsEditableWhenEmpty(): void
    {
        $registry = new Registry([Richtext::class => 1]);
        $html = $registry->renderNode(
            ['slug' => Registry::slugFor(Richtext::class), 'data' => ['body' => '']],
            Registry::MODE_PREVIEW
        );

        $this->assertStringContainsString('data-cms-edit="rich"', $html);
        $this->assertStringContainsString('data-cms-field="body"', $html);
        $this->assertStringContainsString('data-cms-empty="1"', $html);
        $this->assertStringContainsString('Write something…', $html);
    }

    public function testRichtextRendersBodyAsStored(): void
    {
        $sHtml = (new Richtext())->render(['body' => '<p>Hi <strong>there</strong></p>']);

        $this->assertStringContainsString('class="cms-widget cms-widget-richtext"', $sHtml);
        $this->assertStringContainsString('<p>Hi <strong>there</strong></p>', $sHtml);
    }

    public function testRichtextAliasIsTheV1Slug(): void
    {
        $this->assertSame(['richtext'], Richtext::ALIASES);
    }

    public function testHtmlRendersBodyUnescaped(): void
    {
        $sHtml = (new Html())->render(['body' => '<b>Raw</b>']);

        $this->assertSame('<div class="cms-widget cms-widget-html"><b>Raw</b></div>', $sHtml);
        $this->assertSame('', (new Html())->render(['body' => '']));
        $this->assertSame(['html'], Html::ALIASES);
    }

    public function testBlockquoteRendersNothingWithoutAQuote(): void
    {
        $this->assertSame('', (new Blockquote())->render(['quote' => '', 'cite_text' => 'Someone']));
    }

    public function testBlockquotePreviewShowsEmptyFieldsAsPlaceholders(): void
    {
        $registry = new Registry([Blockquote::class => 1]);
        $slug     = Registry::slugFor(Blockquote::class);

        $empty = $registry->renderNode(
            ['slug' => $slug, 'data' => ['quote' => '', 'cite_text' => '', 'cite_url' => 'https://example.com']],
            Registry::MODE_PREVIEW
        );

        $this->assertStringContainsString('class="cms-widget cms-widget-blockquote"', $empty);
        $this->assertStringContainsString('data-cms-field="quote" data-cms-edit="rich-basic"', $empty);
        $this->assertStringContainsString('data-cms-empty="1"', $empty);
        $this->assertStringContainsString('Write something…', $empty);
        $this->assertStringContainsString('data-cms-field="cite_text" data-cms-edit="text"', $empty);
        $this->assertStringContainsString('data-cms-placeholder="Add a citation"', $empty);
        $this->assertStringContainsString('Add a citation', $empty);
        // With a URL the citation field is the link itself, so the placeholder is not wrapped in one
        $this->assertStringContainsString('<a data-cms-placeholder="Add a citation" data-cms-empty="1" href="https://example.com" data-cms-field="cite_text"', $empty);

        $plain = $registry->renderNode(
            ['slug' => $slug, 'data' => ['quote' => '<p>Quote</p>', 'cite_text' => '', 'cite_url' => '']],
            Registry::MODE_PREVIEW
        );
        $this->assertStringContainsString('<span data-cms-placeholder="Add a citation" data-cms-empty="1" data-cms-field="cite_text"', $plain);
        $this->assertStringNotContainsString('<a ', $plain);

        $filled = $registry->renderNode(
            ['slug' => $slug, 'data' => [
                'quote'     => '<p>Quote</p>',
                'cite_text' => 'Source & co',
                'cite_url'  => 'https://example.com/a?b=1',
            ]],
            Registry::MODE_PREVIEW
        );

        $this->assertStringContainsString('<p>Quote</p>', $filled);
        $this->assertStringContainsString('<a data-cms-placeholder="Add a citation" href="https://example.com/a?b=1" data-cms-field="cite_text"', $filled);
        $this->assertStringContainsString('>Source &amp; co</a>', $filled);
        $this->assertStringNotContainsString('data-cms-empty', $filled);
        $this->assertStringNotContainsString('Write something…', $filled);
    }

    public function testBlockquoteWithoutCitation(): void
    {
        $sHtml = (new Blockquote())->render(['quote' => '<p>Quote</p>', 'cite_text' => '', 'cite_url' => '']);

        $this->assertSame(
            '<div class="cms-widget cms-widget-blockquote"><blockquote><p>Quote</p></blockquote></div>',
            $sHtml
        );
    }

    public function testBlockquoteLinksCitationWhenThereIsAUrl(): void
    {
        $sHtml = (new Blockquote())->render([
            'quote'     => '<p>Quote</p>',
            'cite_text' => 'Source & co',
            'cite_url'  => 'https://example.com/a?b=1',
        ]);

        $this->assertStringContainsString(
            '<footer><a href="https://example.com/a?b=1">Source &amp; co</a></footer>',
            $sHtml
        );
    }

    public function testBlockquoteCitationWithoutUrlIsPlain(): void
    {
        $sHtml = (new Blockquote())->render([
            'quote'     => '<p>Quote</p>',
            'cite_text' => 'Someone',
            'cite_url'  => '',
        ]);

        $this->assertStringContainsString('<footer>Someone</footer>', $sHtml);
        $this->assertStringNotContainsString('<a ', $sHtml);
    }

    public function testNativePortsDeclareValidFieldSchemas(): void
    {
        foreach ([new Richtext(), new Html(), new Blockquote(), new KitchenSink()] as $oWidget) {
            $aFields = Fields::describe($oWidget->getFields());
            $this->assertNotEmpty($aFields);
        }

        $aQuote = Fields::describe((new Blockquote())->getFields())[0];
        $this->assertSame('basic', $aQuote['editor']);
    }

    public function testKitchenSinkUsesEveryFieldType(): void
    {
        $aTypes = [];
        foreach (Fields::describe((new KitchenSink())->getFields()) as $aField) {
            $aTypes[] = $aField['type'];
            foreach ($aField['fields'] ?? [] as $aChild) {
                $aTypes[] = $aChild['type'];
            }
        }

        $aExpected = ['cdn_object', 'number', 'repeater', 'richtext', 'select', 'table', 'text', 'textarea', 'toggle'];
        $this->assertSame([], array_values(array_diff($aExpected, $aTypes)));
    }

    public function testDefaultsIncludeEachFieldsDefault(): void
    {
        $defaults = (new KitchenSink())->getDefaults();

        $this->assertSame('A subtitle', $defaults['subtitle']);
        $this->assertSame('light', $defaults['tone']);
        $this->assertSame('medium', $defaults['size']);
        $this->assertFalse($defaults['showLink']);
        $this->assertSame([], $defaults['items']);
    }

    public function testKitchenSinkEditsTextAndBothRichModesOnTheCanvas(): void
    {
        $registry = new Registry([KitchenSink::class => 1]);
        $html     = $registry->renderNode(
            ['slug' => Registry::slugFor(KitchenSink::class), 'data' => ['items' => [['label' => 'One']]]],
            Registry::MODE_PREVIEW
        );

        $this->assertStringContainsString('data-cms-field="title" data-cms-edit="text"', $html);
        $this->assertStringContainsString('data-cms-field="body" data-cms-edit="rich"', $html);
        $this->assertStringContainsString('data-cms-field="quote" data-cms-edit="rich-basic"', $html);
        $this->assertStringContainsString('<dt>label</dt><dd>One</dd>', $html);
    }

    public function testShippedWidgetsAreNative(): void
    {
        foreach (glob(dirname(__DIR__, 2) . '/src/Cms/Widgets/*.php') as $file) {
            $class = 'Nails\\Cms\\Cms\\Widgets\\' . basename($file, '.php');
            $this->assertNotContains(Legacy::class, (new \ReflectionClass($class))->getTraitNames(), $class);
        }
    }

    public function testPortsAnswerToTheirV1Slugs(): void
    {
        $this->assertSame(['table'], Table::ALIASES);
        $this->assertSame(['tabs'], Tabs::ALIASES);
        $this->assertSame(['accordion'], Accordion::ALIASES);
        $this->assertSame(['Area'], Area::ALIASES);
    }

    public function testPortsDeclareValidFieldSchemas(): void
    {
        foreach ([new Table(), new Tabs(), new Accordion(), $this->area(['3' => 'Footer'])] as $widget) {
            $this->assertNotEmpty(Fields::describe($widget->getFields()), get_class($widget));
        }
    }

    // --------------------------------------------------------------------------

    public function testAccordionUpgradesTheV1Lists(): void
    {
        $data = (new Accordion())->upgrade([
            'title' => ['One', 'Two'],
            'body'  => ['<p>1</p>'],
        ]);

        $this->assertSame([
            'panels' => [
                ['title' => 'One', 'body' => '<p>1</p>', 'open' => true],
                ['title' => 'Two', 'body' => '', 'open' => false],
            ],
        ], $data);
    }

    public function testAccordionKeepsAStoredOpenStateAndReadsV1Collapsed(): void
    {
        $widget = new Accordion();

        $this->assertSame(
            ['panels' => [['title' => 'A', 'body' => '', 'open' => false]]],
            $widget->upgrade(['panels' => [['title' => 'A', 'body' => '', 'open' => false]]])
        );
        $this->assertSame(
            [
                'panels' => [
                    ['title' => 'A', 'body' => '', 'collapsed' => true, 'open' => false],
                    ['title' => 'B', 'body' => '', 'collapsed' => false, 'open' => true],
                ],
            ],
            $widget->upgrade([
                'panels' => [
                    ['title' => 'A', 'body' => '', 'collapsed' => true],
                    ['title' => 'B', 'body' => '', 'collapsed' => false],
                ],
            ])
        );
    }

    public function testUpgradeLeavesTheCurrentShapeAlone(): void
    {
        $panels = ['panels' => [['title' => 'One', 'body' => '', 'open' => false]], 'title' => ['Stale']];

        $this->assertSame($panels, (new Accordion())->upgrade($panels));
        $this->assertSame(['tabs' => []], (new Tabs())->upgrade(['tabs' => []]));
        $this->assertSame(['rows' => [['a']], 'attributes' => ''], (new Table())->upgrade(['rows' => [['a']], 'attributes' => '']));
        $this->assertSame(['areaId' => '4'], $this->area([])->upgrade(['areaId' => '4']));
    }

    public function testAccordionRendersPanelsWithTheFirstOpen(): void
    {
        $widget = new Accordion();
        $html   = $widget->render($widget->normalise([
            'title' => ['One & two', 'Three'],
            'body'  => ['<p>First</p>', '<p>Second</p>'],
        ]));

        $this->assertStringContainsString('class="cms-widget cms-widget-accordion"', $html);
        $this->assertStringContainsString('One &amp; two', $html);
        $this->assertStringContainsString('<div class="panel-body"><p>Second</p></div>', $html);
        $this->assertSame(1, substr_count($html, 'collapse in'));
        $this->assertStringNotContainsString('data-cms-', $html);
        $this->assertSame('', $widget->render(['panels' => []]));

        $closed = $widget->render($widget->normalise([
            'panels' => [
                ['title' => 'A', 'body' => '<p>a</p>', 'open' => false],
                ['title' => 'B', 'body' => '<p>b</p>', 'open' => true],
            ],
        ]));
        $this->assertSame(1, substr_count($closed, 'collapse in'));
        $this->assertStringContainsString('<div class="panel-body"><p>b</p></div>', $closed);
    }

    public function testAccordionPreviewAsksForAPanelAndEditsItsFields(): void
    {
        $registry = new Registry([Accordion::class => 1]);
        $slug     = Registry::slugFor(Accordion::class);

        $empty = $registry->renderNode(
            ['slug' => $slug, 'data' => ['panels' => []]],
            Registry::MODE_PREVIEW
        );
        $this->assertStringContainsString('Add a panel in the inspector.', $empty);
        $this->assertStringNotContainsString('data-cms-edit', $empty);

        $html = $registry->renderNode(
            ['slug' => $slug, 'data' => ['panels' => [['title' => '', 'body' => '', 'open' => true]]]],
            Registry::MODE_PREVIEW
        );
        $this->assertStringContainsString('data-cms-field="panels/0/title" data-cms-edit="text"', $html);
        $this->assertStringContainsString('data-cms-field="panels/0/body" data-cms-edit="rich"', $html);
        $this->assertStringContainsString('data-cms-action="toggle"', $html);
        $this->assertStringContainsString('data-cms-field="panels/0/open"', $html);
        $this->assertStringContainsString('Add a title', $html);
        $this->assertStringContainsString('Write something…', $html);
        $this->assertStringContainsString('collapse in', $html);
    }

    public function testTabsRenderWithTheFirstActive(): void
    {
        $widget = new Tabs();
        $html   = $widget->render($widget->normalise(['title' => ['A', 'B'], 'body' => ['<p>a</p>', '<p>b</p>']]));

        $this->assertStringContainsString('<ul class="nav nav-tabs" role="tablist">', $html);
        $this->assertSame(1, substr_count($html, 'tab-item active'));
        $this->assertSame(1, substr_count($html, 'tab-pane active'));
        $this->assertStringContainsString('<p>b</p>', $html);
        $this->assertStringNotContainsString('data-cms-edit', $html);
    }

    public function testTabsPreviewEditsEachTabAndAsksWhenThereAreNone(): void
    {
        $registry = new Registry([Tabs::class => 1]);
        $slug     = Registry::slugFor(Tabs::class);

        $empty = $registry->renderNode(['slug' => $slug, 'data' => ['tabs' => []]], Registry::MODE_PREVIEW);
        $this->assertStringContainsString('Add a tab in the inspector.', $empty);
        $this->assertStringNotContainsString('data-cms-edit', $empty);

        $html = $registry->renderNode(
            ['slug' => $slug, 'data' => ['tabs' => [
                ['title' => '', 'body' => ''],
                ['title' => 'Second', 'body' => '<p>b</p>'],
            ]]],
            Registry::MODE_PREVIEW
        );

        $this->assertSame(1, substr_count($html, 'tab-item active'));
        $this->assertSame(1, substr_count($html, 'tab-pane active'));
        $this->assertStringContainsString('data-cms-action="tab" data-cms-tab="1"', $html);
        $this->assertStringContainsString('aria-selected="false"', $html);
        $this->assertStringContainsString('data-cms-field="tabs/0/title" data-cms-edit="text"', $html);
        $this->assertStringContainsString('data-cms-field="tabs/1/body" data-cms-edit="rich"', $html);
        $this->assertStringContainsString('Add a title', $html);
        $this->assertStringContainsString('Write something…', $html);
        $this->assertStringContainsString('<p>b</p>', $html);
    }

    // --------------------------------------------------------------------------

    public function testTableUpgradesTheV1JsonAndAttributes(): void
    {
        //  The v1 form posted its JSON from a textarea, so it can end in a newline
        $data = (new Table())->upgrade(['tblData' => "[[\"a\",\"b\"],[\"c\",\"d\"]]\n", 'tblAttr' => 'id="x"']);

        $this->assertSame(['rows' => [['a', 'b'], ['c', 'd']], 'attributes' => 'id="x"', 'header_rows' => 0], $data);
    }

    public function testTableUpgradeReadsCellsAsV1OutputThem(): void
    {
        //  Handsontable stores an empty cell as null, and a cell can be a number or a boolean
        $data = Table::upgradeV1(['tblData' => '[[null,12,1.5,true,false,"<b>x</b>"],[12345678901234567890]]']);

        $this->assertSame([['', '12', '1.5', '1', '', '<b>x</b>'], ['12345678901234567890']], $data['rows']);
        $this->assertArrayNotHasKey(Table::V1_DATA_KEY, $data);
    }

    public function testTableUpgradeOfAnEmptyV1Table(): void
    {
        $this->assertSame(
            ['rows' => [], 'attributes' => '', 'header_rows' => 0],
            Table::upgradeV1(['tblData' => '', 'tblAttr' => ''])
        );
        $this->assertSame(['rows' => [], 'header_rows' => 0], Table::upgradeV1(['tblData' => 'null']));
        $this->assertSame(['rows' => [['a']], 'header_rows' => 0], Table::upgradeV1(['tblData' => [['a']]]));
    }

    public function testTableUpgradeKeepsWhatItCannotConvert(): void
    {
        $this->assertSame(
            ['rows' => [], Table::V1_DATA_KEY => 'not json', 'header_rows' => 0],
            Table::upgradeV1(['tblData' => 'not json'])
        );

        //  A cell that is a list, and a row that is not one, are converted as far as they can be
        $stored = '[["a",["b"]],"row"]';
        $this->assertSame(
            ['rows' => [['a', ''], []], Table::V1_DATA_KEY => $stored, 'header_rows' => 0],
            Table::upgradeV1(['tblData' => $stored])
        );

        $this->assertSame(
            ['attributes' => '', Table::V1_ATTR_KEY => ['id' => 'x'], 'header_rows' => 0],
            Table::upgradeV1(['tblAttr' => ['id' => 'x']])
        );
    }

    public function testTableUpgradeIsIdempotent(): void
    {
        foreach ([
            ['tblData' => '[["a"]]', 'tblAttr' => 'id="x"'],
            ['tblData' => 'not json', 'other' => 1],
            ['tblData' => '[["a",{"b":1}]]'],
            ['rows' => [['a']], 'header_rows' => 2, 'attributes' => ''],
        ] as $stored) {
            $once = Table::upgradeV1($stored);
            $this->assertSame($once, Table::upgradeV1($once));
        }
    }

    public function testTableUpgradePrefersTheV1KeysTheV1EditorLastSaved(): void
    {
        //  The v1 editor saves its form over the data, with the header rows it was given
        $data = Table::upgradeV1(['rows' => [['old']], 'attributes' => 'id="old"', 'tblData' => '[["new"]]', 'tblAttr' => 'id="new"', 'header_rows' => '2']);

        $this->assertSame([['new']], $data['rows']);
        $this->assertSame('id="new"', $data['attributes']);
        $this->assertSame('2', $data['header_rows']);
        $this->assertArrayNotHasKey('tblData', $data);
        $this->assertArrayNotHasKey('tblAttr', $data);
    }

    public function testTableRendersCellsAsStored(): void
    {
        $widget = new Table();
        $html   = $widget->render($widget->normalise(['tblData' => '[["<b>a</b>","b"],["c"]]', 'tblAttr' => 'id="x"']));

        //  A v1 table has no heading rows, so its markup is the v1 markup
        $this->assertStringContainsString('<table class="table" id="x"><tbody>', $html);
        $this->assertStringNotContainsString('<thead>', $html);
        $this->assertStringContainsString('<td data-row="0" class="cms-widget-table-cell"><b>a</b></td>', $html);
        // The short row is padded by the field
        $this->assertSame(4, substr_count($html, '<td '));
    }

    public function testTableRendersItsHeaderRowsAsHeadings(): void
    {
        $widget = new Table();
        $html   = $widget->render($widget->normalise(['rows' => [['H1', 'H2'], ['a', 'b'], ['c', 'd']], 'header_rows' => 1]));

        $this->assertStringContainsString(
            '<thead><tr data-row="0" class="cms-widget-table-row cms-widget-table-header-row">'
            . '<th scope="col" data-row="0" class="cms-widget-table-cell">H1</th>',
            $html
        );
        $this->assertStringContainsString(
            '<tbody><tr data-row="0" class="cms-widget-table-row cms-widgettable-row-even"><td data-row="0" class="cms-widget-table-cell">a</td>',
            $html
        );
        $this->assertStringContainsString('<tr data-row="1" class="cms-widget-table-row cms-widgettable-row-odd">', $html);

        //  More header rows than rows makes every row a heading
        $html = $widget->render($widget->normalise(['rows' => [['H1']], 'header_rows' => 5]));
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringNotContainsString('<tbody>', $html);
    }

    public function testTableDefaultsToAnEmptyGridWithOneHeaderRow(): void
    {
        $widget   = new Table();
        $defaults = $widget->getDefaults();

        $this->assertCount(4, $defaults['rows']);
        $this->assertSame(['', '', ''], $defaults['rows'][0]);
        $this->assertSame(1, $defaults['header_rows']);
        $this->assertSame('', $widget->render(['rows' => []]));

        //  A table whose cells are all blank renders nothing on the page
        $this->assertSame('', $widget->render($widget->normalise([])));
    }

    public function testTablePreviewDrawsABlankTableAndAsksForCells(): void
    {
        $registry = new Registry([Table::class => 1]);
        $slug     = Registry::slugFor(Table::class);

        $blank = $registry->renderNode(['slug' => $slug, 'data' => []], Registry::MODE_PREVIEW);
        $this->assertStringContainsString('<thead>', $blank);
        $this->assertSame(9, substr_count($blank, '<td '));

        $none = $registry->renderNode(['slug' => $slug, 'data' => ['rows' => []]], Registry::MODE_PREVIEW);
        $this->assertStringContainsString('Add some cells in the inspector.', $none);
    }

    public function testTableFieldNamesItsHeaderRowsField(): void
    {
        $fields = Fields::validate((new Table())->getFields());

        $this->assertSame('header_rows', Fields::find($fields, 'rows')->toArray()['headerRows']);
        $this->assertSame('number', Fields::find($fields, 'header_rows')->toArray()['type']);
    }

    // --------------------------------------------------------------------------

    public function testAreaUpgradesTheV1Id(): void
    {
        $this->assertSame(['areaId' => '7'], $this->area([])->upgrade(['iAreaId' => 7]));
    }

    public function testAreaOffersTheAreasOrSaysThereAreNone(): void
    {
        $options = Fields::describe($this->area(['3' => 'Footer'])->getFields())[0]['options'];
        $this->assertSame(['' => 'Choose an area', '3' => 'Footer'], $options);

        $none = Fields::describe($this->area([])->getFields())[0];
        $this->assertSame(['' => 'No areas available'], $none['options']);
        $this->assertSame('alert alert-warning', $none['infoClass']);
    }

    public function testAreaRendersTheChosenAreaAndCannotIncludeItself(): void
    {
        $widget = $this->area(['3' => 'Footer'], function (int $id, $widget) {
            return '[' . $id . ':' . $widget->render(['areaId' => (string) $id]) . ']';
        });

        $this->assertSame('[3:]', $widget->render(['areaId' => '3']));
        $this->assertSame('', $widget->render(['areaId' => '']));
    }

    public function testAreaPreviewNamesTheAreaAndDoesNotRenderIt(): void
    {
        $rendered = false;
        $widget   = $this->area(['3' => 'Footer'], function (int $id) use (&$rendered) {
            $rendered = true;

            return 'area ' . $id;
        });

        $html = $widget->preview(['areaId' => '3']);

        $this->assertFalse($rendered);
        $this->assertStringContainsString('cms-we-area', $html);
        $this->assertStringContainsString('Footer', $html);
        $this->assertStringContainsString('This area will be rendered here.', $html);
        $this->assertStringContainsString('fa-layer-group', $html);
        $this->assertStringNotContainsString('area 3', $html);

        $empty = $widget->preview(['areaId' => '']);
        $this->assertStringContainsString('Choose an area in the inspector.', $empty);
        $this->assertStringNotContainsString('cms-we-area', $empty);

        $missing = $widget->preview(['areaId' => '9']);
        $this->assertStringContainsString('Unknown area', $missing);
        $this->assertStringContainsString('This area is no longer available.', $missing);
        $this->assertFalse($rendered);
    }

    /**
     * An Area widget with fixed areas, whose area renders through $render
     *
     * @param array<string, string> $areas
     */
    private function area(array $areas, ?\Closure $render = null): Area
    {
        $render ??= fn(int $id) => 'area ' . $id;

        return new class ($areas, $render) extends Area {
            public function __construct(private array $fixed, private \Closure $renderer)
            {
            }

            protected function areaOptions(): array
            {
                return $this->fixed;
            }

            protected function renderArea(int $areaId): string
            {
                return ($this->renderer)($areaId, $this);
            }
        };
    }
}
