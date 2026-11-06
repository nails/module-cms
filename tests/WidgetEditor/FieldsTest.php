<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Cms\Fields;
use Nails\Cms\WidgetEditor\Service\Fields as FieldList;
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
            Fields\Text::make('sHeading')->label('Heading'),
            Fields\Toggle::make('bShowLink')->label('Show link'),
            Fields\Repeater::make('aItems')->fields([
                Fields\Text::make('sText')->label('Text'),
                Fields\Select::make('sTone')->options(['light' => 'Light', 'dark' => 'Dark']),
            ]),
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
    public function testDescribeFillsTypeDefaults(): void
    {
        $aFields = FieldList::describe([
            Fields\Text::make('sTitle'),
            Fields\Toggle::make('bOn'),
            Fields\Select::make('sTone')->options(['light' => 'Light', 'dark' => 'Dark']),
            Fields\Repeater::make('aRows')->fields([Fields\Text::make('sText')]),
        ]);

        self::assertSame('sTitle', $aFields[0]['label'], 'the label falls back to the key');
        self::assertSame('', $aFields[0]['default']);
        self::assertFalse($aFields[1]['default']);
        self::assertSame('light', $aFields[2]['default'], 'select defaults to its first option');
        self::assertSame([], $aFields[3]['default'], 'repeater defaults to no rows');
        self::assertSame(
            ['key' => 'sText', 'type' => 'text', 'label' => 'sText', 'tip' => '', 'subtitle' => '', 'info' => '', 'infoClass' => '', 'required' => false, 'wide' => false, 'visibleWhen' => [], 'default' => '', 'maxlength' => null],
            $aFields[3]['fields'][0]
        );
    }

    public function testDescribeCarriesTheSharedSettings(): void
    {
        $aField = FieldList::describe([
            Fields\Toggle::make('bOn'),
            Fields\Text::make('sTitle')->label('Title')->tip('A tip')->subtitle('Under the label')->info('More context', 'alert alert-info')->required()->wide()->visibleWhen(['bOn' => true]),
        ])[1];

        self::assertSame('Title', $aField['label']);
        self::assertSame('A tip', $aField['tip']);
        self::assertSame('Under the label', $aField['subtitle']);
        self::assertSame('More context', $aField['info']);
        self::assertSame('alert alert-info', $aField['infoClass']);
        self::assertTrue($aField['required']);
        self::assertTrue($aField['wide']);
        self::assertSame(['bOn' => true], $aField['visibleWhen']);
    }

    public function testExplicitDefaultIsKept(): void
    {
        $aFields = FieldList::describe([Fields\Select::make('sTone')->options(['a' => 'A', 'b' => 'B'])->default('b')]);

        self::assertSame('b', $aFields[0]['default']);
    }

    public function testRepeaterIgnoresAnExplicitDefault(): void
    {
        $oField = Fields\Repeater::make('aRows')->fields([Fields\Text::make('sText')])->default([['sText' => 'x']]);

        self::assertSame([], $oField->getDefault());
    }

    public function testFillAddsMissingKeysAndRowDefaults(): void
    {
        $aFields = (new FieldsFixture())->getFields();

        $aData = FieldList::fill($aFields, [
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
        Fields\Text::make('1bad');
    }

    public function testRejectsSomethingThatIsNotAField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([['key' => 'sTitle', 'type' => 'text']]);
    }

    public function testRejectsADuplicateKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([
            Fields\Text::make('sTitle'),
            Fields\Textarea::make('sTitle'),
        ]);
    }

    public function testRejectsANestedRepeater(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([
            Fields\Repeater::make('aOuter')->fields([
                Fields\Repeater::make('aInner')->fields([Fields\Text::make('sText')]),
            ]),
        ]);
    }

    public function testRejectsARepeaterWithoutFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([Fields\Repeater::make('aRows')]);
    }

    public function testRejectsADuplicateKeyInsideARow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([
            Fields\Repeater::make('aRows')->fields([Fields\Text::make('sText'), Fields\Text::make('sText')]),
        ]);
    }

    public function testRejectsASelectWithoutOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([Fields\Select::make('sTone')]);
    }

    public function testRejectsASelectDefaultThatIsNotAnOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([Fields\Select::make('sTone')->options(['a' => 'A'])->default('z')]);
    }

    public function testRejectsVisibleWhenForAnUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([
            Fields\Text::make('sLink')->visibleWhen(['bShowLink' => true]),
        ]);
    }

    public function testRejectsVisibleWhenForAFieldOutsideTheRow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::describe([
            Fields\Toggle::make('bShowLink'),
            Fields\Repeater::make('aRows')->fields([
                Fields\Text::make('sLink')->visibleWhen(['bShowLink' => true]),
            ]),
        ]);
    }

    public function testAcceptsVisibleWhenForASibling(): void
    {
        $aFields = FieldList::describe([
            Fields\Toggle::make('bShowLink'),
            Fields\Text::make('sLink')->visibleWhen(['bShowLink' => true]),
        ]);

        self::assertSame(['bShowLink' => true], $aFields[1]['visibleWhen']);
    }

    public function testRichtextDefaultsToTheFullEditor(): void
    {
        $oField = Fields\Richtext::make('body');

        self::assertSame('default', $oField->toArray()['editor']);
        self::assertSame('rich', $oField->getCanvasEdit());
    }

    public function testRichtextKeepsTheBasicEditor(): void
    {
        $oField = Fields\Richtext::make('quote')->basic();

        self::assertSame('basic', $oField->toArray()['editor']);
        self::assertSame('rich-basic', $oField->getCanvasEdit());
    }

    public function testOnlyTextAndRichtextAreEditedOnTheCanvas(): void
    {
        self::assertSame('text', Fields\Text::make('a')->getCanvasEdit());
        self::assertNull(Fields\Textarea::make('a')->getCanvasEdit());
        self::assertNull(Fields\Select::make('a')->getCanvasEdit());
        self::assertNull(Fields\Toggle::make('a')->getCanvasEdit());
        self::assertNull(Fields\Number::make('a')->getCanvasEdit());
        self::assertNull(Fields\Repeater::make('a')->getCanvasEdit());
    }

    public function testTypeSettingsAreDescribed(): void
    {
        self::assertSame(80, Fields\Text::make('a')->maxLength(80)->toArray()['maxlength']);
        self::assertSame(300, Fields\Textarea::make('a')->maxLength(300)->toArray()['maxlength']);

        $aNumber = Fields\Number::make('a')->min(0)->max(5)->toArray();
        self::assertSame(0.0, $aNumber['min']);
        self::assertSame(5.0, $aNumber['max']);
        self::assertNull($aNumber['default']);

        $aRows = Fields\Repeater::make('a')->min(1)->max(3)->fields([Fields\Text::make('b')])->toArray();
        self::assertSame(1, $aRows['min']);
        self::assertSame(3, $aRows['max']);
    }

    public function testSelectOptionKeysAreStrings(): void
    {
        $aField = Fields\Select::make('a')->options([1 => 'One', 2 => 'Two'])->toArray();

        self::assertSame(['1' => 'One', '2' => 'Two'], $aField['options']);
        self::assertSame('1', $aField['default']);
    }

    public function testSelectCarriesTheOrderItWasGiven(): void
    {
        $field = Fields\Select::make('a')->options(['' => 'Choose', 5 => 'Five', 2 => 'Two'])->toArray();

        self::assertSame(['', '5', '2'], $field['order']);
    }

    public function testTableDefaultsToItsSizeAndFillsRowsToTheWidest(): void
    {
        $field = Fields\Table::make('rows')->size(2, 3);

        self::assertSame('table', $field->toArray()['type']);
        self::assertSame([['', '', ''], ['', '', '']], $field->getDefault());
        self::assertSame([['a', 'b'], ['1', '']], $field->fill([['a', 'b'], [1]]));
        self::assertSame('not rows', $field->fill('not rows'));
    }

    public function testRejectsAnEmptyTableSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldList::validate([Fields\Table::make('rows')->size(0, 2)]);
    }
}
