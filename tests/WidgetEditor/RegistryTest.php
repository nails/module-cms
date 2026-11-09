<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Attribute\DoesNotOverride;
use Nails\Cms\WidgetEditor\Attribute\Overrides;
use Nails\Cms\WidgetEditor\Exception\RegistryException;
use Nails\Cms\WidgetEditor\Monitor\Adapter;
use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Cms\WidgetEditor\Status;
use Nails\Cms\WidgetEditor\Widget\Base;
use PHPUnit\Framework\TestCase;

/*
 * Fixtures. Declared here so they autoload without a test autoload entry.
 */

/** A leaf which renders nothing; for fixtures that only exercise the registry */
abstract class Leaf extends Base
{
    public function render(array $data): string
    {
        return '';
    }
}

class Heading extends Base
{
    const ALIASES = ['legacy-heading'];

    protected array $defaults = ['sText' => 'Hello'];

    public function render(array $data): string
    {
        return '<h1>' . $data['sText'] . '</h1>';
    }
}

/** Cross-component override: higher precedence in the test registry */
class HeadingOverride extends Heading
{
    public function render(array $data): string
    {
        return '<h2>' . $data['sText'] . '</h2>';
    }
}

/** Same-component variant: equal precedence, so a separate widget */
class HeadingVariant extends Heading
{
    const ALIASES = [];

    public function render(array $data): string
    {
        return '<h3>' . $data['sText'] . '</h3>';
    }
}

/** Higher precedence, but refuses to override */
#[DoesNotOverride]
class HeadingDeclined extends Heading
{
    const ALIASES = [];
}

/** Equal precedence, but forced to override */
#[Overrides]
class HeadingForced extends Heading
{
}

/** Forced both ways */
#[Overrides]
#[DoesNotOverride]
class HeadingBoth extends Heading
{
}

/** Forced to override a class that is not registered */
#[Overrides]
class Orphan extends Leaf
{
}

class Columns extends Base
{
    const ALIASES = [];

    public function getSlots(): array
    {
        return [
            'left'  => ['label' => 'Left', 'max' => null],
            'right' => ['label' => 'Right', 'max' => null],
        ];
    }

    public function render(array $data): string
    {
        return '<div>' . $this->renderSlot('left', $data) . '|' . $this->renderSlot('right', $data) . '</div>';
    }
}

/** Claims an alias that Heading already owns */
class Collide extends Leaf
{
    const ALIASES = ['legacy-heading'];
}

class Disabled extends Leaf
{
    const STATUS  = Status::DISABLED;
    const ALIASES = [];
}

class Deprecated extends Leaf
{
    const STATUS      = Status::DEPRECATED;
    const ALTERNATIVE = 'Tests.WidgetEditor.Heading';
    const ALIASES     = [];
}

// --------------------------------------------------------------------------

class RegistryTest extends TestCase
{
    private const HEADING = 'Tests.WidgetEditor.Heading';
    private const COLUMNS = 'Tests.WidgetEditor.Columns';

    // Slugs and classes

    public function testSlugRoundTrip(): void
    {
        self::assertSame('App.Cms.Widgets.Heading', Registry::slugFor('\App\Cms\Widgets\Heading'));
        self::assertSame('\App\Cms\Widgets\Heading', Registry::classFor('App.Cms.Widgets.Heading'));
    }

    public function testStatusRendering(): void
    {
        self::assertTrue(Status::ACTIVE->renders());
        self::assertTrue(Status::HIDDEN->renders());
        self::assertTrue(Status::DEPRECATED->renders());
        self::assertFalse(Status::DISABLED->renders());
    }

    // Overrides

    public function testHigherPrecedenceOverridesAndKeepsRootSlug(): void
    {
        $oRegistry = new Registry([Heading::class => 1, HeadingOverride::class => 2]);

        $oWidget = $oRegistry->resolve(self::HEADING);
        self::assertInstanceOf(HeadingOverride::class, $oWidget);
        self::assertSame(self::HEADING, $oRegistry->canonicalSlugFor(HeadingOverride::class));
        //  The root's slug, its alias, and the overriding class's own slug all resolve to the root
        self::assertEqualsCanonicalizing(
            [self::HEADING, 'legacy-heading', 'Tests.WidgetEditor.HeadingOverride'],
            $oRegistry->slugsFor(self::HEADING)
        );
    }

