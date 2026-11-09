import { writeJsonField } from './json-field.js';

/**
 * Opens the widget editor for a widget field or a page area.
 *
 * The editor is the WidgetEditor overlay. This class reads the stored JSON, hands the areas to that
 * overlay, and writes edits back. PageEditor adds header actions and listens for the ready, change
 * and close events.
 */
class WidgetEditor {

    /**
     * @param adminController
     */
    constructor(adminController) {

        this.adminController = adminController;
        this.adminController.log('Constructing');
        this.instantiated = false;
        this.$btns = $('.open-editor').addClass('processed');
        this.widgetData = {};
        this.actions = [];
        this.isEditorReady = false;
        this.isEditorOpen = false;
        this.v2Open = false;
        this.activeArea = '';
        this.activeButton = null;
        this.defaultArea = 'default';

        this.setupListeners();

        this.adminController.onRefreshUi((e, domElement) => {
            if (!this.instantiated) {
                this.init();
            } else {
                this.initNewButtons(domElement);
            }
        });
    }

    // --------------------------------------------------------------------------

    /**
     * @return {WidgetEditor}
     */
    init() {

        this.adminController.log('Initialising Widget Editor');
        this.instantiated = true;
        this.$btns.prop('disabled', true);
        this.populateWidgetDataFromButtons(this.$btns);

        // The next turn, so a listener registered in the same refresh (PageEditor) is in place first.
        setTimeout(() => {
            this.isEditorReady = true;
            $(this).trigger('widgeteditor-ready');
        }, 0);

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param domElement
     */
    initNewButtons(domElement) {

        let $btns = $('.open-editor:not(.processed)', domElement).addClass('processed');
        if ($btns.length) {
            this.adminController.log(`Found ${$btns.length} new buttons`);
            this
                .setupButtonListeners($btns)
                .populateWidgetDataFromButtons($btns);
            if (this.isEditorReady) {
                $btns.prop('disabled', false);
            }
            for (let i = 0; i < $btns.length; i++) {
                this.$btns.push($btns[i]);
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * @return {WidgetEditor}
     */
    setupListeners() {

        this.adminController.log('Setting up listeners');
        this.setupButtonListeners(this.$btns);
        this.setupGlobalListeners();

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @return {WidgetEditor}
     */
    setupGlobalListeners() {

        $(this)
            .on('widgeteditor-ready', () => {
                this.$btns.prop('disabled', false);
            })
            .on('widgeteditor-close', () => {
                if (!this.activeButton) {
                    return;
                }

                this.adminController.log('Editor Closing, getting area data and saving to input');
                let input = this.activeButton.siblings('textarea.widget-data');
                let data = this.getAreaData(this.activeButton.data('key'));

                if (input.length) {
                    writeJsonField(input.get(0), data);
                }
            });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param $btns
     * @return {WidgetEditor}
     */
    setupButtonListeners($btns) {

        $btns.on('click', (e) => {
            if (this.isReady()) {
                this.activeButton = $(e.currentTarget);
                let key = this.activeButton.data('key');
                this.adminController.log('Opening Editor for area: ' + key);
                this.show(key);
            } else {
                this.adminController.warn('Widget editor not ready');
            }
            return false;
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param $btns
     * @return {WidgetEditor}
     */
    populateWidgetDataFromButtons($btns) {

        $btns.each((index, element) => {
            let key = $(element).data('key');
            let input = $(element).siblings('textarea.widget-data');

            if (input.length) {
                try {
                    this.setAreaData(key, JSON.parse(input.val()));
                } catch (e) {
                    this.adminController.warn('Failed to parse JSON data');
                    this.adminController.warn(e.message);
                }
            }
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} label
     * @param {String} type
     * @param {Function} callback
     * @return {WidgetEditor}
     */
    addAction(label, type, callback) {

        this.adminController.log('Adding action "' + label + '"');
        this.actions.push({
            label: label,
            type: type,
            callback: callback
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} area
     * @return {WidgetEditor}
     */
    show(area) {

        area = area || this.defaultArea;

        if (!window.NAILS_CMS_WIDGET_EDITOR) {
            this.adminController.warn('The widget editor failed to load');
            return this;
        }

        this.openV2(area);

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} [area]
     * @return {Array|null}
     */
    getAreaData(area) {

        area = area || this.defaultArea;
        this.adminController.log('Getting editor data for area "' + area + '"');

        let data = this.widgetData[area];
        if (Array.isArray(data) && data.length === 0) {
            data = null;
        }

        return data || null;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} area
     * @param {Array|null} data
     * @return {WidgetEditor}
     */
    setAreaData(area, data) {

        area = area || this.defaultArea;
        this.adminController.log('Setting editor data for area "' + area + '"', data);
        this.widgetData[area] = data;

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Closes the open editor
     */
    actionClose() {

        if (this.v2Open && window.NAILS_CMS_WIDGET_EDITOR) {
            window.NAILS_CMS_WIDGET_EDITOR.close();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * @return {Array}
     */
    areasForV2() {

        let buttons = Array.from(document.querySelectorAll('.open-editor'));
        if (buttons.length) {
            return buttons.map((button) => this.describeAreaButton(button, button.dataset.key));
        }

        let pageButtons = Array.from(document.querySelectorAll('.js-cms-page-area')).filter((button) => {
            let group = button.closest('.template-area');
            return !group || group.style.display !== 'none';
        });

        if (pageButtons.length) {
            return pageButtons.map((button) => this.describeAreaButton(button, button.dataset.area));
        }

        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * @param {HTMLElement} button
     * @param {String} slug
     * @return {Object}
     */
    describeAreaButton(button, slug) {

        let rules = {};
        if (button.dataset.cmsRules) {
            try {
                rules = JSON.parse(button.dataset.cmsRules);
            } catch (e) {
                rules = {};
            }
        }

        let data = this.widgetData[slug];

        return {
            slug: slug,
            label: this.areaLabel(button, slug),
            rules: rules,
            data: Array.isArray(data) ? data : []
        };
    }

    // --------------------------------------------------------------------------

    /**
     * The widget button's caption is always "Open Widget Editor", so the area name comes from its field label.
     * A page-area button already carries the area title.
     *
     * @param {HTMLElement} button
     * @param {String} slug
     * @return {String}
     */
    areaLabel(button, slug) {

        if (button.classList.contains('open-editor')) {
            let host = button.closest('label');
            if (host) {
                let copy = host.cloneNode(true);
                copy.querySelectorAll('textarea, button, script, style').forEach((el) => el.remove());
                let text = copy.textContent.replace(/\s+/g, ' ').trim().replace(/\*?\s*required\b.*/, '').trim();
                if (text) {
                    return text;
                }
            }
        }

        return button.textContent.replace(/\s+/g, ' ').trim() || slug;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} area
     */
    openV2(area) {

        let areas = this.areasForV2();
        if (!areas.some((item) => item.slug === area) && area) {
            let data = this.widgetData[area];
            areas.push({
                slug: area,
                label: area,
                rules: {},
                data: Array.isArray(data) ? data : []
            });
        }

        this.activeArea = area;
        this.isEditorOpen = true;
        this.v2Open = true;

        window.NAILS_CMS_WIDGET_EDITOR.open({
            area: area,
            areas: areas,
            actions: this.actions,
            onChange: (slug, data) => {
                let stored = Array.isArray(data) && data.length === 0 ? null : data;
                this.setAreaData(slug, stored);
                this.writeAreaInput(slug, stored);
                $(this).trigger('widgeteditor-change');
            },
            onClose: () => {
                this.v2Open = false;
                this.isEditorOpen = false;
                $(this).trigger('widgeteditor-close');
            }
        });
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} slug
     * @param {Array|null} data
     */
    writeAreaInput(slug, data) {

        let button = document.querySelector('.open-editor[data-key="' + CSS.escape(slug) + '"]');
        if (!button) {
            return;
        }

        let input = button.previousElementSibling;
        if (!input || !input.classList.contains('widget-data')) {
            return;
        }

        writeJsonField(input, data);
    }

    // --------------------------------------------------------------------------

    /**
     * @return {Boolean}
     */
    isReady() {

        return this.isEditorReady;
    }

    // --------------------------------------------------------------------------

    /**
     * @return {Boolean}
     */
    isOpen() {

        return this.isEditorOpen;
    }
}

export default WidgetEditor;
