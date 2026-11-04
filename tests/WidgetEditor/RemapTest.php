<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Service\Remap;
use PHPUnit\Framework\TestCase;

/**
 * Covers the slug rewrite behind the remap migration trait (D14)
 */
class RemapTest extends TestCase
{
    const MAP = ['Heading' => 'App.Cms.Widgets.Heading', 'Stack' => 'App.Cms.Widgets.Stack'];

    // --------------------------------------------------------------------------

    public function testTopLevelNodesAreRewritten(): void
    {
        $aResult = Remap::rewrite([
            ['slug' => 'Heading', 'data' => ['sTitle' => 'Hi']],
            ['slug' => 'Other', 'data' => []],
        ], self::MAP);

        $this->assertSame(1, $aResult['nodes']);
        $this->assertSame('App.Cms.Widgets.Heading', $aResult['value'][0]['slug']);
        $this->assertSame(['sTitle' => 'Hi'], $aResult['value'][0]['data']);
        $this->assertSame('Other', $aResult['value'][1]['slug']);
    }

    // --------------------------------------------------------------------------

    public function testNodesInSlotsAreRewrittenAtAnyDepth(): void
    {
        $aResult = Remap::rewrite([
            [
                'slug' => 'Stack',
                'data' => [
                    'items' => [
                        ['slug' => 'Heading', 'data' => ['sTitle' => 'Nested']],
                        [
                            'slug' => 'Stack',
                            'data' => ['items' => [['slug' => 'Heading', 'data' => []]]],
                        ],
                    ],
                ],
            ],
        ], self::MAP);

        //  Outer Stack, the Heading, the nested Stack and the nested Heading
        $this->assertSame(4, $aResult['nodes']);
        $aStack = $aResult['value'][0];
        $this->assertSame('App.Cms.Widgets.Stack', $aStack['slug']);
        $this->assertSame('App.Cms.Widgets.Heading', $aStack['data']['items'][0]['slug']);
        $this->assertSame('App.Cms.Widgets.Stack', $aStack['data']['items'][1]['slug']);
        $this->assertSame(
            'App.Cms.Widgets.Heading',
            $aStack['data']['items'][1]['data']['items'][0]['slug']
        );
    }

    // --------------------------------------------------------------------------

    public function testAValueThatIsNotANodeIsNeverRewritten(): void
    {
        //  A "slug" with no "data" is not a widget, and a plain string is never a slug
        $aResult = Remap::rewrite([
            'slug'     => 'Heading',
            'settings' => ['slug' => 'Heading'],
            'label'    => 'Heading',
        ], self::MAP);

        $this->assertSame(0, $aResult['nodes']);
        $this->assertSame('Heading', $aResult['value']['slug']);
        $this->assertSame(['slug' => 'Heading'], $aResult['value']['settings']);
    }

    // --------------------------------------------------------------------------

    public function testAnUnmappedSlugIsLeftAsItIs(): void
    {
        $aResult = Remap::rewrite([['slug' => 'Unknown', 'data' => []]], self::MAP);
        $this->assertSame(0, $aResult['nodes']);
        $this->assertSame('Unknown', $aResult['value'][0]['slug']);
    }

    // --------------------------------------------------------------------------

    public function testRewritingTwiceChangesNothingTheSecondTime(): void
    {
        $aFirst  = Remap::rewrite([['slug' => 'Heading', 'data' => []]], self::MAP);
        $aSecond = Remap::rewrite($aFirst['value'], self::MAP);

        $this->assertSame(1, $aFirst['nodes']);
        $this->assertSame(0, $aSecond['nodes']);
        $this->assertSame($aFirst['value'], $aSecond['value']);
    }

    // --------------------------------------------------------------------------

    public function testScalarsPassThrough(): void
    {
        $this->assertSame(['value' => null, 'nodes' => 0], Remap::rewrite(null, self::MAP));
        $this->assertSame(['value' => 'text', 'nodes' => 0], Remap::rewrite('text', self::MAP));
    }
}
