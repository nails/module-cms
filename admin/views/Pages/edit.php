<?php
$oInput = \Nails\Factory::service('Input');
?>
<div class="group-cms pages edit">
    <?php

    echo form_open(null, 'id="main-form"');

    $bIssetCmsPage = isset($cmspage);
    $bHashMismatch = $bIssetCmsPage && $cmspage->published->hash !== $cmspage->draft->hash;

    if ($bIssetCmsPage && $cmspage->is_published && $bHashMismatch) {
        ?>
        <p class="alert alert-warning">
            <strong>You have unpublished changes.</strong>
            <br />This version of the page is more recent than the version currently published on site. When
            you're done make sure you click "Publish Changes" below.
        </p>
        <?php
    }

    ?>
    <fieldset>
        <legend>Page Data</legend>
        <?php

        echo form_field([
            'key'         => 'title',
            'label'       => 'Title',
            'default'     => html_entity_decode($cmspage->draft->title ?? '', ENT_COMPAT | ENT_HTML5, 'UTF-8'),
            'placeholder' => 'The title of the page',
        ]);

        echo form_field([
            'key'         => 'slug',
            'label'       => 'Slug',
            'default'     => $cmspage->draft->slug_end ?? '',
            'placeholder' => 'The page\'s slug, leave blank to auto-generate',
        ]);

        $aField = [
            'key'              => 'parent_id',
            'label'            => 'Parent Page',
            'placeholder'      => 'The Page\'s parent.',
            'class'            => 'select2',
            'default'          => $cmspage->draft->parent_id ?? null,
            'disabled_options' => $page_children ?? [],
        ];

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
            $pagesNestedFlat = ['' => 'No Parent Page'] + $pagesNestedFlat;
            echo form_field_dropdown($aField, $pagesNestedFlat);
        } else {
            echo form_hidden($aField['key'], '');
        }

        ?>
    </fieldset>
    <fieldset>
        <legend>Template</legend>
        <?=form_error('template', '<div class="alert alert-danger">', '</div>')?>
        <ul class="templates">
            <?php

            $iNumTemplateGroups = count($templates);
            foreach ($templates as $oTemplateGroup) {

                if ($iNumTemplateGroups > 1) {
                    ?>
                    <li class="template-group-label">
                        <?=$oTemplateGroup->getLabel()?>
                    </li>
                    <?php
                }

                foreach ($oTemplateGroup->getTemplates() as $oTemplate) {

                    //  This template selected?
                    $bIsSelected = $defaultTemplate == $oTemplate->getSlug();

                    //  Define attributes
                    $aAttr = [
                        'class'     => $bIsSelected ? 'template selected' : 'template',
                        'data-slug' => $oTemplate->getSlug(),
                    ];

                    //  Glue together
                    $sAttrStr = '';
                    foreach ($aAttr as $sKey => $sValue) {
                        $sAttrStr .= $sKey . '="' . $sValue . '" ';
                    }

                    ?>
                    <li>
                        <label <?=trim($sAttrStr)?> rel="tipsy-top" title="<?=$oTemplate->getDescription()?>">
                            <?php

                            echo form_radio(
                                'template',
                                $oTemplate->getSlug(),
                                set_radio(
                                    'template',
                                    $oTemplate->getSlug(),
                                    $bIsSelected
                                ),
                                'data-slug="' . $aAttr['data-slug'] . '"'
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

                        $aAttr = [
                            'class'     => 'btn btn-default disabled launch-editor',
                            'data-area' => $sWidgetSlug,
                        ];

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

        if ($oInput->post('template_data')) {

            $sTemplateData = $oInput->post('template_data');

        } elseif (!empty($cmspage->draft->template_data)) {

            $sTemplateData = $cmspage->draft->template_data;

        } else {
            $sTemplateData = null;
        }

        $sTemplateData = json_encode($sTemplateData);
        $sTemplateData = htmlentities($sTemplateData);

        ?>
        <input type="hidden" name="template_data" id="template-data" value="<?=$sTemplateData?>" />
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

                $sTplSlug             = $oTemplate->getSlug();
                $aTplAdditionalFields = $oTemplate->getAdditionalFields();

                //  Any other fields, if specified
                if (!empty($aTplAdditionalFields)) {

                    ?>
                    <div id="additional-fields-<?=$sTplSlug?>" class="additional-fields">
                        <?php

                        foreach ($aTplAdditionalFields as $oField) {

                            //  Set the default key
                            $sFieldKey     = $oField->getKey();
                            $sFieldType    = $oField->getType();
                            $aFieldOptions = $oField->getOptions();
                            if (!empty($cmspage->draft->template_options->{$sFieldKey})) {
                                $oField->setDefault($cmspage->draft->template_options->{$sFieldKey});
                            }

                            //  Override the field key
                            $oField->setKey('template_options[' . $sTplSlug . '][' . $sFieldKey . ']');

                            //  Render the appropriate field
                            $sType = 'form_field_' . $oField->getProperty('type');
                            if (function_exists($sType)) {
                                echo $sType($oField->toArray());
                            } else {
                                echo form_field($oField->toArray());
                            }
                        }

                        ?>
                    </div>
                    <?php
                }
            }
        }

        ?>
    </fieldset>
    <fieldset>
        <legend>Search Engine Optimisation</legend>
        <?php

        echo form_field([
            'key'         => 'seo_title',
            'label'       => 'SEO Title',
            'default'     => html_entity_decode($cmspage->draft->seo_title ?? '', ENT_COMPAT | ENT_HTML5, 'UTF-8'),
            'placeholder' => 'The page\'s SEO title, keep this short and concise. If not set, this will fallback to the page title.',
            'max_length'  => 150,
        ]);

        echo form_field([
            'key'         => 'seo_description',
            'label'       => 'SEO Description',
            'default'     => html_entity_decode($cmspage->draft->seo_description ?? '', ENT_COMPAT | ENT_HTML5, 'UTF-8'),
            'placeholder' => 'The page\'s SEO description, keep this short and concise. Recommended to keep below 150 characters.',
            'tip'         => 'This should be kept short (< 300 characters) and concise. It\'ll be shown in search result listings and search engines will use it to help determine the page\'s content.',
            'max_length'  => 300,
        ]);

        echo form_field([
            'key'         => 'seo_keywords',
            'label'       => 'SEO Keywords',
            'default'     => html_entity_decode($cmspage->draft->seo_keywords ?? '', ENT_COMPAT | ENT_HTML5, 'UTF-8'),
            'placeholder' => 'Comma separated keywords relating to the content of the page. A maximum of 10 keywords is recommended.',
            'tip'         => 'SEO good practice recommend keeping the number of keyword phrases below 10 and less than 150 characters in total.',
            'max_length'  => 150,
        ]);

        echo form_field_cdn_object_picker([
            'key'     => 'seo_image_id',
            'label'   => 'SEO Image',
            'default' => $cmspage->draft->seo_image_id ?? null,
            'tip'     => 'Will be cropped to 1200x630px',
        ]);

        ?>
    </fieldset>
    <div class="admin-floating-controls">
        <input type="hidden" name="action" value="" id="input-action" />
        <button id="action-save" class="btn btn-primary" rel="tipsy-top" title="Your changes will be saved so you can come back later, but won't be published on site.">
            Save <span class="hidden-xs">Changes</span>
        </button>
        <button id="action-publish" class="btn btn-success" rel="tipsy-top" title="Your changes will be published on site and will take hold immediately.">
            Publish <span class="hidden-xs">Changes</span>
        </button>
        <a href="#" id="action-preview" class="btn btn-default right">
            <?=lang('action_preview')?>
        </a>
    </div>
    <?=form_close()?>
</div>
<div id="page-preview" class="group-cms pages cms-page-preview">
    <div class="spinner">
        <b class="fa fa-circle-o-notch fa-spin"></b>
    </div>
    <div class="row actions">
        <div class="col-xs-4 col-md-2">
            <button class="btn btn-primary btn-block btn-sm action-save">
                Save <span class="hidden-xs">Changes</span>
            </button>
        </div>
        <div class="col-xs-4 col-md-2">
            <button class="btn btn-success btn-block btn-sm action-publish">
                Publish <span class="hidden-xs">Changes</span>
            </button>
        </div>
        <div class="hidden-xs col-md-6">
        </div>
        <div class="col-xs-4 col-md-2">
            <button class="btn btn-danger btn-block btn-sm action-close">
                Close <span class="hidden-xs">Preview</span>
            </button>
        </div>
    </div>
    <div class="row iframe">
        <div class="col-xs-12">
            <iframe></iframe>
        </div>
    </div>
</div>
