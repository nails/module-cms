<?php

namespace Nails\Cms\Console\Command\Widget;

use Nails\Cms\Exception\Console\WidgetExistsException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\ValidationException;
use Nails\Console\Command\BaseMaker;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Create
 *
 * @package Nails\Cms\Console\Command\Widget
 */
class Create extends BaseMaker
{
    const RESOURCE_PATH = NAILS_PATH . 'module-cms/resources/console/widget/';
    const WIDGET_PATH   = NAILS_APP_PATH . 'application/modules/cms/widgets/';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('make:cms:widget')
            ->setDescription('Creates a new CMS widget');

        $this->aArguments = [
            [
                'name'        => 'name',
                'mode'        => InputArgument::OPTIONAL,
                'description' => 'Define the name of the widget to create',
                'required'    => true,
                'validation'  => function (string $sValue) {
                    $this->validateClassName($sValue);
                },
            ],
            [
                'name'        => 'description',
                'mode'        => InputArgument::OPTIONAL,
                'description' => 'The widget\'s description',
                'required'    => false,
            ],
            [
                'name'        => 'grouping',
                'mode'        => InputArgument::OPTIONAL,
                'description' => 'Define the sidebar grouping of the widget',
                'required'    => false,
            ],
            [
                'name'        => 'keywords',
                'mode'        => InputArgument::OPTIONAL,
                'description' => 'Define the searchable keywords of the widget',
                'required'    => false,
            ],
        ];

        parent::configure();
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        // --------------------------------------------------------------------------

        try {
            //  Ensure the paths exist
            $this->createPath(self::WIDGET_PATH);
            //  Create the controller
            $this->createWidget();
        } catch (\Exception $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up</comment>...');

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Create the widget
     *
     * @throws \Exception
     */
    private function createWidget(): void
    {
        $aFields            = $this->getArguments();
        $aFields['SLUG']    = $this->generateSlug($aFields['NAME']);
        $aFields['SLUG_LC'] = strtolower($aFields['SLUG']);
        $sPath              = self::WIDGET_PATH . $aFields['SLUG'] . '/';

        try {

            if (is_dir($sPath)) {
                throw new WidgetExistsException('Widget "' . $aFields['SLUG'] . '" exists already');
            }

            $this->createPath($sPath);

            //  Create the files
            $aFiles = [
                'widget.php',
                'views/render.php',
                'views/editor.php',
                ['js/dropped.php', 'js/dropped.js'],
            ];

            foreach ($aFiles as $sFile) {

                if (is_array($sFile)) {

                    $sReadFile  = !empty($sFile[0]) ? $sFile[0] : '';
                    $sWriteFile = !empty($sFile[1]) ? $sFile[1] : '';

                    if (empty($sReadFile)) {
                        throw new NailsException(
                            'File was passed as an array but could not determine the file to read!'
                        );
                    } elseif (empty($sWriteFile)) {
                        throw new NailsException(
                            'File was passed as an array but could not determine the file to write!'
                        );
                    }

                } else {

                    $sReadFile  = $sFile;
                    $sWriteFile = $sFile;
                }

                $this->createPath(dirname($sPath . $sWriteFile));
                $this->createFile($sPath . $sWriteFile, $this->getResource($sReadFile, $aFields));
            }

        } catch (WidgetExistsException $e) {
            //  Do not clean up (delete existing widget)!
            throw $e;
        } catch (\Exception $e) {
            //  Clean up
            if (!empty($aFiles)) {
                foreach ($aFiles as $sFile) {
                    if (is_array($sFile)) {
                        $sWriteFile = !empty($sFile[1]) ? $sFile[1] : '';
                        @unlink($sPath . $sWriteFile);
                    } else {
                        @unlink($sPath . $sFile);
                    }
                }
            }
            rmdir($sPath);

            throw $e;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a class name safe slug
     *
     * @param string $sString The input string
     *
     * @return string
     */
    private function generateSlug($sString): string
    {
        Factory::helper('url');

        $aSlug = explode('-', url_title($sString, '-', false));
        $aSlug = array_map('ucfirst', $aSlug);

        return implode('', $aSlug);
    }
}
