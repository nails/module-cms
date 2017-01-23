<?php

namespace Nails\Cms\Console\Command\Widget;

use Nails\Console\Command\Base;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Base
{
    const TPL_PATH = FCPATH . APPPATH . 'modules/cms/widgets/';
    const TPL_PATH_PERMISSION = 0755;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('cms:widget');
        $this->setDescription('Creates a new CMS widget');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface $oInput The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $oOutput->writeln('');
        $oOutput->writeln('<info>---------------------</info>');
        $oOutput->writeln('<info>Nails CMS Widget Tool</info>');
        $oOutput->writeln('<info>---------------------</info>');

        // --------------------------------------------------------------------------

        //  Setup Factory - config files are required prior to set up
        Factory::setup();

        // --------------------------------------------------------------------------

        //  Check environment
        if (Environment::not('DEVELOPMENT')) {
            return $this->abort(
                $oOutput,
                self::EXIT_CODE_FAILURE,
                [
                    'This tool is only available on DEVELOPMENT environments',
                ]
            );
        }

        // --------------------------------------------------------------------------

        //  Check we can write where we need to write
        if (!is_dir(self::TPL_PATH)) {
            if (!mkdir(self::TPL_PATH, self::TPL_PATH_PERMISSION, true)) {
                return $this->abort(
                    $oOutput,
                    self::EXIT_CODE_FAILURE,
                    [
                        'Widget directory does not exist and could not be created',
                        self::TPL_PATH,
                    ]
                );
            }
        } elseif (!is_writable(self::TPL_PATH)) {
            return $this->abort(
                $oOutput,
                self::EXIT_CODE_FAILURE,
                [
                    'Widget directory exists but is not writeable',
                    self::TPL_PATH,
                ]
            );
        }

        // --------------------------------------------------------------------------

        //  Get field names
        $aFields = [
            'name' => '',
            'description' => '',
            'grouping' => '',
            'keywords' => '',
        ];
        foreach ($aFields as $sField => &$sValue) {
            if (empty($sValue)) {
                $sField = ucwords(strtolower(str_replace('_', ' ', $sField)));
                $sError = '';
                do {
                    $sValue = $this->ask($sError . $sField . ':', '', $oInput, $oOutput);
                    $sError = '<error>Please specify</error> ';
                } while (empty($sValue));
            }
        }
        unset($sValue);

        // --------------------------------------------------------------------------

        $oOutput->writeln('');
        $oOutput->write('Creating widget files... ');
        try {
            $this->createWidget($aFields, $oOutput);
        } catch (\Exception $e) {
            return $this->abort(
                $oOutput,
                self::EXIT_CODE_FAILURE,
                [
                    'Error creating widget',
                    $e->getMessage(),
                ]
            );
        }
        $oOutput->writeln('<comment>done!</comment>');

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up...</comment>');

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
     * @param array $aFields The details to create the widget with
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     * @throws \Exception
     * @return int
     */
    private function createWidget($aFields, $oOutput)
    {
        //  Test if widget already exists
        $aFields['slug'] = $this->generateSlug($aFields['name']);
        $sPath = self::TPL_PATH . $aFields['slug'] . '/';

        try {

            if (is_dir($sPath)) {
                throw new \Exception('Widget "' . $aFields['slug'] . '" exists already');
            }

            //  Make the directory
            if (!mkdir($sPath, self::TPL_PATH_PERMISSION)) {
                throw new \Exception('Failed to create widget directory');
            }

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
                        throw new \Exception('File was passed as an array but could not determine the file to read!');
                    } elseif (empty($sWriteFile)) {
                        throw new \Exception('File was passed as an array but could not determine the file to write!');
                    }

                } else {

                    $sReadFile  = $sFile;
                    $sWriteFile = $sFile;
                }

                $sDir = dirname($sPath . $sReadFile) . '/';

                if ($sPath !== $sDir && !is_dir($sDir)) {
                    if (!mkdir($sDir, self::TPL_PATH_PERMISSION, true)) {
                        throw new \Exception('Failed to create sub directory (' . $sDir . ') in widget');
                    }
                }

                $hHandle = fopen($sPath . $sWriteFile, 'w');

                if (!$hHandle) {
                    throw new \Exception('Failed to open ' . $sWriteFile . ' for writing');
                }

                if (fwrite($hHandle, $this->getResource($sReadFile, $aFields)) === false) {
                    throw new \Exception('Failed to write to ' . $sWriteFile);
                }

                fclose($hHandle);
            }

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

            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get a resource and substitute fields into it
     *
     * @param string $sFile The file to fetch
     * @param array $aFields The widget fields
     * @return string
     */
    private function getResource($sFile, $aFields)
    {
        $sResource = require NAILS_PATH . 'module-cms/resources/console/widget/' . $sFile;

        $aFields['slug_lc'] = strtolower($aFields['slug']);

        foreach ($aFields as $sField => $sValue) {
            $sKey = '{{' . strtoupper($sField) . '}}';
            $sResource = str_replace($sKey, $sValue, $sResource);
        }

        return $sResource;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a class name safe slug
     *
     * @param  string $sString The input string
     * @return string
     */
    private function generateSlug($sString)
    {
        Factory::helper('url');

        $aSlug = explode('-', url_title($sString, '-', true));
        $aSlug = array_map('ucfirst', $aSlug);

        return implode($aSlug, '');
    }
}
