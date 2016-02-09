/* globals console, _nails, _nails_admin, _nails_api, Mustache */
var NAILS_Admin_CMS_WidgetEditor;
NAILS_Admin_CMS_WidgetEditor = function()
{
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * An array of the available widgets
     * @type {Array}
     */
    base.widgets = [];

    // --------------------------------------------------------------------------

    /**
     * An array of actions to render
     * @type {Array}
     */
    base.actions = [
        {
            'label': '<i class="fa fa-lg fa-times"></i>',
            'type': 'danger',
            'callback': function() {
                base.widgetData[base.activeArea] = base.getActiveData();
                base.close();
            }
        }
    ];

    // --------------------------------------------------------------------------

    /**
     * An array of the data to use when rendering widget areas
     * @type {Array}
     */
    base.widgetData = {};

    // --------------------------------------------------------------------------

    /**
     * The main editor container
     */
    base.container = null;

    // --------------------------------------------------------------------------

    /**
     * The individual editor sections
     */
    base.sections = {};

    // --------------------------------------------------------------------------

    /**
     * Whether the editor is currently open
     * @type {Boolean}
     */
    base.isOpen = false;

    // --------------------------------------------------------------------------

    /**
     * The currently active area
     * @type {String}
     */
    base.activeArea = '';

    // --------------------------------------------------------------------------

    /**
     * The name/slug of the default area
     * @type {String}
     */
    base.defaultArea = 'default';

    // --------------------------------------------------------------------------

    /**
     * Holds the timeout when seraching
     * @type {Number}
     */
    base.searchTimeout = null;

    // --------------------------------------------------------------------------

    /**
     * The delay before searching widgets, in milliseconds
     * @type {Number}
     */
    base.searchDelay = 150;

    // --------------------------------------------------------------------------

    /**
     * Construct the CMS widget editor
     * @return {Object}
     */
    base.__construct = function() {

        base.log('Constructing Widget Editor');

        //  Inject markup
        base.generateMarkup();
        base.bindEvents();

         // Fetch and render available widgets
        base.loadSidebarWidgets()
        .done(function() {
            base.renderSidebarWidgets();
        });

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Generate the base markup for the editor
     * @return {Object}
     */
    base.generateMarkup = function() {

        var item;

        if (base.container === null) {

            base.log('Injecting editor markup');

            base.container = $('<div>').addClass('group-cms widgeteditor');
            base.sections  = {
                'header': $('<div>').addClass('widgeteditor-header').text('Some header text, maybe'),
                'actions': $('<div>').addClass('widgeteditor-actions'),
                'search': $('<div>').addClass('widgeteditor-search'),
                'widgets': $('<div>').addClass('widgeteditor-widgets'),
                'body': $('<div>').addClass('widgeteditor-body').html(
                    '<ul></ul><div class="no-widgets">Drag widgets from the left to here</div>'
                ),
                'widget': $('<div>').html(
                    '<i class="icon fa {{icon}}"></i>' +
                    '<span>{{label}}</span>' +
                    '<a href="#" class="action action-remove fa fa-trash-o"></a>' +
                    '<a href="#" class="action action-refresh-editor fa fa-refresh"></a>' +
                    '<div class="description">{{description}}</div>' +
                    '<div class="editor-target fieldset">Widget Loading...</div>'
                )
            };

            //  Add search input
            item = $('<input>').attr('type', 'search').attr('placeholder', 'Search widgets');
            base.sections.search.append(item);

            //  Glue it all together
            base.container
                .append(base.sections.header)
                .append(base.sections.actions)
                .append(base.sections.search)
                .append(base.sections.widgets)
                .append(base.sections.body);

            $('body').append(base.container);

            //  Render any actions and widgets
            base.renderSidebarWidgets();
            base.renderActions();

        } else {

            base.warn('Editor already generated');
        }

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Bind to various events
     * @return {Object}
     */
    base.bindEvents = function() {

        var actionIndex, groupIndex;

        //  Actions
        base.sections.actions.on('click', '.action', function() {

            actionIndex = $(this).data('action-index');
            base.log('Action clicked', actionIndex);

            if (base.actions[actionIndex]) {
                if (typeof base.actions[actionIndex].callback === 'function') {
                    base.actions[actionIndex].callback.call(base);
                }
            } else {
                base.warn('"' + actionIndex+ '" is not a valid action.');
            }
            return false;
        });

        //  Widgets
        base.sections.widgets.on('click', '.widget-group', function() {

            base.log('Toggling visibility of group\'s widgets.');
            if ($(this).hasClass('closed')) {
                $(this).removeClass('closed');
            } else {
                $(this).addClass('closed');
            }

            groupIndex = $(this).data('group');
            $('.widget-group-' + groupIndex, base.sections.widgets).toggleClass('hidden');

            //  Save the state to localstorage
            _nails_admin.localStorage
            .set(
                'widgeteditor-group-' + groupIndex + '-hidden',
                $('.widget-group-' + groupIndex).hasClass('hidden')
            );
        });

        //  Search
        base.sections.search.on('keyup', function() {

            if (base.searchTimeout) {
                clearTimeout(base.searchTimeout);
            }

            base.searchTimeout = setTimeout(function() {

                var term = base.sections.search.find('input').val().trim();
                base.searchWidgets(term);

            }, base.searchDelay);
        });

        //  Body
        base.sections.body.on('click', '.action-remove', function() {

            var domElement = $(this).closest('.widget');
            var widget     = base.getWidget(domElement.data('slug'));

            base.confirm('Are you sure you wish to remove this "' + widget.label + '" widget from the interface?')
            .done(function() {

                base.log('Removing Widget');
                domElement.remove();
                widget.callbacks.removed.call(base, domElement);
            });
        });

        base.sections.body.on('click', '.action-refresh-editor', function() {

            var widget = $(this).closest('.widget');
            var slug   = widget.data('slug');
            var data   = widget.find(':input').serializeObject();

            widget
                .addClass('editor-loading')
                .find('.editor-target')
                .removeClass('alert alert-danger')
                .empty()
                .text('Widget Loading...');

            base.setupWidgetEditor(slug, data, widget);

        });

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Laod the available widgets from the server
     * @return {Object} Deferred
     */
    base.loadSidebarWidgets = function() {

        var deferred, i, x, key, assetsCss, assetsJs;

        base.log('Fetching available CMS widgets');

        deferred = $.Deferred();

        _nails_api.call({
            'controller': 'cms/widgets',
            'method': 'index',
            'success': function(data) {

                base.log('Succesfully fetched widgets from the server.');
                base.widgets = data.widgets;

                //  Make the callbacks on each widget callable
                for (i = base.widgets.length - 1; i >= 0; i--) {
                    for (x = base.widgets[i].widgets.length - 1; x >= 0; x--) {
                        for (key in base.widgets[i].widgets[x].callbacks) {
                            if (base.widgets[i].widgets[x].callbacks.hasOwnProperty(key)) {
                                /* jshint ignore:start */
                                base.widgets[i].widgets[x].callbacks[key] = new Function(
                                    'domElement',
                                    base.widgets[i].widgets[x].callbacks[key]
                                );
                                /* jshint ignore:end */
                            }
                        }
                    }
                }

                //  Inject any assets to load into the  DOM
                assetsCss = '';
                for (i = 0; i < data.assets.css.length; i++) {
                    assetsCss += data.assets.css[i] + "\n";
                }

                assetsJs = '';
                for (i = 0; i < data.assets.js.length; i++) {
                    assetsJs += data.assets.js[i] + "\n";
                }

                $('head').append(assetsCss);
                $('body').append(assetsJs);

                deferred.resolve();
            },
            'error': function() {

                base.warn('Failed to load widgets from the server.');
                deferred.reject();
            }
        });

        return deferred;
    };

    // --------------------------------------------------------------------------

    /**
     * Render the widgets
     * @return {Object}
     */
    base.renderSidebarWidgets = function() {

        base.sections.widgets.empty();

        var i, x, label, toggle, container, icon;

        for (i = 0; i < base.widgets.length; i++) {

            //  Widget Group
            label     = $('<span>').text(base.widgets[i].label);
            toggle    = $('<i>').addClass('icon fa fa-chevron-down');
            container = $('<div>').addClass('widget-group').data('group', i).append(label).append(toggle);

            //  Hidden by default?
            var hidden = _nails_admin.localStorage.get('widgeteditor-group-' + i + '-hidden');
            if (hidden === true || hidden === null) {
                container.addClass('closed');
            }

            base.sections.widgets.append(container);

            //  Individual widgets
            for (x = 0; x < base.widgets[i].widgets.length; x++) {

                icon      = $('<i>').addClass('icon fa ' + base.widgets[i].widgets[x].icon);
                label     = $('<span>').text(base.widgets[i].widgets[x].label);
                container = $('<div>')
                            .addClass('widget widget-group-' + i)
                            .data('group', i)
                            .data('slug', base.widgets[i].widgets[x].slug)
                            .data('keywords', base.widgets[i].widgets[x].keywords);

                if (hidden === true || hidden === null) {
                    container.addClass('hidden');
                }

                container
                    .append(icon)
                    .append(label);

                base.sections.widgets.append(container);
            }
        }

        return base;
    };

    // --------------------------------------------------------------------------

    base.searchWidgets = function(term) {

        var keywords, i, regex, group;

        if (term.length > 0) {

            base.log('Filtering widgets by term "' + term + '"');

            //  Hide widgets which do not match the search term
            base.sections.widgets.find('.widget').each(function() {

                keywords = $(this).data('keywords').split(',');
                for (i = keywords.length - 1; i >= 0; i--) {

                    regex = new RegExp(term, 'gi');
                    if (regex.test(keywords[i])) {

                        $(this).removeClass('search-hide');
                        $(this).addClass('search-show');
                        return;

                    } else {

                        $(this).removeClass('search-show');
                        $(this).addClass('search-hide');
                    }
                }
            });

            //  Hide group headings which no longer have any widgets showing
            base.sections.widgets.find('.widget-group').each(function() {

                group = $(this).data('group');
                if ($('.widget-group-' + group + '.search-show').length) {

                    $(this).removeClass('search-hide');
                    $(this).addClass('search-show');
                    return;

                } else {

                    $(this).removeClass('search-show');
                    $(this).addClass('search-hide');
                }
            });

        } else {

            base.log('Restoring widgets');
            base.sections.widgets.find('.widget, .widget-group').removeClass('search-show search-hide');
        }
    };

    // --------------------------------------------------------------------------

    /**
     * Add an action to the header
     * @param {String}   label    The label of the button
     * @param {String}   type     The type of button (e.g., primary)
     * @param {Function} callback A callback to call when the button is clicked
     */
    base.addAction = function(label, type, callback) {

        base.log('Adding action "' + label + '"');
        base.actions.push({
            'label': label,
            'type': type,
            'callback': callback
        });
        base.renderActions();
        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Remove a widget from an area
     * @param  {String} area       The area to remove from
     * @param  {String} widgetSlug The widget's slug
     * @return {Object}
     */
    base.removeAction = function(label) {

        base.log('Removing action "' + label + '"');
        base.renderActions();
        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Render the actions
     * @return {Object}
     */
    base.renderActions = function() {

        var i, action;

        base.sections.actions.empty();

        for (i = base.actions.length - 1; i >= 0; i--) {

            action = $('<a>')
                .addClass('action btn btn-sm btn-' + base.actions[i].type)
                .data('action-index', i)
                .html(base.actions[i].label);

            base.sections.actions.append(action);
        }

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Show the widget editor and populate with the data for a particular area
     * @param  {String} area Load widgets for this area
     * @return {Object}
     */
    base.show = function(area) {

        area = area || base.defaultArea;

        base.log('Showing Editor for "' + area + '"');

        base.activeArea = area;

        //  Build the widgets for this area
        base.sections.body.find('> ul').empty();

        if (base.widgetData[area] && base.widgetData[area].length) {

            base.log('Adding previous widgets');
            var requestDom  = [];
            var requestData = [];

            for (var key in base.widgetData[area]) {

                if (base.widgetData[area].hasOwnProperty(key)) {

                    //  Add to the DOM, addWidget will populate
                    var widgetDom = $('<div>').addClass('widget');
                    base.sections.body.find('> ul').append(widgetDom);

                    base.addWidget(
                        base.widgetData[area][key].slug,
                        base.widgetData[area][key].data,
                        widgetDom,
                        true
                    );

                    // --------------------------------------------------------------------------

                    requestDom.push(widgetDom);
                    requestData.push(base.widgetData[area][key]);
                }
            }

            base.log('Setting up all widgets');

            //  Send request off for all widget editors
            _nails_api.call({
                'controller': 'cms/widgets',
                'method': 'editors',
                'action': 'POST',
                'data': {
                    'data': requestData
                },
                'success': function(data) {

                    var i, widget;

                    base.log('Succesfully fetched widget editors from the server.');
                    for (i = 0; i < data.data.length; i++) {

                        if (!data.data[i].error) {

                            base.setupWidgetEditorOk(
                                requestDom[i],
                                data.data[i].editor
                            );

                        } else {

                            base.setupWidgetEditorFail(
                                requestDom[i],
                                data.data[i].editor
                            );
                        }
                    }

                    //  Now instanciate everything
                    base.initWidgetEditorElements();

                    //  Finally, call the "dropped" callback on each widget
                    for (i = 0; i < data.data.length; i++) {

                        widget = base.getWidget(data.data[i].slug);
                        widget.callbacks.dropped.call(base, requestDom[i]);
                    }

                },
                'error': function(data) {

                    var _data;

                    try {

                        _data = JSON.parse(data.responseText);

                    } catch (e) {

                        _data = {
                            'status': 500,
                            'error': 'An unknown error occurred.'
                        };
                    }
                    base.warn('Failed to load widget editors from the server with error: ', _data.error);
                    //  @todo show an alert/dialog
                }
            });

        }

        //  Make things draggable and sortable
        base.draggableConstruct();
        base.sortableConstruct();

        //  Show the editor
        base.container.addClass('active');
        base.isOpen = true;

        //  Prevent scrolling on the body
        $('body').addClass('noscroll');

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Close the editor
     * @return {Object}
     */
    base.close = function() {

        base.log('Closing Editor');
        base.container.removeClass('active');
        base.isOpen = false;
        base.activeArea = '';

        //  Destroy all sortables and draggables
        base.draggableDestroy();
        base.sortableDestroy();

        //  Allow body scrolling
        $('body').removeClass('noscroll');

        $(base).trigger('widgeteditor-close');

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Set up draggables
     * @return {Object}
     */
    base.draggableConstruct = function() {

        base.sections.widgets.find('.widget').draggable({
            'helper': 'clone',
            'connectToSortable': base.sections.body.find('> ul'),
            'appendTo': base.container,
            'zIndex': 100
        });

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Destroy draggables
     * @return {Object}
     */
    base.draggableDestroy = function() {

        base.sections.widgets
            .find('.widget.ui-draggable')
            .draggable('destroy');

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Set up sortables
     * @return {Object}
     */
    base.sortableConstruct = function() {

        base.sections.body.find('> ul').sortable({
            placeholder: 'sortable-placeholder',
            handle: '.icon',
            axis: 'y',
            start: function(e, ui)
            {
                ui.placeholder.height(ui.helper.outerHeight());
                _nails_admin.destroyWysiwyg('basic', ui.helper);
                _nails_admin.destroyWysiwyg('default', ui.helper);
            },
            receive: function(e, ui)
            {
                var sourceWidget, targetWidget, widgetSlug;

                sourceWidget = ui.item;
                targetWidget = ui.helper;
                widgetSlug   = sourceWidget.data('slug');

                base.addWidget(widgetSlug, null, targetWidget);

                //  Allow auto sizing
                targetWidget.removeAttr('style');
            },
            stop: function(e, ui) {

                _nails_admin.buildWysiwyg('basic', ui.helper);
                _nails_admin.buildWysiwyg('default', ui.helper);
            }
        });

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Destroy sortables
     * @return {Object}
     */
    base.sortableDestroy = function() {

        var sortable = base.sections.body.find('> ul.ui-sortable');

        if (sortable.length > 0) {
            sortable.sortable('destroy');
        }

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Add a widget to the interface
     * @param  {String|Object} slug      The widget slug, or the widget Object
     * @param  {Object}        data      Any data to populate the editor with
     * @param  {DomElement}    widgetDom The widget's DOM element
     * @return {Object}
     */
    base.addWidget = function(widget, data, widgetDom, skipSetup) {

        if (typeof widget === 'string') {

            widget = base.getWidget(widget);
        }

        if (widget) {

            base.log('Adding Widget "' + widget.slug + '" with data:', data);

            //  Populate the dom element with the widget template
            $(widgetDom)
                .empty()
                .data('slug', widget.slug)
                .addClass('editor-loading')
                .html(Mustache.render(base.sections.widget.html(), widget));

            //  Setup the widget's editor
            if (!skipSetup) {
                base.setupWidgetEditor(widget.slug, data, widgetDom);
            }

        } else {

            base.warn('Attempted to add an invalid widget');
        }

        return base;
    };

    // --------------------------------------------------------------------------

    base.setupWidgetEditor = function(slug, data, widgetDom) {

        base.getWidgetEditor(slug, data)
        .done(function(data) {

            base.log('Editor Received');
            base.setupWidgetEditorOk(widgetDom, data);

            //  Now the markup is in place we need to ensure that things look the part
            base.initWidgetEditorElements(widgetDom);

            //  Finally, call the "dropped" callback
            var widget = base.getWidget(slug);
            widget.callbacks.dropped.call(base, widgetDom);
        })
        .fail(function(data) {

            base.setupWidgetEditorFail(widgetDom, data.error);
        });
    };

    // --------------------------------------------------------------------------

    base.setupWidgetEditorOk = function(widgetDom, editorData) {

        widgetDom
            .removeClass('editor-loading')
            .find('.editor-target')
            .html(editorData);
    };

    // --------------------------------------------------------------------------

    base.setupWidgetEditorFail = function(widgetDom, error) {

        widgetDom
            .removeClass('editor-loading')
            .find('.editor-target')
            .addClass('alert alert-danger')
            .html('<strong>Error:</strong> ' + error);
    };

    // --------------------------------------------------------------------------

    base.initWidgetEditorElements = function(widgetDom) {

        //  Table stripes
        _nails.addStripes(widgetDom);

        //  WYSIWYG editors
        _nails_admin.buildWysiwyg('basic', widgetDom);
        _nails_admin.buildWysiwyg('default', widgetDom);

        //  Select2 Dropdowns
        _nails_admin.initSelect2(widgetDom);

        //  Toggles
        _nails_admin.initToggles(widgetDom);

        //  Tipsys
        _nails.initTipsy(widgetDom);
    };

    // --------------------------------------------------------------------------

    /**
     * Return a single widget
     * @param  {String} slug The slug of the widget to return
     * @return {Object}
     */
    base.getWidget = function(slug) {

        var i, x;

        for (i = base.widgets.length - 1; i >= 0; i--) {
            for (x = base.widgets[i].widgets.length - 1; x >= 0; x--) {
                if (base.widgets[i].widgets[x].slug === slug) {
                    return base.widgets[i].widgets[x];
                }
            }
        }

        return false;
    };

    // --------------------------------------------------------------------------

    /**
     * Fetch the widgete ditor from the API
     * @param  {String} slug The slug of the widget to render the editor for
     * @param  {Object} data Data to prefill the editor with
     * @return {Object}      A jQuery promise
     */
    base.getWidgetEditor = function(slug, data) {

        var deferred;

        deferred = $.Deferred();

        _nails_api.call({
            'controller': 'cms/widgets',
            'method': 'editor',
            'action': 'POST',
            'data': {
                'slug': slug,
                'data': data
            },
            'success': function(data) {

                base.log('Succesfully fetched widget editor from the server.');
                deferred.resolve(data.editor);
            },
            'error': function(data) {

                var _data;

                try {

                    _data = JSON.parse(data.responseText);

                } catch (e) {

                    _data = {
                        'status': 500,
                        'error': 'An unknown error occurred.'
                    };
                }
                base.warn('Failed to load widget editor from the server with error: ', _data.error);
                deferred.reject(_data);
            }
        });

        return deferred.promise();
    };

    // --------------------------------------------------------------------------

    /**
     * Return the data contents of an area
     * @param  {String} area The area to get data for
     * @return {Array}
     */
    base.getAreaData = function(area) {

        area = area || base.defaultArea;
        base.log('Getting editor data for area "' + area + '"');

        //  If the editor is open then refresh the area
        if (base.isOpen && area === base.activeArea) {
            base.log('Editor is open with selected area, refreshing data');
            base.widgetData[area] = base.getActiveData();
        }

        return base.widgetData[area] || null;
    };

    // --------------------------------------------------------------------------

    /**
     * Sets the data contents of an area
     * @param  {String} area The area to set data for
     * @param  {Array}  data  The data to set
     * @return {Object}
     */
    base.setAreaData = function(area, data) {

        area = area || base.defaultArea;
        base.log('Setting editor data for area "' + area + '"', data);
        base.widgetData[area] = data;

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Returns the data of widgets in the currently active editor
     * @return {Array}
     */
    base.getActiveData = function() {

        var widgets, out = [];

        base.log('Getting active editor\'s data');

        widgets = base.sections.body.find('> ul > .widget');
        widgets.each(function() {

            out.push({
                'slug': $(this).data('slug'),
                'data': $(this).find(':input').serializeObject()
            });
        });

        return out;
    };

    // --------------------------------------------------------------------------

    /**
     * Show a confirmation dialog
     * @param  {String} message The message/question to show
     * @param  {String} title   The title of the dialog
     * @return {Object}         A jQuery Promise
     */
    base.confirm = function(message, title) {

        message = message ? message : 'Are you sure you wish to complete this action?';
        title   = title ? title : 'Are you sure?';

        var deferred = $.Deferred();

        $('<div>')
            .text(message)
            .dialog({
                'title': title,
                'resizable': false,
                'draggable': false,
                'modal': true,
                'dialogClass': 'group-cms widgeteditor-alert',
                'buttons': {

                    'OK': function() {

                        $(this).dialog('close');
                        deferred.resolve();
                    },
                    'Cancel': function() {

                        $(this).dialog('close');
                        deferred.reject();
                    }
                }
            });

        return deferred.promise();
    };

    // --------------------------------------------------------------------------

    /**
     * Write a log to the console
     * @param  {String} message The message to log
     * @param  {Mixed}  payload Any additional data to display in the console
     * @return {Object}
     */
    base.log = function(message, payload) {

        if (typeof(console.log) === 'function') {

            if (payload !== undefined) {

                console.log('CMS Widget Editor:', message, payload);

            } else {

                console.log('CMS Widget Editor:', message);
            }
        }

        return base;
    };

    // --------------------------------------------------------------------------

    /**
     * Write a warning to the console
     * @param  {String} message The message to warn
     * @param  {Mixed}  payload Any additional data to display in the console
     * @return {Object}
     */
    base.warn = function(message, payload) {

        if (typeof(console.warn) === 'function') {

            if (payload !== undefined) {

                console.warn('CMS Widget Editor:', message, payload);

            } else {

                console.warn('CMS Widget Editor:', message);
            }
        }

        return base;
    };

    // --------------------------------------------------------------------------

    return base.__construct();
};
