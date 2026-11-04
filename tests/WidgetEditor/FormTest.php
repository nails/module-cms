<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Helper\Form;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testRulesAttributeIsEmptyWhenTheAreaHasNoRules(): void
    {
        self::assertSame('', Form::rulesAttribute([]));
        self::assertSame('', Form::rulesAttribute(['allowed' => 'Heading']));
    }

    public function testRulesAttributeEncodesListsAndDropsNonStrings(): void
    {
        $attribute = Form::rulesAttribute([
            'allowed'    => ['Heading', 3, 'group:Text'],
            'disallowed' => ['Note'],
        ]);

        self::assertStringStartsWith(' data-cms-rules="', $attribute);
        self::assertStringContainsString(
            htmlspecialchars('{"allowed":["Heading","group:Text"],"disallowed":["Note"]}', ENT_QUOTES, 'UTF-8'),
            $attribute
        );
    }
}
