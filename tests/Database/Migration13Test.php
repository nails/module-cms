<?php

namespace Tests\Database;

use Nails\Cms\Database\Migration\Migration13;
use PHPUnit\Framework\TestCase;

/**
 * Migration13 run against SQLite copies of the page and area tables
 */
class Migration13Test extends TestCase
{
    private \PDO $pdo;

    // --------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE `nails_cms_page` (`id` INTEGER PRIMARY KEY, `draft_template_data` TEXT, `published_template_data` TEXT)');
        $this->pdo->exec('CREATE TABLE `nails_cms_area` (`id` INTEGER PRIMARY KEY, `widget_data` TEXT)');
    }

    // --------------------------------------------------------------------------

    private function migrate(): void
    {
        SqliteMigration13::on($this->pdo)->execute();
    }

    private function column(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    // --------------------------------------------------------------------------

    public function testV1TablesInPagesAndSlotsAreConverted(): void
    {
        $draft = json_encode([
            'mainbody' => [
                ['slug' => 'richtext', 'data' => ['body' => '<p>tblData is only text here</p>']],
                ['slug' => 'Stack', 'data' => ['items' => [
                    ['slug' => 'table', 'data' => ['tblData' => '[["a",null],["b",2]]', 'tblAttr' => 'id="x"']],
                ]]],
            ],
        ]);
        $published = json_encode(['mainbody' => [['slug' => 'Nails.Cms.Cms.Widgets.Table', 'data' => ['tblData' => '[["c"]]']]]]);
        $this->pdo->prepare('INSERT INTO `nails_cms_page` VALUES (1, ?, ?)')->execute([$draft, $published]);

        $this->migrate();

        $draft = json_decode($this->column('SELECT `draft_template_data` FROM `nails_cms_page`'), true);
        $this->assertSame('<p>tblData is only text here</p>', $draft['mainbody'][0]['data']['body']);
        $this->assertSame(
            ['rows' => [['a', ''], ['b', '2']], 'attributes' => 'id="x"', 'header_rows' => 0],
            $draft['mainbody'][1]['data']['items'][0]['data']
        );

        $published = json_decode($this->column('SELECT `published_template_data` FROM `nails_cms_page`'), true);
        $this->assertSame(['rows' => [['c']], 'header_rows' => 0], $published['mainbody'][0]['data']);
    }

    // --------------------------------------------------------------------------

    public function testOnlyTheTableIsChanged(): void
    {
        //  Empty objects stay objects, and text keeps its characters
        $stored = '[{"slug":"Other","data":{}},{"slug":"table","data":{"tblData":"[[\"café / 1.0\"]]"}}]';
        $this->pdo->prepare('INSERT INTO `nails_cms_area` VALUES (1, ?)')->execute([$stored]);

        $this->migrate();

        $this->assertSame(
            '[{"slug":"Other","data":{}},{"slug":"table","data":{"rows":[["café / 1.0"]],"header_rows":0}}]',
            $this->column('SELECT `widget_data` FROM `nails_cms_area`')
        );
    }

    // --------------------------------------------------------------------------

    public function testRowsItCannotReadAreLeftAsTheyAre(): void
    {
        $insert = $this->pdo->prepare('INSERT INTO `nails_cms_area` VALUES (?, ?)');
        $insert->execute([1, '{"tblData": broken']);
        $insert->execute([2, '[{"slug":"richtext","data":{"body":"no table"}}]']);
        $insert->execute([3, null]);
        $insert->execute([4, '[{"slug":"table","data":{"tblData":"not json"}}]']);

        $this->migrate();

        $rows = $this->pdo->query('SELECT `id`, `widget_data` FROM `nails_cms_area` ORDER BY `id`')->fetchAll(\PDO::FETCH_KEY_PAIR);
        $this->assertSame('{"tblData": broken', $rows[1]);
        $this->assertSame('[{"slug":"richtext","data":{"body":"no table"}}]', $rows[2]);
        $this->assertNull($rows[3]);

        //  A table whose own JSON cannot be read keeps it, next to an empty grid
        $this->assertSame(
            ['rows' => [], 'v1_tblData' => 'not json', 'header_rows' => 0],
            json_decode($rows[4], true)[0]['data']
        );
    }

    // --------------------------------------------------------------------------

    public function testASecondRunChangesNothing(): void
    {
        $this->pdo->prepare('INSERT INTO `nails_cms_area` VALUES (1, ?)')
            ->execute(['[{"slug":"table","data":{"tblData":"[[\"a\"]]","tblAttr":""}}]']);

        $this->migrate();
        $first = $this->column('SELECT `widget_data` FROM `nails_cms_area`');
        $this->migrate();

        $this->assertSame($first, $this->column('SELECT `widget_data` FROM `nails_cms_area`'));
    }
}

// --------------------------------------------------------------------------

/**
 * Migration13 with its queries sent to a PDO connection, in place of the Nails database service
 */
class SqliteMigration13 extends Migration13
{
    private \PDO $pdo;

    /** The migration interface fixes the constructor, so the connection is set on an instance made without it */
    public static function on(\PDO $pdo): static
    {
        $migration      = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
        $migration->pdo = $pdo;
        return $migration;
    }

    public function query(string $sQuery): \PDOStatement
    {
        return $this->pdo->query($this->replaceConstants($sQuery));
    }

    public function prepare(string $sQuery): \PDOStatement
    {
        return $this->pdo->prepare($this->replaceConstants($sQuery));
    }
}
