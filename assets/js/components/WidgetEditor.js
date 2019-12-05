/* global _nails, window._nails_admin */
class WidgetEditor {

    /**
     * Construct WidgetEditor
     */
    constructor(adminController) {

        this.log('Constructing');
        this.adminController = adminController;
        this.instantiated = false;
        this.$btns = $('.field.cms-widgets .open-editor');

        this.setupListeners();

        //  @todo (Pablo - 2019-12-05) - Support re-initing?
        this.adminController.onRefreshUi(() => {
            if (!this.instantiated) {
                this.init();
            }
        });
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the CMS widget editor
     * @return {WidgetEditor} The object itself, for chaining
     */
    init() {

        this.log('Initialising Widget Editor');

        //  Only instantiate once
        this.instantiated = true;

        //  Disable all buttons until the widgeteditor is ready
        this.$btns.prop('disabled', true);

        /**
         * Give other items a chance to check if the widget editor is ready or not
         * @type {Boolean}
         */
        this.isEditorReady = false;

        // --------------------------------------------------------------------------

        /**
         * Whether the editor is currently open
         * @type {Boolean}
         */
        this.isEditorOpen = false;

        // --------------------------------------------------------------------------

        /**
         * An array of the available widgets
         * @type {Array}
         */
        this.widgets = [];

        // --------------------------------------------------------------------------

        /**
         * An array of actions to render
         * @type {Array}
         */
        this.actions = [
            {
                'label': '<i class="fa fa-lg fa-check"></i>',
                'type': 'success',
                'callback': () => {
                    this.actionClose();
                }
            }
        ];

        // --------------------------------------------------------------------------

        /**
         * An array of the data to use when rendering widget areas
         * @type {Array}
         */
        this.widgetData = {};

        // --------------------------------------------------------------------------

        /**
         * The main editor container
         */
        this.container = null;

        // --------------------------------------------------------------------------

        /**
         * The individual editor sections
         */
        this.sections = {};

        // --------------------------------------------------------------------------

        /**
         * The currently active area
         * @type {String}
         */
        this.activeArea = '';

        // --------------------------------------------------------------------------

        /**
         * The element which triggered the widgeteditor
         * @type {null}
         */
        this.activeButton = null;

        // --------------------------------------------------------------------------

        /**
         * The name/slug of the default area
         * @type {String}
         */
        this.defaultArea = 'default';

        // --------------------------------------------------------------------------

        /**
         * Holds the timeout when searching
         * @type {Number}
         */
        this.searchTimeout = null;

        // --------------------------------------------------------------------------

        /**
         * The delay before searching widgets, in milliseconds
         * @type {Number}
         */
        this.searchDelay = 150;

        // --------------------------------------------------------------------------

        /**
         * A map of useful keyboard keys
         * @type {{ESC: number}}
         */
        this.keymap = {
            'ESC': 27
        };

        // --------------------------------------------------------------------------

        /**
         * Any callbacks to apply to widgets on initialisation
         * @type {Array}
         */
        this.widgetInitCallback = [];

        //  Inject markup
        this.generateMarkup();
        this.bindEvents();

        // Fetch and render available widgets
        this.loadSidebarWidgets()
            .done(() => {
                this.renderSidebarWidgets();
                this.isEditorReady = true;
                this.log('Widget Editor ready');
                $(this).trigger('widgeteditor-ready');
            });

        //  Default editor elements
        this
            .addWidgetInitCallback((widgetDom) => {

                //  @todo (Pablo - 2019-12-05) - Try and move these to admin components and use refreshUi event
                window._nails.addStripes();
                window._nails_admin.initSelect2();
                window._nails_admin.initToggles();
                window._nails.initTipsy();
                window._nails_admin.buildWysiwyg('basic', widgetDom);
                window._nails_admin.buildWysiwyg('default', widgetDom);

                this.adminController.refreshUi();
            });

        //  Populate the editor with existing areas
        this.$btns
            .each((index, element) => {

                //  Look for the associated input
                let key = $(element).data('key');
                let input = $(element).siblings('textarea.widget-data');

                if (input.length) {
                    try {
                        let widgetData = JSON.parse(input.val());
                        this.setAreaData(key, widgetData);
                    } catch (e) {
                        this.warn('Failed to parse JSON data');
                        this.warn(e.message);
                    }
                }
            });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Binds the listeners
     * @return {void}
     */
    setupListeners() {

        this.log('Setting up listeners');

        this.$btns
            .on('click', (e) => {
                if (this.isReady()) {
                    this.activeButton = $(e.currentTarget);
                    let key = this.activeButton.data('key');
                    this.log('Opening Editor for area: ' + key);
                    this.show(key);
                } else {
                    this.warn('Widget editor not ready');
                }
                return false;
            });

        $(this)
            .on('widgeteditor-ready', () => {
                this.$btns.prop('disabled', false);
            })
            .on('widgeteditor-close', () => {
                if (this.activeButton) {

                    this.log('Editor Closing, getting area data and saving to input');
                    let input = this.activeButton.siblings('textarea.widget-data');
                    let data = this.getAreaData(this.activeButton.data('key'));
                    let dataString = JSON.stringify(data);

                    if (input.length) {
                        input.val(dataString).trigger('change');
                    }
                }
            });
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the this markup for the editor
     * @return {WidgetEditor} The object itself, for chaining
     */
    generateMarkup() {

        let item;

        if (this.container === null) {

            this.log('Injecting editor markup');

            this.container = $('<div>').addClass('group-cms widgeteditor');
            this.sections = {
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
                ),
                'preview': $('<div>')
                    .addClass('widgeteditor-preview')
                    .html('<img width="250"><small></small>')
            };

            //  Add search input
            item = $('<input>').attr('type', 'search').attr('placeholder', 'Search widgets');
            this.sections.search.append(item);

            //  Glue it all together
            this.container
                .append(this.sections.header)
                .append(this.sections.actions)
                .append(this.sections.search)
                .append(this.sections.widgets)
                .append(this.sections.body)
                .append(this.sections.preview);

            $('body').append(this.container);

            //  Render any actions and widgets
            this.renderSidebarWidgets();
            this.renderActions();

        } else {
            this.warn('Editor already generated');
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Bind to various events
     * @return {WidgetEditor} The object itself, for chaining
     */
    bindEvents() {

        let actionIndex, groupIndex;

        //  Actions
        this.sections.actions.on('click', '.action', (e) => {

            let $el = $(e.currentTarget);
            actionIndex = $el.data('action-index');
            this.log('Action clicked', actionIndex);

            if (this.actions[actionIndex]) {
                if (typeof this.actions[actionIndex].callback === 'function') {
                    this.actions[actionIndex].callback.call(this);
                }
            } else {
                this.warn('"' + actionIndex + '" is not a valid action.');
            }
            return false;
        });

        //  Widgets
        this.sections.widgets
            .on('click', '.widget-group', (e) => {

                let $el = $(e.currentTarget);
                this.log('Toggling visibility of group\'s widgets.');

                let isOpen;

                if ($el.hasClass('closed')) {
                    $el.removeClass('closed');
                    isOpen = true;
                } else {
                    $el.addClass('closed');
                    isOpen = false;
                }

                groupIndex = $el.data('group');
                $('.widget-group-' + groupIndex, this.sections.widgets).toggleClass('hidden');

                //  Save the state to localstorage
                window._nails_admin.localStorage
                    .set(
                        'widgeteditor-group-' + groupIndex + '-hidden',
                        !isOpen
                    );
            })
            .on('mouseenter', '.widget', (e) => {
                let $el = $(e.currentTarget);
                let img = $el.data('preview');
                let description = $el.data('description');
                if (img || description) {
                    this.sections.preview
                        .css('top', ($el.offset().top + 10) + 'px')
                        .addClass('is-shown');
                    if (img) {
                        this.sections.preview
                            .find('img')
                            .attr('src', img)
                            .show();
                    } else {
                        this.sections.preview
                            .find('img')
                            .hide();
                    }
                    this.sections.preview
                        .find('small')
                        .html(description);
                }
            })
            .on('mouseleave', '.widget', () => {
                this.sections.preview
                    .removeClass('is-shown');
            });

        //  Search
        this.sections.search.on('keyup', () => {

            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            this.searchTimeout = setTimeout(() => {

                let term = this.sections.search.find('input').val().trim();
                this.searchWidgets(term);

            }, this.searchDelay);
        });

        //  Body
        this.sections.body.on('click', '.action-remove', (e) => {

            let $el = $(e.currentTarget);
            let domElement = $el.closest('.widget');
            let widget = this.getWidget(domElement.data('slug'));

            this.confirm('Are you sure you wish to remove this "' + widget.label + '" widget from the interface?')
                .done(() => {
                    this.log('Removing Widget');
                    domElement.remove();
                    widget.callbacks.removed.call(this, domElement);
                });
        });

        this.sections.body.on('click', '.action-refresh-editor', (e) => {

            let $el = $(e.currentTarget);
            let widget = $el.closest('.widget');
            let slug = widget.data('slug');
            let data = widget.find(':input').serializeObject();

            widget
                .addClass('editor-loading')
                .find('.editor-target')
                .removeClass('alert alert-danger')
                .empty()
                .text('Widget Loading...');

            this.setupWidgetEditor(slug, data, widget);

        });

        //  Keyboard shortcuts
        $(document).on('keyup', (e) => {
            if (this.isOpen() && e.which === this.keymap.ESC) {
                this.actionClose();
            }
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Load the available widgets from the server
     * @return {Object} Deferred
     */
    loadSidebarWidgets() {

        let deferred, i, x, key, assetsCss, assetsJs;

        this.log('Fetching available CMS widgets');

        deferred = $.Deferred();

        window._nails_api.call({
            'controller': 'cms/widgets',
            'method': 'index',
            'success': (response) => {

                this.log('Succesfully fetched widgets from the server.');
                this.widgets = response.data.widgets;

                //  Make the callbacks on each widget callable
                for (i = this.widgets.length - 1; i >= 0; i--) {
                    for (x = this.widgets[i].widgets.length - 1; x >= 0; x--) {
                        for (key in this.widgets[i].widgets[x].callbacks) {
                            if (this.widgets[i].widgets[x].callbacks.hasOwnProperty(key)) {
                                /* jshint ignore:start */
                                this.widgets[i].widgets[x].callbacks[key] = new Function(
                                    'domElement',
                                    this.widgets[i].widgets[x].callbacks[key]
                                );
                                /* jshint ignore:end */
                            }
                        }
                    }
                }

                //  Inject any assets to load into the  DOM
                assetsCss = '';
                for (i = 0; i < response.data.assets.css.length; i++) {
                    assetsCss += response.data.assets.css[i] + '\n';
                }

                assetsJs = '';
                for (i = 0; i < response.data.assets.js.length; i++) {
                    assetsJs += response.data.assets.js[i] + '\n';
                }

                $('head').append(assetsCss);
                $('body').append(assetsJs);

                deferred.resolve();
            },
            'error': () => {
                this.warn('Failed to load widgets from the server.');
                deferred.reject();
            }
        });

        return deferred;
    }

    // --------------------------------------------------------------------------

    /**
     * Render the widgets
     * @return {WidgetEditor} The object itself, for chaining
     */
    renderSidebarWidgets() {

        this.sections.widgets.empty();

        let i, x, label, toggle, container, icon;

        for (i = 0; i < this.widgets.length; i++) {

            //  Widget Group
            label = $('<span>').text(this.widgets[i].label);
            toggle = $('<i>').addClass('icon fa fa-chevron-up');
            container = $('<div>').addClass('widget-group').data('group', i).append(label).append(toggle);

            //  Hidden by default?
            let hidden = window._nails_admin.localStorage.get('widgeteditor-group-' + i + '-hidden');
            if (hidden === true || hidden === null) {
                container.addClass('closed');
            }

            this.sections.widgets.append(container);

            //  Individual widgets
            for (x = 0; x < this.widgets[i].widgets.length; x++) {

                icon = $('<i>').addClass('icon fa ' + this.widgets[i].widgets[x].icon);
                label = $('<span>').text(this.widgets[i].widgets[x].label);
                container = $('<div>')
                    .addClass('widget widget-group-' + i)
                    .data('group', i)
                    .data('slug', this.widgets[i].widgets[x].slug)
                    .data('preview', this.widgets[i].widgets[x].screenshot)
                    .data('description', this.widgets[i].widgets[x].description)
                    .data('keywords', this.widgets[i].widgets[x].keywords);

                if (hidden === true || hidden === null) {
                    container.addClass('hidden');
                }

                container
                    .append(icon)
                    .append(label);

                this.sections.widgets.append(container);
            }
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Search widgets
     * @param {String} term The search term
     * @return {void}
     */
    searchWidgets(term) {

        let keywords, i, regex, group;

        if (term.length > 0) {

            this.log('Filtering widgets by term "' + term + '"');

            //  Hide widgets which do not match the search term
            this.sections.widgets.find('.widget').each((index, element) => {

                let $el = $(element);
                keywords = $el.data('keywords').split(',');
                for (i = keywords.length - 1; i >= 0; i--) {

                    regex = new RegExp(term, 'gi');
                    if (regex.test(keywords[i])) {

                        $el.removeClass('search-hide');
                        $el.addClass('search-show');
                        return;

                    } else {

                        $el.removeClass('search-show');
                        $el.addClass('search-hide');
                    }
                }
            });

            //  Hide group headings which no longer have any widgets showing
            this.sections.widgets.find('.widget-group').each((index, element) => {
                let $el = $(element);
                group = $el.data('group');
                if ($('.widget-group-' + group + '.search-show').length) {
                    $el.removeClass('search-hide');
                    $el.addClass('search-show');
                } else {
                    $el.removeClass('search-show');
                    $el.addClass('search-hide');
                }
            });

        } else {

            this.log('Restoring widgets');
            this.sections.widgets.find('.widget, .widget-group').removeClass('search-show search-hide');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Add an action to the header
     * @param {String}   label    The label of the button
     * @param {String}   type     The type of button (e.g., primary)
     * @param {Function} callback A callback to call when the button is clicked
     * @return {WidgetEditor} The object itself, for chaining
     */
    addAction(label, type, callback) {

        this.log('Adding action "' + label + '"');
        this.actions.push({
            'label': label,
            'type': type,
            'callback': callback
        });
        this.renderActions();
        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Render the actions
     * @return {WidgetEditor} The object itself, for chaining
     */
    renderActions() {

        let i, action;

        this.sections.actions.empty();

        for (i = this.actions.length - 1; i >= 0; i--) {
            action = $('<a>')
                .addClass('action btn btn-sm btn-' + this.actions[i].type)
                .data('action-index', i)
                .html(this.actions[i].label);

            this.sections.actions.append(action);
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Show the widget editor and populate with the data for a particular area
     * @param  {String} area Load widgets for this area
     * @return {WidgetEditor} The object itself, for chaining
     */
    show(area) {

        area = area || this.defaultArea;

        this.log('Showing Editor for "' + area + '"');

        this.activeArea = area;

        //  Build the widgets for this area
        this.sections.body.find('> ul').empty();

        if (this.widgetData[area] && this.widgetData[area].length) {

            this.log('Adding previous widgets');
            let requestDom = [];
            let requestData = [];

            for (let key in this.widgetData[area]) {
                if (this.widgetData[area].hasOwnProperty(key)) {

                    //  Add to the DOM, addWidget will populate
                    let widgetDom = $('<div>').addClass('widget');
                    this.sections.body.find('> ul').append(widgetDom);

                    this.addWidget(
                        this.widgetData[area][key].slug,
                        this.widgetData[area][key].data,
                        widgetDom,
                        true
                    );

                    // --------------------------------------------------------------------------

                    requestDom.push(widgetDom);
                    requestData.push(this.widgetData[area][key]);
                }
            }

            this.log('Setting up all widgets');

            //  Send request off for all widget editors
            window._nails_api.call({
                'controller': 'cms/widgets',
                'method': 'editors',
                'action': 'POST',
                'data': {
                    'data': JSON.stringify(requestData)
                },
                'success': (response) => {

                    let i, widget;

                    this.log('Succesfully fetched widget editors from the server.');
                    for (i = 0; i < response.data.length; i++) {

                        if (!response.data[i].error) {

                            this.setupWidgetEditorOk(
                                requestDom[i],
                                response.data[i].editor
                            );

                        } else {

                            this.setupWidgetEditorFail(
                                requestDom[i],
                                response.data[i].editor
                            );
                        }
                    }

                    //  Now instantiate everything
                    this.initWidgetEditorElements();

                    //  Finally, call the "dropped" callback on each widget
                    for (i = 0; i < response.data.length; i++) {

                        widget = this.getWidget(response.data[i].slug);
                        widget.callbacks.dropped.call(this, requestDom[i]);
                    }

                },
                'error': (data) => {

                    let _data;

                    try {

                        _data = JSON.parse(data.responseText);

                    } catch (e) {

                        _data = {
                            'status': 500,
                            'error': 'An unknown error occurred.'
                        };
                    }
                    this.warn('Failed to load widget editors from the server with error: ', _data.error);
                    //  @todo show an alert/dialog
                }
            });

        }

        //  Make things draggable and sortable
        this.draggableConstruct();
        this.sortableConstruct();

        //  Show the editor
        this.container.addClass('active');
        this.isEditorOpen = true;

        //  Prevent scrolling on the body
        $('body').addClass('noscroll');

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Close the editor
     *@return {WidgetEditor} The object itself, for chaining
     */
    close() {

        this.log('Closing Editor');
        this.container.removeClass('active');
        this.isEditorOpen = false;
        this.activeArea = '';

        //  Destroy all sortables and draggables
        this.draggableDestroy();
        this.sortableDestroy();

        //  Allow body scrolling
        $('body').removeClass('noscroll');

        $(this).trigger('widgeteditor-close');

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set up draggables
     * @return {WidgetEditor} The object itself, for chaining
     */
    draggableConstruct() {

        this.sections.widgets.find('.widget').draggable({
            'helper': 'clone',
            'connectToSortable': this.sections.body.find('> ul'),
            'appendTo': this.container,
            'zIndex': 100
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Destroy draggables
     * @return {WidgetEditor} The object itself, for chaining
     */
    draggableDestroy() {

        this.sections.widgets
            .find('.widget.ui-draggable')
            .draggable('destroy');

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Set up sortables
     * @return {WidgetEditor} The object itself, for chaining
     */
    sortableConstruct() {

        this.sections.body.find('> ul').sortable({
            placeholder: 'sortable-placeholder',
            handle: '.icon',
            axis: 'y',
            start: (e, ui) => {
                //  If the element's group is hidden, but revealed by search then
                //  the helper will not be visible, remove the classes which hide things
                ui.helper.removeClass('hidden search-show search-hide');

                ui.placeholder.height(ui.helper.outerHeight());
                window._nails_admin.destroyWysiwyg('basic', ui.helper);
                window._nails_admin.destroyWysiwyg('default', ui.helper);
            },
            receive: (e, ui) => {
                let sourceWidget, targetWidget, widgetSlug;

                sourceWidget = ui.item;
                targetWidget = ui.helper;
                widgetSlug = sourceWidget.data('slug');

                //  Remove the hidden class  - if a group is hidden (but revealed
                //  by search) then it'll not show when dropped.
                targetWidget.removeClass('hidden search-show search-hide');

                this.addWidget(widgetSlug, null, targetWidget);

                //  Allow auto sizing
                targetWidget.removeAttr('style');
            },
            stop: (e, ui) => {

                window._nails_admin.buildWysiwyg('basic', ui.helper);
                window._nails_admin.buildWysiwyg('default', ui.helper);
            }
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Destroy sortables
     * @return {WidgetEditor} The object itself, for chaining
     */
    sortableDestroy() {

        let sortable = this.sections.body.find('> ul.ui-sortable');

        if (sortable.length > 0) {
            sortable.sortable('destroy');
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Add a widget to the interface
     * @param  {String|Object} widget    The widget slug, or the widget Object
     * @param  {Object}        data      Any data to populate the editor with
     * @param  {Object}        widgetDom The widget's DOM element
     * @param  {Boolean}       skipSetup Whether to skip setup
     * @return {WidgetEditor} The object itself, for chaining
     */
    addWidget(widget, data, widgetDom, skipSetup) {

        if (typeof widget === 'string') {
            widget = this.getWidget(widget);
        }

        if (widget) {

            this.log('Adding Widget "' + widget.slug + '" with data:', data);

            //  Populate the dom element with the widget template
            $(widgetDom)
                .empty()
                .data('slug', widget.slug)
                .addClass('editor-loading')
                .html(Mustache.render(this.sections.widget.html(), widget));

            //  Setup the widget's editor
            if (!skipSetup) {
                this.setupWidgetEditor(widget.slug, data, widgetDom);
            }

        } else {
            this.warn('Attempted to add an invalid widget');

            //  Show user feedback
            let $error = $('<p>')
                .addClass('alert alert-danger')
                .html('Widget "' + widget + '" does not exist. <a href="#" class="action-remove">Remove?</a>');

            $(widgetDom)
                .empty()
                .addClass('editor-missing')
                .append($error);
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Setup the contents of a widget's editor
     * @param {String} slug The widget's slug
     * @param {Object} data The widget's data
     * @param {Object} widgetDom The widget's DOM element
     * @return {void}
     */
    setupWidgetEditor(slug, data, widgetDom) {

        this.getWidgetEditor(slug, data)
            .done((data) => {

                this.log('Editor Received');
                this.setupWidgetEditorOk(widgetDom, data);

                //  Now the markup is in place we need to ensure that things look the part
                this.initWidgetEditorElements(widgetDom);

                //  Finally, call the "dropped" callback
                let widget = this.getWidget(slug);
                widget.callbacks.dropped.call(this, widgetDom);
            })
            .fail((data) => {
                this.setupWidgetEditorFail(widgetDom, data.error);
            });
    }

    // --------------------------------------------------------------------------

    /**
     * Callback when the widget editor setup is successful
     * @param {Object} widgetDom The widget's DOM element
     * @param {String} editorData The widget's editor HTML
     * @return {void}
     */
    setupWidgetEditorOk(widgetDom, editorData) {
        widgetDom
            .removeClass('editor-loading')
            .find('.editor-target')
            .html(editorData);
    }

    // --------------------------------------------------------------------------

    /**
     * Callback when the widget editor setup fails
     * @param {Object} widgetDom The widget's DOM element
     * @param {String} error The error string
     * @return {void}
     */
    setupWidgetEditorFail(widgetDom, error) {
        widgetDom
            .removeClass('editor-loading')
            .find('.editor-target')
            .addClass('alert alert-danger')
            .html('<strong>Error:</strong> ' + error);
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a new callback to be fired when a widget is instatiated
     * @param callback
     * @returns {WidgetEditor}
     */
    addWidgetInitCallback(callback) {
        this.widgetInitCallback.push(callback);
        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Initialise all the items in a widget editor
     * @param {Object} [widgetDom] The widget's DOM element
     * @return {void}
     */
    initWidgetEditorElements(widgetDom) {
        for (let i = 0, j = this.widgetInitCallback.length; i < j; i++) {
            this.widgetInitCallback[i].call(widgetDom);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Return a single widget
     * @param  {String} slug The slug of the widget to return
     * @return {Object|Boolean} The widget object on success, false on failure
     */
    getWidget(slug) {

        let i, x;

        for (i = this.widgets.length - 1; i >= 0; i--) {
            for (x = this.widgets[i].widgets.length - 1; x >= 0; x--) {
                if (this.widgets[i].widgets[x].slug === slug) {
                    return this.widgets[i].widgets[x];
                }
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch the widget editor from the API
     * @param  {String} slug The slug of the widget to render the editor for
     * @param  {Object} data Data to pre-fill the editor with
     * @return {Object}      A jQuery promise
     */
    getWidgetEditor(slug, data) {

        let deferred;

        deferred = $.Deferred();

        window._nails_api.call({
            'controller': 'cms/widgets',
            'method': 'editor',
            'action': 'POST',
            'data': {
                'slug': slug,
                'data': data
            },
            'success': (response) => {
                this.log('Successfully fetched widget editor from the server.');
                deferred.resolve(response.data.editor);
            },
            'error': (data) => {

                let _data;

                try {
                    _data = JSON.parse(data.responseText);
                } catch (e) {
                    _data = {
                        'status': 500,
                        'error': 'An unknown error occurred.'
                    };
                }
                this.warn('Failed to load widget editor from the server with error: ', _data.error);
                deferred.reject(_data);
            }
        });

        return deferred.promise();
    }

    // --------------------------------------------------------------------------

    /**
     * Return the data contents of an area
     * @param  {String} area The area to get data for
     * @return {Array|Boolean} The widget area data on success, false on failure
     */
    getAreaData(area) {

        area = area || this.defaultArea;
        this.log('Getting editor data for area "' + area + '"');

        //  If the editor is open then refresh the area
        if (this.isOpen() && area === this.activeArea) {
            this.log('Editor is open with selected area, refreshing data');
            this.widgetData[area] = this.getActiveData();
        }

        let data = this.widgetData[area];

        if (Array.isArray(data) && data.length === 0) {
            data = null;
        }

        return data || null;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the data contents of an area
     * @param  {String} area The area to set data for
     * @param  {Array}  data  The data to set
     * @return {WidgetEditor} The object itself, for chaining
     */
    setAreaData(area, data) {

        area = area || this.defaultArea;
        this.log('Setting editor data for area "' + area + '"', data);
        this.widgetData[area] = data;

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the data of widgets in the currently active editor
     * @return {Array} The active editor's data
     */
    getActiveData() {

        let widgets, out = [];

        this.log('Getting active editor\'s data');

        widgets = this.sections.body.find('> ul > .widget');
        widgets.each((index, element) => {
            out.push({
                'slug': $(element).data('slug'),
                'data': $(element).find(':input').serializeObject()
            });
        });

        return out;
    }

    // --------------------------------------------------------------------------

    /**
     * Get's the active editor's data and closes the editor
     * @return {void}
     */
    actionClose() {
        this.widgetData[this.activeArea] = this.getActiveData();
        this.close();
    }

    // --------------------------------------------------------------------------

    /**
     * Show a confirmation dialog
     * @param  {String} [message] The message/question to show
     * @param  {String} [title]   The title of the dialog
     * @return {Object}         A jQuery Promise
     */
    confirm(message, title) {

        message = message ? message : 'Are you sure you wish to complete this action?';
        title = title ? title : 'Are you sure?';

        let deferred = $.Deferred();

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
    }

    // --------------------------------------------------------------------------

    /**
     * Write a log to the console
     * @param  {String} message The message to log
     * @param  {mixed}  [payload] Any additional data to display in the console
     * @return {WidgetEditor} The object itself, for chaining
     */
    log(message, payload) {

        if (typeof (console.log) === 'function') {
            if (payload !== undefined) {
                console.log('CMS Widget Editor:', message, payload);
            } else {
                console.log('CMS Widget Editor:', message);
            }
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Write a warning to the console
     * @param  {String} message The message to warn
     * @param  {mixed}  [payload] Any additional data to display in the console
     * @return {WidgetEditor} The object itself, for chaining
     */
    warn(message, payload) {

        if (typeof (console.warn) === 'function') {
            if (payload !== undefined) {
                console.warn('CMS Widget Editor:', message, payload);
            } else {
                console.warn('CMS Widget Editor:', message);
            }
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether the widget editor is ready or not
     * @return {Boolean}
     */
    isReady() {
        return this.isEditorReady;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether the widget editor is open or not
     * @return {Boolean}
     */
    isOpen() {
        return this.isEditorOpen;
    };
}

export default WidgetEditor;
