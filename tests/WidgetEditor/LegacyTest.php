<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Status;
use Nails\Cms\WidgetEditor\Traits\Legacy;
use Nails\Cms\WidgetEditor\Widget\Base;
use PHPUnit\Framework\TestCase;

/*
 * Fixtures. A v1-shaped widget (no base class needed; the bridge only calls these methods), and a bridge to it.
 */

class LegacyStubWidget
{
    public static bool $disabled   = false;
    public static bool $deprecated = false;
    public static bool $hidden     = false;

    public function getLabel(): string
    {
        return 'Stub label';
    }

    public function getDescription(): string
    {
        return 'Stub description';
    }

    public function getIcon(): string
    {
        return 'fa-star';
    }

    public function getGrouping(): string
    {
        return 'Content & Cards';
    }

    public function getKeywords(): string
    {
        return 'One, Two,, Three ';
    }

    public function getScreenshot(): string
    {
        return '';
    }

    public function getEditor(array $aWidgetData = []): string
    {
        return '<input name="sTitle" value="' . htmlspecialchars((string) ($aWidgetData['sTitle'] ?? '')) . '">';
    }

    public function render(array $aWidgetData = []): string
    {
        return '<h2>' . htmlspecialchars((string) ($aWidgetData['sTitle'] ?? '')) . '</h2>';
    }

    public function getRenderAssets(): array
    {
        return ['https://example.test/render.css'];
    }

    public function getEditorAssets(): array
    {
        return ['https://example.test/editor.js'];
    }

    public function getCallbacks()
    {
        return (object) [
            'dropped' => 'domElement.find("input");',
            'removed' => '',
        ];
    }

    public static function isDisabled(): bool
    {
        return static::$disabled;
    }

    public static function isDeprecated(): bool
    {
        return static::$deprecated;
    }

    public static function isHidden(): bool
    {
        return static::$hidden;
    }

    public static function alternative(): string
    {
        return 'App.Cms.Widgets.Replacement';
    }
}

class LegacyStubBridge extends Base
{
    use Legacy;

    protected static function legacyClass(): string
    {
        return LegacyStubWidget::class;
    }
}

class LegacyStubNative extends Base
{
    public function getFields(): ?array
    {
        return [\Nails\Cms\Cms\Fields\Text::make('title')];
    }

    public function upgrade(array $data): array
    {
        return ['title' => $data['sTitle'] ?? ''];
    }

    public function preview(array $data, array $context = []): string
    {
        return 'native preview';
    }

    public function render(array $data): string
    {
        return 'native render';
    }
}

class LegacyStubOverride extends LegacyStubNative
{
    use Legacy;

    protected static function legacyClass(): string
    {
        return LegacyStubWidget::class;
    }
}

/**
 * Covers the v1 bridge: the mapping of v1 metadata, status and aliases, and delegation of render, editor and scripts
 */
class LegacyTest extends TestCase
{
    protected function setUp(): void
    {
        LegacyStubWidget::$disabled   = false;
        LegacyStubWidget::$deprecated = false;
        LegacyStubWidget::$hidden     = false;
    }

    // --------------------------------------------------------------------------

    public function testStoredV1SlugIsAnAlias(): void
    {
        //  The v1 slug is the name of the folder the class lives in, which is this test folder
        $this->assertContains(basename(__DIR__), LegacyStubBridge::getAliases());
    }

    // --------------------------------------------------------------------------

    public function testMetadataIsMappedFromV1(): void
    {
        $oBridge = new LegacyStubBridge();

        $this->assertSame('Stub label', $oBridge->getLabel());
        $this->assertSame('Stub description', $oBridge->getDescription());
        $this->assertSame('Stub description', $oBridge->getSummary());
        $this->assertSame('fa-star', $oBridge->getIcon());
        $this->assertSame(['Content & Cards'], $oBridge->getGroups());
        $this->assertSame(['One', 'Two', 'Three'], $oBridge->getKeywords());
        $this->assertSame([], $oBridge->getScreenshots());
    }

    // --------------------------------------------------------------------------

    public function testStatusFollowsV1Flags(): void
    {
        $this->assertSame(Status::ACTIVE, LegacyStubBridge::getStatus());

        LegacyStubWidget::$hidden = true;
        $this->assertSame(Status::HIDDEN, LegacyStubBridge::getStatus());

        LegacyStubWidget::$deprecated = true;
        $this->assertSame(Status::DEPRECATED, LegacyStubBridge::getStatus());

        LegacyStubWidget::$disabled = true;
        $this->assertSame(Status::DISABLED, LegacyStubBridge::getStatus());
    }

    // --------------------------------------------------------------------------

    public function testAlternativeIsReadFromV1(): void
    {
        $this->assertSame('App.Cms.Widgets.Replacement', LegacyStubBridge::getAlternative());
    }

    // --------------------------------------------------------------------------

    public function testRenderIsDelegated(): void
    {
        $oBridge = new LegacyStubBridge();
        $this->assertSame('<h2>Hello</h2>', $oBridge->render(['sTitle' => 'Hello']));
    }

    // --------------------------------------------------------------------------

    public function testABridgeOverANativeWidgetIsV1Throughout(): void
    {
        $oBridge = new LegacyStubOverride();

        $this->assertNull($oBridge->getFields());
        $this->assertSame(['sTitle' => 'Hello'], $oBridge->upgrade(['sTitle' => 'Hello']));
        $this->assertSame(['sTitle' => 'Hello'], $oBridge->normalise(['sTitle' => 'Hello']));
        $this->assertSame('<h2>Hello</h2>', $oBridge->render(['sTitle' => 'Hello']));
        $this->assertSame('<h2>Hello</h2>', $oBridge->preview(['sTitle' => 'Hello']));
    }

    // --------------------------------------------------------------------------

    public function testEditorFormIsTheV1EditorAndUsesTheData(): void
    {
        $oBridge = new LegacyStubBridge();
        $this->assertSame(
            '<input name="sTitle" value="Hi">',
            $oBridge->getEditorForm(['sTitle' => 'Hi'])
        );
    }

    // --------------------------------------------------------------------------

    public function testOnlyEditorAssetsAreReturned(): void
    {
        $oBridge = new LegacyStubBridge();
        $this->assertSame(
            ['render' => [], 'editor' => ['https://example.test/editor.js']],
            $oBridge->getAssets()
        );
    }

    // --------------------------------------------------------------------------

    public function testCallbacksAreReturnedAsSource(): void
    {
        $oBridge = new LegacyStubBridge();
        $this->assertSame(
            ['dropped' => 'domElement.find("input");', 'removed' => ''],
            $oBridge->getCallbacks()
        );
    }

    // --------------------------------------------------------------------------

    public function testAWidgetWithoutScriptsHasNoCallbacks(): void
    {
        $oWidget = new class extends Base {
            public function render(array $data): string
            {
                return '';
            }
        };
        $this->assertSame([], $oWidget->getCallbacks());
    }
}
