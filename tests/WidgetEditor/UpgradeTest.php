<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Service\Upgrade;
use PHPUnit\Framework\TestCase;

/**
 * Covers the data upgrade behind the UpgradeWidgetData migration trait
 */
class UpgradeTest extends TestCase
{
    private function upgraders(): array
    {
        return [
            'Heading' => function (array $data): array {
                if (array_key_exists('sTitle', $data)) {
                    $data['title'] = $data['sTitle'];
                    unset($data['sTitle']);
                }
                return $data;
            },
        ];
    }

    // --------------------------------------------------------------------------

    public function testMatchingNodesAreUpgradedAtAnyDepth(): void
    {
        $result = Upgrade::rewrite([
            ['slug' => 'Heading', 'data' => ['sTitle' => 'Top']],
            ['slug' => 'Stack', 'data' => ['items' => [['slug' => 'Heading', 'data' => ['sTitle' => 'Nested']]]]],
            ['slug' => 'Other', 'data' => ['sTitle' => 'Left alone']],
        ], $this->upgraders());

        $this->assertSame(2, $result['nodes']);
        $this->assertSame(['title' => 'Top'], $result['value'][0]['data']);
        $this->assertSame(['title' => 'Nested'], $result['value'][1]['data']['items'][0]['data']);
        $this->assertSame(['sTitle' => 'Left alone'], $result['value'][2]['data']);
    }

    // --------------------------------------------------------------------------

    public function testObjectsStayObjects(): void
    {
        $tree   = json_decode('{"main":[{"slug":"Heading","data":{"sTitle":"Hi"}},{"slug":"Other","data":{}}],"meta":{}}');
        $result = Upgrade::rewrite($tree, $this->upgraders());

        $this->assertSame(1, $result['nodes']);
        $this->assertSame(
            '{"main":[{"slug":"Heading","data":{"title":"Hi"}},{"slug":"Other","data":{}}],"meta":{}}',
            json_encode($result['value'])
        );
    }

    // --------------------------------------------------------------------------

    public function testDataInTheCurrentShapeIsNotCounted(): void
    {
        $tree  = [['slug' => 'Heading', 'data' => ['title' => 'Hi']]];
        $first = Upgrade::rewrite([['slug' => 'Heading', 'data' => ['sTitle' => 'Hi']]], $this->upgraders());

        $this->assertSame(['value' => $tree, 'nodes' => 0], Upgrade::rewrite($tree, $this->upgraders()));
        $this->assertSame(0, Upgrade::rewrite($first['value'], $this->upgraders())['nodes']);
    }

    // --------------------------------------------------------------------------

    public function testAValueThatIsNotANodeIsNeverUpgraded(): void
    {
        //  A "slug" with no "data" is not a widget, and data that is not an array or object is left alone
        $result = Upgrade::rewrite([
            ['slug' => 'Heading', 'sTitle' => 'No data'],
            ['slug' => 'Heading', 'data' => 'text'],
        ], $this->upgraders());

        $this->assertSame(0, $result['nodes']);
        $this->assertSame(['value' => null, 'nodes' => 0], Upgrade::rewrite(null, $this->upgraders()));
    }
}
