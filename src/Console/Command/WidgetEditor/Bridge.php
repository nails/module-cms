<?php

/**
 * Generates WidgetEditor (v2) bridge classes for existing v1 widgets (D13)
 *
 * One bridge per legacy widget, written to src/Cms/Widgets/. Existing files are never overwritten, so a bridge
 * that has been edited by hand is safe to re-run.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Console Command
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Console\Command\WidgetEditor;

use Nails\Cms\Constants;
use Nails\Console\Command\Base;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Bridge extends Base
{
    /** Where bridges are written, relative to the app root */
    const BRIDGE_PATH = 'src/Cms/Widgets/';

    /** The namespace of the bridges, which follows the app's PSR-4 root */
    const BRIDGE_NAMESPACE = 'App\\Cms\\Widgets';

    // --------------------------------------------------------------------------

    protected function configure(): void
    {
        $this
            ->setName('cms:widgets:bridge')
            ->setDescription('Generates WidgetEditor (v2) bridge classes for existing v1 widgets')
            ->addOption('slug', null, InputOption::VALUE_REQUIRED, 'Only bridge the v1 widget with this slug')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List what would be written, without writing');
    }

    // --------------------------------------------------------------------------

    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $sOnly   = (string) $oInput->getOption('slug');
        $bDryRun = (bool) $oInput->getOption('dry-run');
        $sDir    = NAILS_APP_PATH . self::BRIDGE_PATH;

        /** @var \Nails\Cms\Service\Widget $oWidgetService */
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        $oRegistry      = $oWidgetService->getRegistry();

        $aLines = [];
        $iWrote = 0;

        foreach ($oWidgetService->getAvailable(true) as $oGroup) {
            foreach ($oGroup->getWidgets() as $oWidget) {
                $sSlug  = $oWidget->getSlug();
                $sClass = get_class($oWidget);

                if ($sOnly !== '' && $sSlug !== $sOnly) {
                    continue;
                }

                if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $sSlug)) {
                    $aLines[] = '<comment>skip</comment>   ' . $sSlug . ' (not a valid class name)';
                    continue;
                }

                $sFile = $sDir . $sSlug . '.php';
                if (is_file($sFile)) {
                    $aLines[] = '<comment>exists</comment> ' . $sSlug . ' (' . self::BRIDGE_PATH . $sSlug . '.php)';
                    continue;
                }

                // A v2 widget already claims this slug (a module ships it). A module's own v1 widget needs no
                // bridge. An app's v1 override of it is bridged by overriding the v2 widget, rather than claiming
                // the same alias, which would fail the whole catalogue
                $sParent = null;
                if ($oRegistry->knows($sSlug)) {
                    $oClaimed = $oRegistry->resolve($sSlug);
                    $sParent  = $oClaimed !== null ? get_class($oClaimed) : null;

                    if (!self::isAppClass($sClass)) {
                        $aLines[] = '<comment>native</comment> ' . $sSlug . ($sParent !== null ? ' (' . $sParent . ')' : '');
                        continue;
                    }
                }

                $sNote = $sParent !== null ? ' (overrides ' . $sParent . ')' : '';

                if ($bDryRun) {
                    $aLines[] = '<info>would write</info> ' . $sSlug . ' -> ' . $sClass . $sNote;
                    continue;
                }

                if (!is_dir($sDir) && !mkdir($sDir, 0775, true) && !is_dir($sDir)) {
                    return $this->abort(self::EXIT_CODE_FAILURE, ['Could not create ' . $sDir]);
                }

                if (file_put_contents($sFile, $this->bridgeSource($sSlug, $sClass, $sParent)) === false) {
                    return $this->abort(self::EXIT_CODE_FAILURE, ['Could not write ' . $sFile]);
                }

                $iWrote++;
                $aLines[] = '<info>wrote</info>      ' . $sSlug . ' -> ' . $sClass . $sNote;
            }
        }

        if ($sOnly !== '' && empty($aLines)) {
            return $this->abort(self::EXIT_CODE_FAILURE, ['No v1 widget has the slug "' . $sOnly . '"']);
        }

        foreach ($aLines as $sLine) {
            $oOutput->writeln($sLine);
        }

        $oOutput->writeln('');
        $oOutput->writeln($bDryRun
            ? 'Dry run: nothing was written.'
            : 'Wrote ' . $iWrote . ' bridge' . ($iWrote === 1 ? '' : 's') . '. Review the new classes before you use them.');

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether a v1 widget class belongs to the app, rather than to a module
     */
    private static function isAppClass(string $sClass): bool
    {
        return str_starts_with(ltrim($sClass, '\\'), 'App\\');
    }

    // --------------------------------------------------------------------------

    /**
     * The source of a bridge class. With a parent, the bridge overrides that v2 widget (#[Overrides]) so it claims
     * its slug; the v1 class it delegates to renders it and decides the editor, scripts and status.
     */
    private function bridgeSource(string $sSlug, string $sLegacyClass, ?string $sParent = null): string
    {
        $sNamespace = self::BRIDGE_NAMESPACE;
        $sLegacy    = '\\' . ltrim($sLegacyClass, '\\');

        if ($sParent === null) {
            $sImports = 'use Nails\Cms\WidgetEditor\Traits\Legacy;' . PHP_EOL
                . 'use Nails\Cms\WidgetEditor\Widget\Base;';
            $sDeclare = 'final class ' . $sSlug . ' extends Base';
        } else {
            $sImports = 'use Nails\Cms\WidgetEditor\Attribute\Overrides;' . PHP_EOL
                . 'use Nails\Cms\WidgetEditor\Traits\Legacy;';
            $sDeclare = '#[Overrides]' . PHP_EOL . 'class ' . $sSlug . ' extends \\' . ltrim($sParent, '\\');
        }

        return <<<PHP
<?php

/**
 * Bridge: the "{$sSlug}" v1 widget, as a WidgetEditor (v2) widget
 *
 * Generated by cms:widgets:bridge. It delegates to {$sLegacy}, so the v1 render, editor and scripts are used.
 * Stored content keeps the slug "{$sSlug}" through an alias.
 */

namespace {$sNamespace};

{$sImports}

{$sDeclare}
{
    use Legacy;

    protected static function legacyClass(): string
    {
        return {$sLegacy}::class;
    }
}

PHP;
    }
}
