<?php

namespace Tests\WidgetEditor;

use Nails\Cms\Cms\Fields;
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

class EditableBasic extends Base
{
    const ALIASES = [];

    public function render(array $data): string
    {
        return $this->editable('caption', 'div', $data['caption'] ?? '', [], 'rich-basic');
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

    public function testPreviewMarksABasicRichField(): void
    {
        $sHtml = (new Registry([EditableBasic::class => 1]))->renderNode(
            ['slug' => 'Tests.WidgetEditor.EditableBasic', 'data' => ['caption' => '<p><strong>Hi</strong></p>']],
            Registry::MODE_PREVIEW
        );

        self::assertStringContainsString(
            '<div data-cms-field="caption" data-cms-edit="rich-basic" data-cms-path=""><p><strong>Hi</strong></p></div>',
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

    public function testTheDeclaredFieldDecidesTheEditMode(): void
    {
        $oWidget = new class() extends Base {
            public function getFields(): ?array
            {
                return [
                    Fields\Text::make('title'),
                    Fields\Richtext::make('caption')->basic(),
                ];
            }

            public function render(array $data): string
            {
                return $this->editable('title', 'h2', 'T') . $this->editable('caption', 'div', '<p>C</p>');
            }
        };
        $oWidget->attach(new Registry(), 'fixture', Registry::MODE_PREVIEW, 0, []);

        $sHtml = $oWidget->render([]);

        self::assertStringContainsString('data-cms-field="title" data-cms-edit="text"', $sHtml);
        self::assertStringContainsString('data-cms-field="caption" data-cms-edit="rich-basic"', $sHtml);
    }

    public function testAFieldEditedOnlyInTheInspectorCannotBeEditable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('edited in the inspector');

        $oWidget = new class() extends Base {
            public function getFields(): ?array
            {
                return [Fields\Textarea::make('intro')];
            }

            public function render(array $data): string
            {
                return $this->editable('intro', 'p', 'Intro');
            }
        };

        $oWidget->render([]);
    }

    public function testARepeaterRowUsesTheChildField(): void
    {
        $oWidget = new class() extends Base {
            public function getFields(): ?array
            {
                return [
                    Fields\Repeater::make('panels')->fields([
                        Fields\Text::make('title'),
                        Fields\Richtext::make('body'),
                    ]),
                ];
            }

            public function render(array $data): string
            {
                return $this->editable('panels/0/title', 'span', 'T')
                    . $this->editable('panels/0/body', 'div', '<p>B</p>');
            }
        };
        $oWidget->attach(new Registry(), 'fixture', Registry::MODE_PREVIEW, 0, []);

        $sHtml = $oWidget->render([]);

        self::assertStringContainsString('data-cms-field="panels/0/title" data-cms-edit="text"', $sHtml);
        self::assertStringContainsString('data-cms-field="panels/0/body" data-cms-edit="rich"', $sHtml);
    }

    public function testARepeaterPathThatIsNotARowThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not a repeater row');

        $oWidget = new class() extends Base {
            public function getFields(): ?array
            {
                return [
                    Fields\Repeater::make('panels')->fields([
                        Fields\Text::make('title'),
                    ]),
                ];
            }

            public function render(array $data): string
            {
                return $this->editable('panels/title', 'span', 'T');
            }
        };

        $oWidget->render([]);
    }

    public function testAnEditModeThatDisagreesWithTheFieldThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not "rich"');

        $oWidget = new class() extends Base {
            public function getFields(): ?array
            {
                return [Fields\Richtext::make('quote')->basic()];
            }

            public function render(array $data): string
            {
                return $this->editable('quote', 'div', '', [], 'rich');
            }
        };

        $oWidget->render([]);
    }
}
