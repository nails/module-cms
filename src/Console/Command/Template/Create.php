<?php

namespace Nails\Cms\Console\Command\Template;

use Nails\Cms\Exception\Console\TemplateExistsException;
use Nails\Console\Command\BaseMaker;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends BaseMaker
{
    const RESOURCE_PATH = NAILS_PATH . 'module-cms/resources/console/template/';
    const TEMPLATE_PATH = NAILS_APP_PATH . 'application/modules/cms/templates/';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('make:cms:template')
            ->setDescription('Creates a new CMS template');

        $this->aArguments = [
            [
                'name'        => 'name',
                'mode'        => InputArgument::OPTIONAL,
                'description' => 'Define the name of the template to create',
                'required'    => true,
            ],
            [
                'name'        => 'description',
                'mode'        => InputArgument::OPTIONAL,
                'description' => 'The template\'s description',
                'required'    => false,
            ],
        ];

        parent::configure();
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface  $oInput  The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        // --------------------------------------------------------------------------

        try {
            //  Ensure the paths exist
            $this->createPath(self::TEMPLATE_PATH);
            //  Create the controller
            $this->createTemplate();
        } catch (\Exception $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

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
     * @throws \Exception
     */
    private function createTemplate(): void
    {
        $aFields         = $this->getArguments();
        $aFields['SLUG'] = $this->generateSlug($aFields['NAME']);
        $sPath           = self::TEMPLATE_PATH . $aFields['SLUG'] . '/';

        try {

            if (is_dir($sPath)) {
                throw new TemplateExistsException('Template "' . $aFields['SLUG'] . '" exists already');
            }

            $this->createPath($sPath);

            //  Create the files
            $aFiles = [
                'template.php',
                'view.php',
            ];

            foreach ($aFiles as $sFile) {
                $this->createFile($sPath . $sFile, $this->getResource($sFile, $aFields));
            }

        } catch (TemplateExistsException $e) {
            //  Do not clean up (delete existing template)!
            throw new \Exception($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            //  Clean up
            if (!empty($aFiles)) {
                foreach ($aFiles as $sFile) {
                    @unlink($sPath . $sFile);
                }
            }
            rmdir($sPath);

            throw new \Exception(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a class name safe slug
     *
     * @param  string $sString The input string
     *
     * @return string
     */
    private function generateSlug($sString): string
    {
        Factory::helper('url');

        $aSlug = explode('-', url_title($sString, '-', false));
        $aSlug = array_map('ucfirst', $aSlug);

        return implode($aSlug, '');
    }
}
