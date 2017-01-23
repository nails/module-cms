<?php

namespace Nails\Cms\Console\Command\Template;

use Nails\Console\Command\Base;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Base
{
    const TPL_PATH = FCPATH . APPPATH . 'modules/cms/templates/';
    const TPL_PATH_PERMISSION = 0755;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('cms:template');
        $this->setDescription('Creates a new CMS template');
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
        $oOutput->writeln('<info>-----------------------</info>');
        $oOutput->writeln('<info>Nails CMS Template Tool</info>');
        $oOutput->writeln('<info>-----------------------</info>');

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
                        'Template directory does not exist and could not be created',
                        self::TPL_PATH,
                    ]
                );
            }
        } elseif (!is_writable(self::TPL_PATH)) {
            return $this->abort(
                $oOutput,
                self::EXIT_CODE_FAILURE,
                [
                    'Template directory exists but is not writeable',
                    self::TPL_PATH,
                ]
            );
        }

        // --------------------------------------------------------------------------

        //  Get field names
        $aFields = [
            'name' => '',
            'description' => '',
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
        $oOutput->write('Creating template files... ');
        try {
            $this->createTemplate($aFields, $oOutput);
        } catch (\Exception $e) {
            return $this->abort(
                $oOutput,
                self::EXIT_CODE_FAILURE,
                [
                    'Error creating template',
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
     * Create the template
     *
     * @param array $aFields The details to create the template with
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     * @throws \Exception
     * @return int
     */
    private function createTemplate($aFields, $oOutput)
    {
        //  Test if template already exists
        $aFields['slug'] = $this->generateSlug($aFields['name']);
        $sPath = self::TPL_PATH . $aFields['slug'] . '/';

        try {

            if (is_dir($sPath)) {
                throw new \Exception('Template "' . $aFields['slug'] . '" exists already');
            }

            //  Make the directory
            if (!mkdir($sPath, self::TPL_PATH_PERMISSION)) {
                throw new \Exception('Failed to create template directory');
            }

            //  Create the files
            $aFiles = [
                'template.php',
                'view.php',
            ];

            foreach ($aFiles as $sFile) {
                $hHandle = fopen($sPath . $sFile, 'w');

                if (!$hHandle) {
                    throw new \Exception('Failed to open ' . $sFile . ' for writing');
                }

                if (fwrite($hHandle, $this->getResource($sFile, $aFields)) === false) {
                    throw new \Exception('Failed to write to ' . $sFile);
                }

                fclose($hHandle);
            }

        } catch (\Exception $e) {

            //  Clean up
            if (!empty($aFiles)) {
                foreach ($aFiles as $sFile) {
                    @unlink($sPath . $sFile);
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
     * @param array $aFields The template fields
     * @return string
     */
    private function getResource($sFile, $aFields)
    {
        $sResource = require NAILS_PATH . 'module-cms/resources/console/template/' . $sFile;

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