    public function testAliasResolvesToOverridingWidget(): void
    {
        $oRegistry = new Registry([Heading::class => 1, HeadingOverride::class => 2]);

        self::assertInstanceOf(HeadingOverride::class, $oRegistry->resolve('legacy-heading'));
    }

    public function testSameComponentVariantIsSeparateWidget(): void
    {
        $oRegistry = new Registry([Heading::class => 1, HeadingVariant::class => 1]);

        self::assertInstanceOf(Heading::class, $oRegistry->resolve(self::HEADING));
        self::assertInstanceOf(HeadingVariant::class, $oRegistry->resolve('Tests.WidgetEditor.HeadingVariant'));
        self::assertSame(
            'Tests.WidgetEditor.HeadingVariant',
            $oRegistry->canonicalSlugFor(HeadingVariant::class)
        );
    }

    public function testDoesNotOverrideAttributeKeepsSeparateWidget(): void
    {
        $oRegistry = new Registry([Heading::class => 1, HeadingDeclined::class => 2]);

        self::assertInstanceOf(Heading::class, $oRegistry->resolve(self::HEADING));
        self::assertInstanceOf(HeadingDeclined::class, $oRegistry->resolve('Tests.WidgetEditor.HeadingDeclined'));
    }

    public function testOverridesAttributeForcesOverrideAtEqualPrecedence(): void
    {
        $oRegistry = new Registry([Heading::class => 1, HeadingForced::class => 1]);

        self::assertInstanceOf(HeadingForced::class, $oRegistry->resolve(self::HEADING));
    }

    public function testDuplicateOverridesThrow(): void
    {
        $oRegistry = new Registry([
            Heading::class       => 1,
            HeadingOverride::class => 2,
            HeadingForced::class => 1,
        ]);

        $this->expectException(RegistryException::class);
        $oRegistry->has(self::HEADING);
    }

    public function testOverridesAttributeWithoutParentThrows(): void
    {
        $oRegistry = new Registry([Orphan::class => 1]);

        $this->expectException(RegistryException::class);
        $oRegistry->has('Tests.WidgetEditor.Orphan');
    }

    public function testBothAttributesThrow(): void
    {
        $oRegistry = new Registry([Heading::class => 1, HeadingBoth::class => 2]);

        $this->expectException(RegistryException::class);
        $oRegistry->has(self::HEADING);
    }

    public function testAliasCollisionThrows(): void
    {
        //  Two unrelated widgets claim the same alias
        $oRegistry = new Registry([Heading::class => 1, Collide::class => 1]);

        $this->expectException(RegistryException::class);
        $oRegistry->has(self::HEADING);
    }

    public function testVariantThatClearsInheritedAliasDoesNotCollide(): void
    {
        //  HeadingVariant clears ALIASES in the fixture, so Heading keeps 'legacy-heading' and nothing collides
        $oRegistry = new Registry([Heading::class => 1, HeadingVariant::class => 1]);

        self::assertTrue($oRegistry->has(self::HEADING));
        self::assertInstanceOf(Heading::class, $oRegistry->resolve('legacy-heading'));
    }

    // Status

    public function testDisabledIsKnownButDoesNotRender(): void
    {
        $oRegistry = new Registry([Disabled::class => 1]);
        $sSlug     = 'Tests.WidgetEditor.Disabled';

        self::assertTrue($oRegistry->knows($sSlug));
        self::assertFalse($oRegistry->has($sSlug));
        self::assertNull($oRegistry->resolve($sSlug));
        self::assertArrayHasKey($sSlug, $oRegistry->known());
        self::assertSame([], $oRegistry->all());
        self::assertSame('', $oRegistry->renderNode(['slug' => $sSlug, 'data' => []], Registry::MODE_PREVIEW));
    }

    public function testUnknownSlugRendersEmpty(): void
    {
        $oRegistry = new Registry([Heading::class => 1]);

        self::assertFalse($oRegistry->knows('richtext'));
        self::assertSame('', $oRegistry->renderNode(['slug' => 'richtext', 'data' => []], Registry::MODE_PREVIEW));
    }

    // Nesting

