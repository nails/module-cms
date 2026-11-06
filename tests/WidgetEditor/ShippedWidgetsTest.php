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
 * The shipped widgets ported to WidgetEditor (v2). The native ports must render what their v1 views rendered, keep
 * the v1 data keys and slugs, and declare a valid fields schema. The bridges keep the v1 class behind the v2 surface.
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

        $aExpected = ['cdn_object', 'number', 'repeater', 'richtext', 'select', 'text', 'textarea', 'toggle'];
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

    public function testBridgesUseTheLegacyTrait(): void
    {
        foreach ([Table::class, Tabs::class, Accordion::class, Area::class] as $sClass) {
            $this->assertContains(Legacy::class, (new \ReflectionClass($sClass))->getTraitNames(), $sClass);
        }
    }
}
