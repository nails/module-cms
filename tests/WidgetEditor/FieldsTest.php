<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Service\Fields;
use Nails\Cms\WidgetEditor\Widget\Base;
use PHPUnit\Framework\TestCase;

/*
 * Fixtures. Declared here so they autoload without a test autoload entry.
 */

class FieldsFixture extends Base
{
    const ALIASES = [];

    public function getFields(): ?array
    {
        return [
            ['key' => 'sHeading', 'type' => 'text', 'label' => 'Heading'],
            ['key' => 'bShowLink', 'type' => 'toggle', 'label' => 'Show link'],
            ['key' => 'aItems', 'type' => 'repeater', 'fields' => [
                ['key' => 'sText', 'type' => 'text', 'label' => 'Text'],
                ['key' => 'sTone', 'type' => 'select', 'options' => ['light' => 'Light', 'dark' => 'Dark']],
            ]],
        ];
    }

    public function render(array $data): string
    {
        return '';
    }
}

// --------------------------------------------------------------------------

class FieldsTest extends TestCase
{
    public function testNormaliseFillsTypeDefaults(): void
    {
        $aFields = Fields::normalise([
            ['key' => 'sTitle', 'type' => 'text'],
            ['key' => 'bOn', 'type' => 'toggle'],
            ['key' => 'sTone', 'type' => 'select', 'options' => ['light' => 'Light', 'dark' => 'Dark']],
            ['key' => 'aRows', 'type' => 'repeater', 'fields' => [['key' => 'sText', 'type' => 'text']]],
        ]);

        self::assertSame('sTitle', $aFields[0]['label'], 'the label falls back to the key');
        self::assertSame('', $aFields[0]['default']);
        self::assertFalse($aFields[1]['default']);
        self::assertSame('light', $aFields[2]['default'], 'select defaults to its first option');
        self::assertSame([], $aFields[3]['default'], 'repeater defaults to no rows');
        self::assertSame(['key' => 'sText', 'type' => 'text', 'label' => 'sText', 'tip' => '', 'required' => false, 'wide' => false, 'visibleWhen' => [], 'default' => ''], $aFields[3]['fields'][0]);
    }

    public function testExplicitDefaultIsKept(): void
    {
        $aFields = Fields::normalise([['key' => 'sTone', 'type' => 'select', 'options' => ['a' => 'A', 'b' => 'B'], 'default' => 'b']]);

        self::assertSame('b', $aFields[0]['default']);
    }

    public function testFillAddsMissingKeysAndRowDefaults(): void
    {
        $aFields = Fields::normalise((new FieldsFixture())->getFields());

        $aData = Fields::fill($aFields, [
            'aItems' => [['sText' => 'One'], ['sText' => 'Two', 'sTone' => 'dark']],
        ]);

        self::assertSame('', $aData['sHeading']);
        self::assertFalse($aData['bShowLink']);
        self::assertSame([
            ['sText' => 'One', 'sTone' => 'light'],
            ['sText' => 'Two', 'sTone' => 'dark'],
        ], $aData['aItems']);
    }

    public function testNormaliseOnAWidgetFillsDefaultsWithoutSavingThem(): void
    {
        $oWidget = new FieldsFixture();
        $aData   = $oWidget->normalise(['sHeading' => 'Hi']);

        self::assertSame('Hi', $aData['sHeading']);
        self::assertFalse($aData['bShowLink']);
        self::assertSame([], $aData['aItems']);
    }

    public function testRejectsAnInvalidKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([['key' => '1bad', 'type' => 'text']]);
    }

    public function testRejectsAnUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([['key' => 'sTitle', 'type' => 'table']]);
    }

    public function testRejectsADuplicateKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([
            ['key' => 'sTitle', 'type' => 'text'],
            ['key' => 'sTitle', 'type' => 'textarea'],
        ]);
    }

    public function testRejectsANestedRepeater(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([[
            'key'    => 'aOuter',
            'type'   => 'repeater',
            'fields' => [['key' => 'aInner', 'type' => 'repeater', 'fields' => [['key' => 'sText', 'type' => 'text']]]],
        ]]);
    }

    public function testRejectsASelectWithoutOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([['key' => 'sTone', 'type' => 'select']]);
    }

    public function testRejectsASelectDefaultThatIsNotAnOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([['key' => 'sTone', 'type' => 'select', 'options' => ['a' => 'A'], 'default' => 'z']]);
    }

    public function testRejectsVisibleWhenForAnUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fields::normalise([
            ['key' => 'sLink', 'type' => 'text', 'visibleWhen' => ['bShowLink' => true]],
        ]);
    }

    public function testAcceptsVisibleWhenForASibling(): void
    {
        $aFields = Fields::normalise([
            ['key' => 'bShowLink', 'type' => 'toggle'],
            ['key' => 'sLink', 'type' => 'text', 'visibleWhen' => ['bShowLink' => true]],
        ]);

        self::assertSame(['bShowLink' => true], $aFields[1]['visibleWhen']);
    }

    public function testCdnObjectKeepsItsBucket(): void
    {
        $aFields = Fields::normalise([['key' => 'iImageId', 'type' => 'cdn_object', 'bucket' => 'images']]);

        self::assertSame('images', $aFields[0]['bucket']);
        self::assertNull($aFields[0]['default']);
    }
}
