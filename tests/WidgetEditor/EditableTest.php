<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Cms\WidgetEditor\Widget\Base;
use PHPUnit\Framework\TestCase;

/*
 * Fixtures. Declared here so they autoload without a test autoload entry.
 */

class EditableTitle extends Base
{
    const ALIASES = [];

    public function render(array $data): string
    {
        return $this->editable('sTitle', 'h2', $data['sTitle'] ?? '', ['class' => 'title'])
            . $this->editable('body', 'div', $data['body'] ?? '', [], 'rich');
    }
}

class EditableStack extends Base
{
    const ALIASES = [];

    public function getSlots(): array
    {
        return ['items' => ['label' => 'Items', 'max' => null]];
    }

    public function render(array $data): string
    {
        return '<section>' . $this->renderSlot('items', $data) . '</section>';
    }
}

// --------------------------------------------------------------------------

class EditableTest extends TestCase
{
    private const TITLE = 'Tests.WidgetEditor.EditableTitle';
    private const STACK = 'Tests.WidgetEditor.EditableStack';

    private function registry(): Registry
    {
        return new Registry([EditableTitle::class => 1, EditableStack::class => 1]);
    }

    public function testRenderModeHasNoEditorAttributes(): void
    {
        $sHtml = $this->registry()->renderNode(
            ['slug' => self::TITLE, 'data' => ['sTitle' => 'Hello', 'body' => '<p>Body</p>']],
            Registry::MODE_RENDER
        );

        self::assertSame('<h2 class="title">Hello</h2><div><p>Body</p></div>', $sHtml);
    }

    public function testRichValueIsNotEscapedInRenderMode(): void
    {
        $sHtml = $this->registry()->renderNode(
            ['slug' => self::TITLE, 'data' => ['body' => '<p><strong>Bold</strong></p>']],
            Registry::MODE_RENDER
        );

        self::assertStringContainsString('<div><p><strong>Bold</strong></p></div>', $sHtml);
    }

    public function testPreviewMarksTopLevelFields(): void
    {
        $sHtml = $this->registry()->renderNode(
            ['slug' => self::TITLE, 'data' => ['sTitle' => 'Hi <b>', 'body' => '<p>x</p>']],
            Registry::MODE_PREVIEW
        );

        self::assertStringContainsString(
            '<h2 class="title" data-cms-field="sTitle" data-cms-edit="text" data-cms-path="">Hi &lt;b&gt;</h2>',
            $sHtml
        );
        self::assertStringContainsString(
            '<div data-cms-field="body" data-cms-edit="rich" data-cms-path=""><p>x</p></div>',
            $sHtml
        );
    }

    public function testPreviewMarksNestedFieldsWithTheirPath(): void
    {
        $sHtml = $this->registry()->renderNode(
            [
                'slug' => self::STACK,
                'data' => [
                    'items' => [
                        ['slug' => self::TITLE, 'data' => ['sTitle' => 'First']],
                        ['slug' => self::TITLE, 'data' => ['sTitle' => 'Second']],
                    ],
                ],
            ],
            Registry::MODE_PREVIEW
        );

        self::assertStringContainsString('data-cms-path="items/0"', $sHtml);
        self::assertStringContainsString('data-cms-path="items/1"', $sHtml);
        self::assertStringContainsString('>Second</h2>', $sHtml);
    }

    public function testUnknownEditTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $oWidget = new class() extends Base {
            public function render(array $data): string
            {
                return $this->editable('x', 'p', 'y', [], 'table');
            }
        };

        $oWidget->render([]);
    }
}
