<style type="text/css">
    div.ui-front {
        z-index: 1000;
    }
</style>
<?php

    //  Set the default template; either POST data, the one being used by the page, or the first in the list.
    if ($this->input->post('template')) {

        $defaultTemplate = $this->input->post('template');

    } elseif (!empty($cmspage->draft->template)) {

        $defaultTemplate = $cmspage->draft->template;

    } else {

        reset($templates);
        $defaultTemplate = key($templates);
    }

?>
<div class="group-cms pages edit">
    <?php

        switch ($this->input->get('message')) {

            case 'saved' :

                echo '<p class="system-alert success">';
                    echo 'Your page was saved successfully. ';
                    echo anchor(
                        'cms/render/preview/' . $cmspage->id,
                        'Preview it here',
                        'class="main-action" data-action="preview" target="_blank"'
                    ) . '.';
                echo '</p>';
                break;

            case 'published' :

                echo '<p class="system-alert success">';
                    echo 'Your page was published successfully. ';
                    echo anchor(
                        $cmspage->published->url,
                        'View it here',
                        'target="_blank"'
                    ) . '.';
                echo '</p>';
                break;

            case 'unpublished' :

                echo '<p class="system-alert success">';
                    echo 'Your page was unpublished successfully.';
                echo '</p>';
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
            $field                = array();
            $field['key']         = 'title';
            $field['label']       = 'Title';
            $field['default']     = isset($cmspage->draft->title) ? html_entity_decode($cmspage->draft->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
            $field['placeholder'] = 'The title of the page';

            echo form_field($field);

            // --------------------------------------------------------------------------

            //  Parent ID
            $field                     = array();
            $field['key']              = 'parent_id';
            $field['label']            = 'Parent Page';
            $field['placeholder']      = 'The Page\'s parent.';
            $field['class']            = 'select2';
            $field['default']          = isset($cmspage->draft->parent_id) ? $cmspage->draft->parent_id : '';
            $field['disabled_options'] = isset($page_children) ? $page_children : array();

            /**
             * Remove this page from the available options; INFINITE LOOP
             */

            if (isset($cmspage)) {

                foreach ($pagesNestedFlat as $id => $label) {

                    if ($id == $cmspage->id) {

                        $field['disabled_options'][] = $id;
                        break;

                    }
                }
            }

            if (count($pagesNestedFlat) && count($field['disabled_options']) < count($pagesNestedFlat)) {

                $pagesNestedFlat = array('' => 'No Parent Page') + $pagesNestedFlat;

                // --------------------------------------------------------------------------

                if (count($field['disabled_options'])) {

                    $field['info']  = '<strong>Some options have been disabled.</strong> ';
                    $field['info'] .= 'You cannot set the parent page to this page or any existing child of this page.';
                }

                echo form_field_dropdown($field, $pagesNestedFlat);

            } else {

                echo form_hidden($field['key'], '');
            }

            // --------------------------------------------------------------------------

            //  SEO Title
            $field                = array();
            $field['key']         = 'seo_title';
            $field['label']       = 'SEO Title';
            $field['default']     = isset($cmspage->draft->seo_title) ? html_entity_decode($cmspage->draft->seo_title, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
            $field['placeholder'] = 'The page\'s SEO title, keep this short and concise. If not set, this will fallback to the page title.';

            echo form_field($field);

            // --------------------------------------------------------------------------

            //  SEO Description
            $field                = array();
            $field['key']         = 'seo_description';
            $field['label']       = 'SEO Description';
            $field['default']     = isset($cmspage->draft->seo_description) ? html_entity_decode($cmspage->draft->seo_description, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
            $field['placeholder'] = 'The page\'s SEO description, keep this short and concise. Recommended to keep below 160 characters.';
            $field['tip']         = 'This should be kept short (< 160 characters) and concise. It\'ll be shown in search result listings and search engines will use it to help determine the page\'s content.';

            echo form_field($field);

            // --------------------------------------------------------------------------

            //  SEO Keywords
            $field                = array();
            $field['key']         = 'seo_keywords';
            $field['label']       = 'SEO Keywords';
            $field['default']     = isset($cmspage->draft->seo_keywords) ? html_entity_decode($cmspage->draft->seo_keywords, ENT_COMPAT | ENT_HTML5, 'UTF-8') : '';
            $field['placeholder'] = 'Comma separated keywords relating to the content of the page. A maximum of 10 keywords is recommended.';
            $field['tip']         = 'SEO good practice recommend keeping the number of keyword phrases below 10 and less than 160 characters in total.';

            echo form_field($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Template</legend>
        <ul class="templates">
        <?php

            foreach ($templates as $template) {

                echo '<li>';

                    //  This template selected?
                    $selected = $defaultTemplate == $template->getSlug() ? true : false;
    
                    //  Define attributes
                    $attr                       = array();
                    $attr['class']              = $selected ? 'template selected' : 'template';
                    $attr['data-template-slug'] = $template->getSlug();

                    //  Glue together
                    $attrStr = '';
                    foreach ($attr as $key => $value) {

                        $attrStr .= $key . '="' . $value . '" ';
                    }

                    echo '<label ' . trim($attrStr) . ' rel="tipsy-top" title="' . $template->getDescription() . '">';

                        echo form_radio(
                            'template',
                            $template->getSlug(),
                            set_radio(
                                'template',
                                $template->getSlug(),
                                $selected
                            )
                        );

                        echo '<span class="icon">';
                            if (!empty($template->getIcon())) {

                                echo img(
                                    array(
                                        'src'   => $template->getIcon(),
                                        'class' => 'icon'
                                    )
                                );
                            }
                        echo '</span>';
                        echo '<span class="newrow"></span>';
                        echo '<span class="name">';
                            echo '<span class="checkmark fa fa-check-circle"></span>';
                            echo '<span>' . $template->getLabel() . '</span>';
                        echo '</span>';
                    echo '</label>';
                echo '</li>';
            }

        ?>
        </ul>
    </fieldset>
    <fieldset>
        <legend>Template Configurations</legend>
        <?php

            //  Any additional page data for the templates
            foreach ($templates as $template) {

                //  Shortcut
                if (isset($cmspage) && property_exists($cmspage->draft->template_data->data->additional_fields, $template->getSlug())) {

                    $additionalFields = $cmspage->draft->template_data->data->additional_fields->{$template->getSlug()};

                } else {

                    $additionalFields = null;
                }

                $visible = $defaultTemplate == $template->getSlug() ? 'block' : 'none';
                echo '<div id="additional-fields-' . $template->getSlug() . '" class="additional-fields" style="display:' . $visible . '">';

                //  Common, manual config item
                $field                 = array();
                $field['key']          = 'additional_field[' . $template->getSlug() . '][manual_config]';
                $field['label']        = 'Manual Config';
                $field['sub_label']    = 'Specify any manual config items here. This field should be ';
                $field['sub_label']   .= anchor(
                    'http://en.wikipedia.org/wiki/JSON',
                    'JSON encoded',
                    'class="fancybox" data-fancybox-type="iframe" data-width="90%" data-height="90%"'
                ) . '.';
                $field['type']    = 'textarea';
                $field['default'] = !empty($additionalFields->manual_config) ? $additionalFields->manual_config : '';

                echo form_field($field);

                //  Any other fields, if specified
                if ($template->getAdditionalFields()) {

                    foreach ($template->getAdditionalFields() as $field) {

                        //  Set the default key
                        if (!empty($additionalFields) && property_exists($additionalFields, $field->key)) {

                            $field->default = $additionalFields->{$field->key};

                        }

                        //  Override the field key
                        $field->key = 'additional_field[' . $template->getSlug() . '][' . $field->key . ']';

                        switch ($field->type) {

                            case 'dropdown' :

                                $options = !empty($field->options) ? $field->options : array();
                                echo form_field_dropdown($field->toArray(), $options);
                                break;

                            default :

                                echo form_field($field->toArray());
                                break;
                        }
                    }
                }

                echo '</div>';
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

            foreach ($templates as $template) {

                //  This template selected?
                $selected = $defaultTemplate == $template->getSlug() ? true : false;

                foreach ($template->getWidgetAreas() as $slug => $area) {

                    //  Define attributes
                    $data              = array();
                    $data['area-slug'] = $slug;

                    $attr = '';
                    foreach ($data as $key => $value) {

                        $attr .= 'data-' . $key . '="' . $value . '" ';
                    }

                    //  Define attributes
                    $attr                  = array();
                    $attr['class']         = 'awesome launch-editor template-' . $template->getSlug();
                    $attr['style']         = $selected ? 'display:inline-block;' : 'display:none;';
                    $attr['data-template'] = $template->getSlug();
                    $attr['data-area']     = $slug;

                    //  Glue together
                    $attrStr = '';
                    foreach ($attr as $key => $value) {

                        $attrStr .= $key . '="' . $value . '" ';
                    }

                    echo '<a href="#" ' . trim($attrStr) . '>' . $area->title . '</a>';
                }
            }

        ?>
        </p>
    </fieldset>
    <?php

        if (isset($cmspage) && $cmspage->is_published && $cmspage->published->hash !== $cmspage->draft->hash) {

            echo '<p class="system-alert message">';
                echo '<strong>You have unpublished changes.</strong><br />This version of the page is more recent ';
                echo 'than the version currently published on site. When you\'re done make sure you click ';
                echo '"Publish Changes" below.';
            echo '</p>';
        }

    ?>
    <p class="actions">
    <?php

        echo '<a href="#" data-action="save" class="main-action awesome orange large" rel="tipsy-top" title="Your changes will be saved so you can come back later, but won\'t be published on site.">Save Changes</a>';
        echo '<a href="#" data-action="publish" class="main-action awesome green large" rel="tipsy-top" title="Your changes will be published on site and will take hold immediately.">Publish Changes</a>';
        echo '<a href="#" data-action="preview" class="main-action awesome large launch-preview right">' . lang('action_preview') . '</a>';

    ?>
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
        <li><a href="#" class="main-action" data-action="preview">Preview</a></li>
        <li><a href="#" class="action" data-action="close">Close</a></li>
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
            <p class="title">No widgets</p>
            <p class="label">Drag widgets from the left to start building your page.</p>
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
