<style type="text/css">
    div.ui-front {
        z-index: 1000;
    }
</style>
<div class="group-cms pages edit">
    <?php

    switch ($this->input->get('message')) {

        case 'saved' :

            ?>
            <p class="system-alert success">
                Your page was saved successfully.
                <?php

                echo anchor(
                    'cms/render/preview/' . $cmspage->id,
                    'Preview it here',
                    'class="main-action" data-action="preview" target="_blank"'
                ) . '.';

                ?>
            </p>
            <?php

            break;

        case 'published' :

            ?>
            <p class="system-alert success">
                Your page was published successfully.
                <?=anchor( $cmspage->published->url, 'View it here', 'target="_blank"')?>.
            </p>
            <?php

            break;

        case 'unpublished' :

            ?>
            <p class="system-alert success">
                Your page was unpublished successfully.
            </p>
            <?php

            break;
    }

    ?>
    <div class="system-alert notice" id="save-status">
        <p>
            <small>
                Last Saved: <span class="last-saved">Not Saved</span>
                <span class="fa fa-refresh fa-spin"></span>
            </small>
        </p>
    </div>
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

        // --------------------------------------------------------------------------

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
                $attr                       = array();
                $attr['class']              = $selected ? 'template selected' : 'template';
                $attr['data-template-slug'] = $oTemplate->getSlug();

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
                            )
                        );

                        ?>
                        <span class="icon">
                            <?php

                            if (!empty($oTemplate->getIcon())) {

                                echo img(
                                    array(
                                        'src'   => $oTemplate->getIcon(),
                                        'class' => 'icon'
                                    )
                                );
                            }

                            ?>
                        </span>
                        <span class="newrow"></span>
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
    <fieldset>
        <legend>Template Configurations</legend>
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

                $bDisplay = $defaultTemplate == $sTplSlug ? 'block' : 'none';
                echo '<div id="additional-fields-' . $sTplSlug . '" class="additional-fields" style="display:' . $bDisplay . '">';

                //  Common, manual config item
                $aField               = array();
                $aField['key']        = 'additional_field[' . $sTplSlug . '][manual_config]';
                $aField['label']      = 'Manual Config';
                $aField['sub_label']  = 'Specify any manual config items here. This field should be ';
                $aField['sub_label'] .= anchor(
                    'http://en.wikipedia.org/wiki/JSON',
                    'JSON encoded',
                    'class="fancybox" data-fancybox-type="iframe" data-width="90%" data-height="90%"'
                ) . '.';
                $aField['type']    = 'textarea';
                $aField['default'] = !empty($additionalFields->manual_config) ? $additionalFields->manual_config : '';

                echo form_field($aField);

                //  Any other fields, if specified
                if (!empty($aTplAdditionalFields)) {

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
                }

                echo '</div>';
            }
        }

        ?>
    </fieldset>
    <fieldset>
        <legend>Page Content</legend>
        <p>
            Choose which area of the page you'd like to edit.
        </p>
        <p>
        <?php

        foreach ($templates as $oTemplateGroup) {

            $aTemplates = $oTemplateGroup->getTemplates();
            foreach ($aTemplates as $oTemplate) {

                $bSelected    = $defaultTemplate == $oTemplate->getSlug() ? true : false;
                $sTplSlug     = $oTemplate->getSlug();
                $aWidgetAreas = $oTemplate->getWidgetAreas();

                foreach ($aWidgetAreas as $sWidgetSlug => $oWidgetArea) {

                    $aAttr                   = array();
                    $aAttr['class']          = 'awesome launch-editor template-' . $sTplSlug;
                    $aAttr['style']          = $bSelected ? 'display:inline-block;' : 'display:none;';
                    $aAttr['data-template']  = $sTplSlug;
                    $aAttr['data-area']      = $sWidgetSlug;

                    $attrStr = '';
                    foreach ($aAttr as $sKey => $sValue) {

                        $attrStr .= $sKey . '="' . $sValue . '" ';
                    }

                    echo '<a href="#" ' . trim($attrStr) . '>' . $oWidgetArea->getTitle() . '</a>';
                }
            }
        }

        ?>
        </p>
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
        <a href="#" data-action="save" class="main-action awesome orange large" rel="tipsy-top" title="Your changes will be saved so you can come back later, but won\'t be published on site.">
            Save Changes
        </a>
        <a href="#" data-action="publish" class="main-action awesome green large" rel="tipsy-top" title="Your changes will be published on site and will take hold immediately.">
            Publish Changes
        </a>
        <a href="#" data-action="preview" class="main-action awesome large launch-preview right">
            <?=lang('action_preview')?>
        </a>
    </p>
</div>
<script type="text/template" id="template-loader">
    <span class="fa fa-refresh fa-spin"></span>
</script>
<script type="text/template" id="template-header">
    <ul>
        <li>
            Currently editing: {{active_area}}
        </li>
    </ul>
    <ul class="rhs">
        <li>
            <a href="#" class="main-action" data-action="preview">Preview</a>
        </li>
        <li>
            <a href="#" class="action" data-action="close">Close</a>
        </li>
    </ul>
</script>
<script type="text/template" id="template-widget-search">
    <input type="search" placeholder="Search widget library" />
    <a href="#" class="minimiser">
        <span class="fa fa-navicon"></span>
    </a>
</script>
<script type="text/template" id="template-widget-grouping">
    <li class="grouping open" data-group="{{group}}">
        <span class="icon fa fa-folder"></span>
        <span class="label">{{name}}</span>
        <span class="toggle-open right fa fa-sort-desc"></span>
        <span class="toggle-closed right fa fa-sort-asc"></span>
    </li>
</script>
<script type="text/template" id="template-widget">
    <li class="widget {{group}} {{slug}}" data-slug="{{slug}}" data-title="{{name}} Widget" data-keywords="{{keywords}}" title="">
        <span class="icon fa fa-arrows"></span>
        <span class="label">{{name}}</span>
        {{#description}}<span class="description">{{description}}</span>{{/description}}
    </li>
</script>
<script type="text/template" id="template-dropzone-empty">
    <li class="empty">
        <div class="valigned">
            <p class="title">
                No widgets
            </p>
            <p class="label">
                Drag widgets from the left to start building your page.
            </p>
        </div>
        <div class="valigned-helper"></div>
    </li>
</script>
<script type="text/template" id="template-dropzone-widget">
    <div class="header-bar">
        <span class="sorter">
            <span class="fa fa-arrows"></span>
        </span>
        <span class="label">{{label}}</span>
        <span class="closer fa fa-trash-o"></span>
        {{#description}}<span class="description">{{description}}</span>{{/description}}
    </div>
    <form class="editor">
        <p style="text-align:center;">
            <span class="fa fa-refresh fa-spin"></span>
            <br />
            Please wait, loading widget
        </p>
    </form>
</script>
