<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Cms\Widgets\Accordion;
use Nails\Cms\Cms\Widgets\Area;
use Nails\Cms\Cms\Widgets\Blockquote;
use Nails\Cms\Cms\Widgets\Html;
use Nails\Cms\Cms\Widgets\Richtext;
use Nails\Cms\Cms\Widgets\Table;
use Nails\Cms\Cms\Widgets\Tabs;
use Nails\Cms\WidgetEditor\Service\Fields;
use Nails\Cms\WidgetEditor\Traits\Legacy;
use PHPUnit\Framework\TestCase;

/**
 * The shipped widgets ported to WidgetEditor (v2). The native ports must render what their v1 views rendered, keep
 * the v1 data keys and slugs, and declare a valid fields schema. The bridges keep the v1 class behind the v2 surface.
 */
class ShippedWidgetsTest extends TestCase
{
    public function testRichtextRendersNothingWhenEmpty(): void
    {
        $this->assertSame('', (new Richtext())->render(['body' => '']));
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
        foreach ([new Richtext(), new Html(), new Blockquote()] as $oWidget) {
            $aFields = Fields::normalise($oWidget->getFields());
            $this->assertNotEmpty($aFields);
        }
    }

    public function testBridgesUseTheLegacyTrait(): void
    {
        foreach ([Table::class, Tabs::class, Accordion::class, Area::class] as $sClass) {
            $this->assertContains(Legacy::class, (new \ReflectionClass($sClass))->getTraitNames(), $sClass);
        }
    }
}
