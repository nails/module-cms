<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Console\Command\WidgetEditor\Bridge;
use PHPUnit\Framework\TestCase;

/**
 * The source the bridge command writes. A bridge with no parent claims its own alias; a bridge with a parent
 * overrides the v2 widget that already claims the slug, so the two never claim the same alias.
 */
class BridgeTest extends TestCase
{
    private function source(string $sSlug, string $sClass, ?string $sParent): string
    {
        $oCommand = (new \ReflectionClass(Bridge::class))->newInstanceWithoutConstructor();
        $oMethod  = new \ReflectionMethod(Bridge::class, 'bridgeSource');

        return $oMethod->invoke($oCommand, $sSlug, $sClass, $sParent);
    }

    public function testBridgeWithoutParentIsAFinalV2Widget(): void
    {
        $sSource = $this->source('FAQBlock', 'App\\Cms\\Widget\\FAQBlock', null);

        $this->assertStringContainsString('namespace App\\Cms\\Widgets;', $sSource);
        $this->assertStringContainsString('final class FAQBlock extends Base', $sSource);
        $this->assertStringNotContainsString('#[Overrides]', $sSource);
        $this->assertStringContainsString('return \\App\\Cms\\Widget\\FAQBlock::class;', $sSource);
    }

    public function testBridgeWithParentOverridesThatWidget(): void
    {
        $sSource = $this->source('Area', 'App\\Cms\\Widget\\Area', 'Nails\\Cms\\Cms\\Widgets\\Area');

        $this->assertStringContainsString('use Nails\\Cms\\WidgetEditor\\Attribute\\Overrides;', $sSource);
        $this->assertStringContainsString('#[Overrides]', $sSource);
        $this->assertStringContainsString('class Area extends \\Nails\\Cms\\Cms\\Widgets\\Area', $sSource);
        $this->assertStringNotContainsString('final class', $sSource);
        $this->assertStringNotContainsString(' extends Base', $sSource);
    }

    public function testOnlyAppV1WidgetsAreBridgedOverANativeWidget(): void
    {
        $oMethod = new \ReflectionMethod(Bridge::class, 'isAppClass');

        $this->assertTrue($oMethod->invoke(null, 'App\\Cms\\Widget\\Area'));
        $this->assertTrue($oMethod->invoke(null, '\\App\\Cms\\Widget\\Area'));
        $this->assertFalse($oMethod->invoke(null, 'Nails\\Cms\\Cms\\Widget\\Area'));
    }
}
