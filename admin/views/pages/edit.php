<div class="group-cms pages edit">
    <fieldset>
        <legend>Page Data</legend>
        <?php

        //  Title
        $aField                = array();
        $aField['key']         = 'title';
        $aField['label']       = 'Title';
        $aField['default']     = isset($cmspage->draft->title) ? html_entity_decode($cmspage->draft->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
        $aField['placeholder'] = 'The title of the page';

        echo form_field($aField);

        // --------------------------------------------------------------------------

        //  Parent ID
        $aField                     = array();
        $aField['key']              = 'parent_id';
        $aField['label']            = 'Parent Page';
        $aField['placeholder']      = 'The Page\'s parent.';
        $aField['class']            = 'select2';
        $aField['default']          = isset($cmspage->draft->parent_id) ? $cmspage->draft->parent_id : '';
        $aField['disabled_options'] = isset($page_children) ? $page_children : array();

        /**
         * Remove this page from the available options; INFINITE LOOP
         */

        if (isset($cmspage)) {

            foreach ($pagesNestedFlat as $id => $label) {

                if ($id == $cmspage->id) {

                    $aField['disabled_options'][] = $id;
                    break;
                }
            }
        }

        if (count($pagesNestedFlat) && count($aField['disabled_options']) < count($pagesNestedFlat)) {

            $pagesNestedFlat = array('' => 'No Parent Page') + $pagesNestedFlat;

            // --------------------------------------------------------------------------

            if (count($aField['disabled_options'])) {

                $aField['info']  = '<strong>Some options have been disabled.</strong> ';
                $aField['info'] .= 'You cannot set the parent page to this page or any existing child of this page.';
            }

            echo form_field_dropdown($aField, $pagesNestedFlat);

        } else {

            echo form_hidden($aField['key'], '');
        }

        ?>
    </fieldset>
    <fieldset>
        <legend>Template</legend>
        <ul class="templates">
        <?php

        $numTemplateGroups = count($templates);
        foreach ($templates as $oTemplateGroup) {

            if ($numTemplateGroups > 1) {

                ?>
                <li class="template-group-label">
                    <?=$oTemplateGroup->getLabel()?>
                </li>
                <?php
            }

            foreach ($oTemplateGroup->getTemplates() as $oTemplate) {

                //  This template selected?
                $selected = $defaultTemplate == $oTemplate->getSlug() ? true : false;

                //  Define attributes
                $attr              = array();
                $attr['class']     = $selected ? 'template selected' : 'template';
                $attr['data-slug'] = $oTemplate->getSlug();

                //  Glue together
                $attrStr = '';
                foreach ($attr as $key => $value) {

                    $attrStr .= $key . '="' . $value . '" ';
                }

                ?>
                <li>
                    <label <?=trim($attrStr)?> rel="tipsy-top" title="<?=$oTemplate->getDescription()?>">
                        <?php

                        echo form_radio(
                            'template',
                            $oTemplate->getSlug(),
                            set_radio(
                                'template',
                                $oTemplate->getSlug(),
                                $selected
                            ),
                            'data-slug="' . $attr['data-slug'] . '"'
                        );

                        echo '<span class="icon">';
                        if (!empty($oTemplate->getIcon())) {

                            echo img($oTemplate->getIcon());
                        }
                        echo '</span>';

                        ?>
                        <span class="name">
                            <span class="checkmark fa fa-check-circle"></span>
                            <span>
                                <?=$oTemplate->getLabel()?>
                            </span>
                        </span>
                    </label>
                </li>
                <?php

            }
        }

        ?>
        </ul>
    </fieldset>
    <fieldset class="template-areas">
        <legend>Page Content</legend>
        <p id="template-areas-none" class="alert alert-info">
            This template has no editable areas
        </p>
        <?php

        foreach ($templates as $oTemplateGroup) {

            $aTemplates = $oTemplateGroup->getTemplates();
            foreach ($aTemplates as $oTemplate) {

                $aWidgetAreas = $oTemplate->getWidgetAreas();

                if (!empty($aWidgetAreas)) {

                    echo '<div class="btn-group template-area" id="template-area-' . $oTemplate->getSlug() . '" role="group">';

                    foreach ($aWidgetAreas as $sWidgetSlug => $oWidgetArea) {

                        $aAttr = array(
                            'class'     => 'btn btn-default launch-editor',
                            'data-area' => $sWidgetSlug
                        );

                        $sAttr = '';
                        foreach ($aAttr as $sKey => $sValue) {

                            $sAttr .= $sKey . '="' . $sValue . '" ';
                        }

                        echo '<button ' . trim($sAttr) . '>' . $oWidgetArea->getTitle() . '</button>';
                    }

                    echo '</div>';
                }
            }
        }

        ?>
        <input type="hidden" name="template_data" id="template-data" />
    </fieldset>
    <fieldset class="template-options">
        <legend>Template Options</legend>
        <p id="template-options-none" class="alert alert-info">
            This template has no additional options
        </p>
        <?php

        //  Any additional page data for the templates
        foreach ($templates as $oTemplateGroup) {

            $aTemplates = $oTemplateGroup->getTemplates();
            foreach ($aTemplates as $oTemplate) {

                $sTplSlug = $oTemplate->getSlug();
                $aTplAdditionalFields = $oTemplate->getAdditionalFields();
                $bIssetCmsPage = isset($cmspage);
                $bPropertyExists = $bIssetCmsPage && property_exists(
                    $cmspage->draft->template_data->data->additional_fields,
                    $sTplSlug
                );

                if ($bIssetCmsPage && $bPropertyExists) {

                    $additionalFields = $cmspage->draft->template_data->data->additional_fields->{$sTplSlug};

                } else {

                    $additionalFields = null;
                }

                //  Any other fields, if specified
                if (!empty($aTplAdditionalFields)) {

                    echo '<div id="additional-fields-' . $sTplSlug . '" class="additional-fields">';

                    foreach ($aTplAdditionalFields as $aField) {

                        //  Set the default key
                        $sFieldKey     = $aField->getKey();
                        $sFieldType    = $aField->getType();
                        $aFieldOptions = $aField->getOptions();
                        if (!empty($additionalFields) && property_exists($additionalFields, $sFieldKey)) {

                            $aField->setDefault($additionalFields->{$sFieldKey});
                        }

                        //  Override the field key
                        $aField->setKey('additional_field[' . $sTplSlug . '][' . $sFieldKey . ']');

                        switch ($sFieldType) {

                            case 'dropdown' :

                                echo form_field_dropdown($aField->toArray(), $aFieldOptions);
                                break;

                            default :

                                echo form_field($aField->toArray());
                                break;
                        }
                    }

                    echo '</div>';
                }
            }
        }

        ?>
    </fieldset>
    <fieldset>
        <legend>Search Engine Optimisation</legend>
        <?php

        //  SEO Title
        $aField                = array();
        $aField['key']         = 'seo_title';
        $aField['label']       = 'SEO Title';
        $aField['default']     = isset($cmspage->draft->seo_title) ? html_entity_decode($cmspage->draft->seo_title, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
        $aField['placeholder'] = 'The page\'s SEO title, keep this short and concise. If not set, this will fallback to the page title.';

        echo form_field($aField);

        // --------------------------------------------------------------------------

        //  SEO Description
        $aField                = array();
        $aField['key']         = 'seo_description';
        $aField['label']       = 'SEO Description';
        $aField['default']     = isset($cmspage->draft->seo_description) ? html_entity_decode($cmspage->draft->seo_description, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
        $aField['placeholder'] = 'The page\'s SEO description, keep this short and concise. Recommended to keep below 160 characters.';
        $aField['tip']         = 'This should be kept short (< 160 characters) and concise. It\'ll be shown in search result listings and search engines will use it to help determine the page\'s content.';

        echo form_field($aField);

        // --------------------------------------------------------------------------

        //  SEO Keywords
        $aField                = array();
        $aField['key']         = 'seo_keywords';
        $aField['label']       = 'SEO Keywords';
        $aField['default']     = isset($cmspage->draft->seo_keywords) ? html_entity_decode($cmspage->draft->seo_keywords, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
        $aField['placeholder'] = 'Comma separated keywords relating to the content of the page. A maximum of 10 keywords is recommended.';
        $aField['tip']         = 'SEO good practice recommend keeping the number of keyword phrases below 10 and less than 160 characters in total.';

        echo form_field($aField);

        ?>
    </fieldset>
    <?php

    $bIssetCmsPage = isset($cmspage);
    $bHashMatch    = $bIssetCmsPage && $cmspage->published->hash !== $cmspage->draft->hash;

    if ($bIssetCmsPage && $cmspage->is_published && $bHashMatch) {

        ?>
        <p class="system-alert message">
            <strong>You have unpublished changes.</strong><br />This version of the page is more
            recent  than the version currently published on site. When you\'re done make sure you
            click  "Publish Changes" below.
        </p>
        <?php
    }

    ?>
    <p class="actions">
        <button type="submit" name="save" class="btn btn-primary" rel="tipsy-top" title="Your changes will be saved so you can come back later, but won't be published on site.">
            <?=lang('action_save_changes')?>
        </button>
        <button type="submit" name="publish" id="action-publish" class="btn btn-success" rel="tipsy-top" title="Your changes will be published on site and will take hold immediately.">
            <?=lang('action_publish_changes')?>
        </button>
        <a href="#" id="action-preview" class="btn btn-default right">
            <?=lang('action_preview')?>
        </a>
    </p>
</div>
<div id="page-preview" class="group-cms pages cms-page-preview">
    <div class="spinner">
        <b class="fa fa-circle-o-notch fa-spin"></b>
    </div>
    <iframe></iframe>
    <div class="actions">
        <div class="btn-group btn-group-justified">
            <div class="btn-group">
                <button class="btn btn-danger btn-sm action-close">
                    Close Preview
                </button>
            </div>
            <div class="btn-group">
                <button class="btn btn-success btn-sm action-publish">
                    <?=lang('action_publish_changes')?>
                </button>
            </div>
        </div>
    </div>
</div>