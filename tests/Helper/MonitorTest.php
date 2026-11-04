<?php

namespace Tests\Helper;

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
}
