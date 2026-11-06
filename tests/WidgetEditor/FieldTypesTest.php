<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Cms\Fields;
use Nails\Cms\WidgetEditor\Field\Base;
use Nails\Cms\WidgetEditor\Service\FieldTypes;
use PHPUnit\Framework\TestCase;

/*
 * Fixtures. Declared here so they autoload without a test autoload entry.
 */

class ColourField extends Base
{
    public static function getType(): string
    {
        return 'colour';
    }

    public static function getAssets(): array
    {
        return [['admin.colour.min.js', 'app'], 'https://example.test/colour.css'];
    }
}

class AppText extends Fields\Text
{
    public static function getAssets(): array
    {
        return [['admin.text.min.js', 'app']];
    }
}

class OtherText extends Base
{
    public static function getType(): string
    {
        return 'text';
    }
}

class SharedAssetField extends Base
{
    public static function getType(): string
    {
        return 'shared';
    }

    public static function getAssets(): array
    {
        return [['admin.colour.min.js', 'app']];
    }
}

// --------------------------------------------------------------------------

class FieldTypesTest extends TestCase
{
    public function testListsTypesByName(): void
    {
        $oTypes = new FieldTypes([Fields\Text::class => 1, ColourField::class => 2]);

        self::assertSame(['colour' => ColourField::class, 'text' => Fields\Text::class], $oTypes->all());
    }

    public function testASubclassFromAHigherComponentReplacesTheType(): void
    {
        $oTypes = new FieldTypes([Fields\Text::class => 1, AppText::class => 5]);

        self::assertSame(['text' => AppText::class], $oTypes->all());
        self::assertSame([['admin.text.min.js', 'app']], $oTypes->assets());
    }

    public function testTwoUnrelatedClassesWithOneNameThrow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FieldTypes([Fields\Text::class => 1, OtherText::class => 5]);
    }

    public function testASubclassFromALowerComponentDoesNotReplaceTheType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FieldTypes([Fields\Text::class => 5, AppText::class => 1]);
    }

    public function testCollectsAssetsOnce(): void
    {
        $oTypes = new FieldTypes([ColourField::class => 1, SharedAssetField::class => 1, Fields\Text::class => 1]);

        self::assertSame(
            [['admin.colour.min.js', 'app'], 'https://example.test/colour.css'],
            $oTypes->assets()
        );
    }
}
