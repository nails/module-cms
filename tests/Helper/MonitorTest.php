<?php

namespace Tests\Helper;

use Nails\Cms\Factory\Monitor\Item;
use Nails\Cms\Helper\Monitor;
use PHPUnit\Framework\TestCase;

class MonitorTest extends TestCase
{
    public function testJsonStringQuotesPlainSlug(): void
    {
        self::assertSame('"richtext"', Monitor::jsonString('richtext'));
    }

    public function testJsonStringMatchesMysqlRendering(): void
    {
        self::assertSame('"App.Cms.Widgets.Heading"', Monitor::jsonString('App.Cms.Widgets.Heading'));
        self::assertSame('"a\\\\b"', Monitor::jsonString('a\\b'));
        self::assertSame('"a\\"b"', Monitor::jsonString('a"b'));
        self::assertSame('"a/b"', Monitor::jsonString('a/b'));
        self::assertSame('"é"', Monitor::jsonString('é'));
    }

    public function testLikePatternWrapsJsonString(): void
    {
        self::assertSame('%"richtext"%', Monitor::likePattern('richtext'));
    }

    public function testLikePatternEscapesWildcards(): void
    {
        self::assertSame('%"a!%b!_c!!d"%', Monitor::likePattern('a%b_c!d'));
    }

    public function testWidgetExportQueryDropsMissingInput(): void
    {
        self::assertSame([], Monitor::widgetExportQuery(false, false, false));
        self::assertSame([], Monitor::widgetExportQuery('', null, 0));
    }

    public function testWidgetExportQueryKeepsARealSearchAndSort(): void
    {
        self::assertSame(
            [
                'keywords'  => '0',
                'sortOn'    => 'usages',
                'sortOrder' => 'desc',
            ],
            Monitor::widgetExportQuery('0', 'usages', 'desc')
        );
    }

    public function testWidgetCsvIncludesTheHeaderAndInUseFlag(): void
    {
        $rows = Monitor::widgetCsv([
            new Item('richtext', 'Rich Text', 'A description', '', 3, false, ''),
            new Item('old', 'Old', 'Gone', '', 0, true, 'Rich Text'),
        ]);

        self::assertSame(['Label', 'Slug', 'Description', 'Usages', 'In use', 'Deprecated'], $rows[0]);
        self::assertSame(['Rich Text', 'richtext', 'A description', 3, 'Yes', 'No'], $rows[1]);
        self::assertSame(['Old', 'old', 'Gone', 0, 'No', 'Yes'], $rows[2]);
    }

    public function testWidgetCsvNotInUseMeansNoUsagesWhateverTheDeprecatedFlag(): void
    {
        $rows = Monitor::widgetCsv([
            new Item('unused', 'Unused', '', '', 0, false, ''),
        ]);

        self::assertSame(['Unused', 'unused', '', 0, 'No', 'No'], $rows[1]);
    }
}