    public function testSlotsRenderChildrenInPreviewMode(): void
    {
        $oRegistry = new Registry([Heading::class => 1, Columns::class => 1]);

        $aNode = [
            'slug' => self::COLUMNS,
            'data' => [
                'left'  => [['slug' => self::HEADING, 'data' => ['sText' => 'A']]],
                'right' => [['slug' => self::HEADING, 'data' => ['sText' => 'B']]],
            ],
        ];

        self::assertSame(
            '<div>'
            . '<!--cms-we-slot left--><div class="cms-we-slot"><!--cms-we-node left/0--><h1>A</h1><!--/cms-we-node left/0--></div><!--/cms-we-slot left-->'
            . '|'
            . '<!--cms-we-slot right--><div class="cms-we-slot"><!--cms-we-node right/0--><h1>B</h1><!--/cms-we-node right/0--></div><!--/cms-we-slot right-->'
            . '</div>',
            $oRegistry->renderNode($aNode, Registry::MODE_PREVIEW)
        );
    }

    public function testSlotsRenderWithoutMarkersOnThePage(): void
    {
        $oRegistry = new Registry([Heading::class => 1, Columns::class => 1]);

        $aNode = [
            'slug' => self::COLUMNS,
            'data' => [
                'left'  => [['slug' => self::HEADING, 'data' => ['sText' => 'A']]],
                'right' => [],
            ],
        ];

        self::assertSame('<div><h1>A</h1>|</div>', $oRegistry->renderNode($aNode));
    }

    public function testEmptySlotHoldsAPlaceInPreviewMode(): void
    {
        $oRegistry = new Registry([Heading::class => 1, Columns::class => 1]);

        $aNode = [
            'slug' => self::COLUMNS,
            'data' => [
                'left'  => [[
                    'slug' => self::COLUMNS,
                    'data' => ['left' => [], 'right' => []],
                ]],
                'right' => [],
            ],
        ];

        $sHtml = $oRegistry->renderNode($aNode, Registry::MODE_PREVIEW);

        self::assertStringContainsString(
            '<!--cms-we-slot left%2F0%2Fleft--><div class="cms-we-slot"><div class="cms-we-slot-empty" data-cms-slot-empty></div></div><!--/cms-we-slot left%2F0%2Fleft-->',
            $sHtml
        );
        self::assertSame(3, substr_count($sHtml, 'data-cms-slot-empty'));
    }

    public function testDepthCapSkipsDeeperNodes(): void
    {
        //  Depth 0: outer. Depth 1: inner. Depth 2: the heading, skipped with a cap of 1.
        $oRegistry = new Registry([Heading::class => 1, Columns::class => 1], 1);

        $aNode = [
            'slug' => self::COLUMNS,
            'data' => [
                'left' => [[
                    'slug' => self::COLUMNS,
                    'data' => [
                        'left'  => [['slug' => self::HEADING, 'data' => ['sText' => 'Deep']]],
                        'right' => [],
                    ],
                ]],
                'right' => [],
            ],
        ];

        self::assertSame('<div><div>|</div>|</div>', $oRegistry->renderNode($aNode));
    }

    public function testWalkVisitsNestedInstancesWithPaths(): void
    {
        $aNodes = [
            ['slug' => 'A', 'data' => ['left' => [['slug' => 'B', 'data' => []]]]],
            ['slug' => 'C', 'data' => []],
        ];

        $aSeen = [];
        Registry::walk($aNodes, function (array $aNode, string $sPath) use (&$aSeen) {
            $aSeen[] = $sPath . '=' . $aNode['slug'];
        });

        self::assertSame(['0=A', '0.data.left.0=B', '1=C'], $aSeen);
    }

    // Monitor adapter

    public function testAdapterCountsAcrossAliasesAndReportsDeprecation(): void
    {
        $oAdapter = new Adapter(new Deprecated(), 'Tests.WidgetEditor.Deprecated', ['legacy-deprecated']);

        self::assertSame(
            ['Tests.WidgetEditor.Deprecated', 'legacy-deprecated'],
            array_map(fn(Adapter $o) => $o->getSlug(), $oAdapter->getMonitoredWidgets())
        );
        self::assertTrue($oAdapter->isWidgetDeprecated());
        self::assertSame('Tests.WidgetEditor.Heading', $oAdapter->getWidgetAlternative());
        self::assertFalse(Adapter::isDeprecated(), 'The static v1 flag is inert');
    }
}
