/* globals console, _nails_api */
var NAILS_Admin_CMS_Pages_CreateEdit;
NAILS_Admin_CMS_Pages_CreateEdit = function(widgetEditor, templates) {
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * The widget editor instance
     * @type {Object}
     */
    base.editor = widgetEditor;

    // --------------------------------------------------------------------------

    /**
     * The available page templates
     * @type {Array}
     */
    base.templates = templates;

    // --------------------------------------------------------------------------

    /**
     * The active template
     * @type {String}
     */
    base.activeTemplate = '';

    // --------------------------------------------------------------------------

    /**
     * Construct the CMS widget editor
     * @return {void}
     */
    base.__construct = function() {

        base.log('Constructing Pages Editor');
        base.bindEvents();
        base.populateEditor();

        //  Add the preview, save and publish actions to the widget editor
        base.editor.addAction(
            'Preview',
            'default',
            function() {
                base.showPreview();
            }
        );

        base.editor.addAction(
            'Publish Changes',
            'success',
            function() {
                base.submitForm('PUBLISH');
            }
        );

        base.editor.addAction(
            'Save Changes',
            'primary',
            function() {
                base.submitForm('SAVE');
            }
        );

        /**
         * Prepare the preview window
         * - Move the preview element to the foot of the page
         * - Match the zindex of the widgeteditor
         */
        var preview = $('#page-preview');

        $('body').append(preview);
        preview.css('zIndex', base.editor.container.css('zIndex'));

        //  Organise the interface for the selected template
        $('.template.selected input').click();
    };

    // --------------------------------------------------------------------------

    /**
     * Bind to various events
     * @return {Object}
     */
    base.bindEvents = function() {

        $('.template input').on('click', function() {
            base.selectTemplate($(this).data('slug'));
        });

        $('button.launch-editor').on('click', function() {
            if (!base.editor.isReady()) {
                base.log('widget Editor not ready');
                return false;
            }

            var area = $(this).data('area');
            base.log('Launching editor for area "' + area + '"');
            base.editor.show(area);
            return false;
        });

        $(base.editor).on('widgeteditor-ready', function() {
            $('button.launch-editor')
                .removeClass('disabled');
        });

        $('#action-preview').on('click', function() {
            base.showPreview();
            return false;
        });

        $('#action-save').on('click', function() {
            base.submitForm('SAVE');
            return false;
        });

        $('#action-publish').on('click', function() {
            base.submitForm('PUBLISH');
            return false;
        });

        $('#page-preview .action-close').on('click', function() {
            base.hidePreview();
            return false;
        });

        $('#page-preview .action-publish').on('click', function() {
            base.hidePreview();
            base.submitForm('PUBLISH');
            return false;
        });

        $('#page-preview .action-save').on('click', function() {
            base.hidePreview();
            base.submitForm('SAVE');
            return false;
        });

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Populate the editor widget data
     * @return {Object}
     */
    base.populateEditor = function() {

        var areaData, area, widgetData;

        areaData = $('#template-data').val() || null;
        areaData = JSON.parse(areaData);

        if (areaData) {
            base.log('Repopulating widget editor');
            for (area in areaData) {
                if (areaData.hasOwnProperty(area)) {
                    widgetData = areaData[area];
                    base.editor.setAreaData(area, widgetData);
                }
            }
        }
        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Select a template, showing the appropriate options and areas
     * @param  {String} slug The template's slug
     * @return {Object}
     */
    base.selectTemplate = function(slug) {

        base.log('Showing template "' + slug + '"');
        base.activeTemplate = slug;

        //  Reset
        $('.template').removeClass('selected');
        $('#template-options-none').hide();
        $('.additional-fields').hide();
        $('#template-areas-none').hide();
        $('.template-area').hide();

        //  Select template
        $('.template[data-slug=' + slug + ']')
            .addClass('selected');

        //  Show Template options and areas
        $('#additional-fields-' + slug).show();
        $('#template-area-' + slug).show();

        //  Show alerts if no options/areas are visible
        if ($('.additional-fields:visible').length === 0) {
            $('#template-options-none').show();
        }

        if ($('.template-area:visible').length === 0) {
            $('#template-areas-none').show();
        }

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Generate a preview and show in a model window
     * @return {Object}
     */
    base.showPreview = function() {

        base.log('Generating preview');
        base.getEditorData();

        $('body').addClass('noscroll');

        var preview = $('#page-preview');

        preview
            .addClass('loading')
            .show();

        //  Size the iframe
        var previewPadding = parseFloat(preview.css('padding'));
        var actionsHeight = preview.find('> .actions').outerHeight();
        var newHeight = $(window).height() - (previewPadding * 2) - actionsHeight;

        preview.find('.iframe, .iframe > div').css('height', newHeight);

        _nails_api.call({
            'controller': 'cms/pages',
            'method': 'preview',
            'action': 'POST',
            'data': $('#main-form').serialize(),
            'success': function(response) {
                $('#page-preview iframe').attr('src', response.data.url);
            },
            'error': function(response) {

                var _data;
                try {
                    _data = JSON.parse(response.responseText);
                } catch (e) {
                    _data = {
                        'status': 500,
                        'error': 'An unknown error occurred.'
                    };
                }

                base.warn('Failed to generate a preview.', _data);
                $('<div>')
                    .text('Failed to generate preview. ' + _data.error)
                    .dialog({
                        'title': 'An error occurred',
                        'resizable': false,
                        'draggable': false,
                        'modal': true,
                        'dialogClass': 'group-cms widgeteditor-alert',
                        'buttons': {
                            'OK': function() {
                                $(this).dialog('close');
                                base.hidePreview();
                            }
                        }
                    });
            }
        });

        return base;
    };

    // --------------------------------------------------------------------------

    base.hidePreview = function() {

        base.log('Hiding preview');

        $('#page-preview')
            .hide()
            .removeClass('loading')
            .find('iframe')
            .removeAttr('src');

        $('body').removeClass('noscroll');

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Submit the form
     * @param  {String} type The type of submission [SAVE|PUBLISH]
     * @return {Object}
     */
    base.submitForm = function(type) {

        type = type || 'SAVE';
        base.log('Submitting form: ' + type);
        base.getEditorData();
        $('#input-action').val(type);
        $('#main-form').submit();
        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Save the contents of the editor for the template
     * @return {Object}
     */
    base.getEditorData = function(template) {

        var area, data = {};

        template = template || base.activeTemplate;
        $('#template-area-' + template + ' .btn').each(function() {

            area = $(this).data('area');
            data[area] = base.editor.getAreaData(area);
        });

        $('#template-data').val(JSON.stringify(data));

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Write a log to the console
     * @param  {String} message The message to log
     * @param  {Mixed}  payload Any additional data to display in the console
     * @return {Void}
     */
    base.log = function(message, payload) {

        if (typeof(console.log) === 'function') {
            if (payload !== undefined) {
                console.log('CMS Pages:', message, payload);
            } else {
                console.log('CMS Pages:', message);
            }
        }
    };

    // --------------------------------------------------------------------------

    /**
     * Write a warning to the console
     * @param  {String} message The message to warn
     * @param  {Mixed}  payload Any additional data to display in the console
     * @return {Void}
     */
    base.warn = function(message, payload) {

        if (typeof(console.warn) === 'function') {
            if (payload !== undefined) {
                console.warn('CMS Pages:', message, payload);
            } else {
                console.warn('CMS Pages:', message);
            }
        }
    };

    // --------------------------------------------------------------------------

    return base.__construct();
};
