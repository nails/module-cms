<?php

/**
 * Migration:   4
 * Started:     06/11/2015
 * Finalised:   06/11/2015
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Database\Migration;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration4
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration4 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD `published_template_options` TEXT NULL AFTER `published_template_data`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD `draft_template_options` TEXT NULL AFTER `draft_template_data`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD `published_template_options` TEXT NULL AFTER `published_template_data`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD `draft_template_options` TEXT NULL AFTER `draft_template_data`;");

        // --------------------------------------------------------------------------

        //  Migrate page content
        $aUpdate = array();
        $oResult = $this->query('SELECT * FROM {{NAILS_DB_PREFIX}}cms_page');

        while ($oRow = $oResult->fetch(\PDO::FETCH_OBJ)) {

            $aUpdate = array(
                'draft'     => $this->convertTplData($oRow->draft_template, $oRow->draft_template_data),
                'published' => $this->convertTplData($oRow->published_template, $oRow->published_template_data)
            );

            /**
             * Work out the new hashes.
             * These are all the editable fields, in a certain order json_encoded'ed and md5'd
             */
            $sDraftHash     = $this->calculateHash($oRow, $aUpdate, 'DRAFT');
            $sPublishedHash = $this->calculateHash($oRow, $aUpdate, 'PUBLISHED');

            //  Update the record
            $sQuery = '
                UPDATE `{{NAILS_DB_PREFIX}}cms_page`
                SET
                    `draft_hash` = :draft_hash,
                    `draft_template_data` = :draft_template_data,
                    `draft_template_options` = :draft_template_options,
                    `published_hash` = :published_hash,
                    `published_template_data` = :published_template_data,
                    `published_template_options` = :published_template_options
                WHERE
                    `id` = :id
            ';

            $oSth = $this->prepare($sQuery);

            if (is_null($aUpdate['draft']['template_data'])) {
                $sDraftTplData = null;
            } else {
                $sDraftTplData = json_encode($aUpdate['draft']['template_data']);
            }

            if (is_null($aUpdate['draft']['template_options'])) {
                $sDraftTplOptions = null;
            } else {
                $sDraftTplOptions = json_encode($aUpdate['draft']['template_options']);
            }

            if (is_null($aUpdate['published']['template_data'])) {
                $sPublishedTplData = null;
            } else {
                $sPublishedTplData = json_encode($aUpdate['published']['template_data']);
            }

            if (is_null($aUpdate['published']['template_options'])) {
                $sPublishedTplOptions = null;
            } else {
                $sPublishedTplOptions = json_encode($aUpdate['published']['template_options']);
            }

            $oSth->bindParam(':draft_hash', $sDraftHash, \PDO::PARAM_STR);
            $oSth->bindParam(':draft_template_data', $sDraftTplData, \PDO::PARAM_STR);
            $oSth->bindParam(':draft_template_options', $sDraftTplOptions, \PDO::PARAM_STR);
            $oSth->bindParam(':published_hash', $sPublishedHash, \PDO::PARAM_STR);
            $oSth->bindParam(':published_template_data', $sPublishedTplData, \PDO::PARAM_STR);
            $oSth->bindParam(':published_template_options', $sPublishedTplOptions, \PDO::PARAM_STR);
            $oSth->bindParam(':id', $oRow->id, \PDO::PARAM_INT);

            $oSth->execute();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Converts old template data into the new format
     * @param  String $sTemplate The template to extract
     * @param  String $sData     The data as a JSON String
     * @return array
     */
    private function convertTplData($sTemplate, $sData)
    {
        $oData = json_decode($sData);
        $aOut  = array(
            'template_data'    => '',
            'template_options' => ''
        );

        //  Widget Data
        if (!empty($oData->widget_areas->{$sTemplate})) {
            $aOut['template_data'] = $oData->widget_areas->{$sTemplate};
            //  Go through each widget and format into the correct format
            foreach ($aOut['template_data'] as $sArea => $aWidgets) {

                foreach ($aWidgets as &$oWidget) {
                    $oWidget->slug = $oWidget->widget;
                    unset($oWidget->widget);

                    $sTempData = $oWidget->data;
                    unset($oWidget->data);
                    parse_str($sTempData, $aData);
                    $oWidget->data = (object) $aData;
                }
            }

        } else {
            $aOut['template_data'] = null;
        }

        //  Template Options
        if (!empty($oData->data->additional_fields->{$sTemplate})) {

            $aOut['template_options'] = $oData->data->additional_fields->{$sTemplate};
            unset($aOut['template_options']->manual_config);
            $aTest = (array) $aOut['template_options'];

            //  If empty then set to null to be consistent with below
            if (empty($aTest)) {

                $aOut['template_options'] = null;
            }
        } else {
            $aOut['template_options'] = null;
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Calclates the hash for the appropriate piece of data
     * @param  Object $oRow     The row
     * @param  Array  $aNewData The newly calculated data
     * @param  String $sType    The type of hash we're generating [DRAFT|PUBLISH]
     * @return string
     */
    private function calculateHash($oRow, $aNewData, $sType)
    {
        $sPrefix = $sType == 'DRAFT' ? 'draft' : 'published';

        //  Prepare the array
        //  The keys need to be prefixed with `draft_` as that is what the hash is generated on in the model
        $aHashArray = array(
            'draft_parent_id' => $oRow->{$sPrefix . '_parent_id'},
            'draft_title' => $oRow->{$sPrefix . '_title'},
            'draft_seo_title' => $oRow->{$sPrefix . '_seo_title'},
            'draft_seo_description' => $oRow->{$sPrefix . '_seo_description'},
            'draft_seo_keywords' => $oRow->{$sPrefix . '_seo_keywords'},
            'draft_template' => $oRow->{$sPrefix . '_template'},
            'draft_template_data' => json_encode($aNewData[$sPrefix]['template_data']),
            'draft_template_options' => json_encode($aNewData[$sPrefix]['template_options']),
            'draft_slug' => $oRow->{$sPrefix . '_slug'},
            'draft_slug_end' => $oRow->{$sPrefix . '_slug_end'},
            'draft_breadcrumbs' => $oRow->{$sPrefix . '_breadcrumbs'},
        );

        return md5(json_encode($aHashArray));
    }
}
