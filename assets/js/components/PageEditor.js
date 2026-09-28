/**
 * CMS page create/edit screen.
 *
 * Template-specific areas and options are shown by Revealer. Save, publish,
 * and the unsaved-changes notice are the shared floating controls. This
 * plugin only keeps the widget editor and the preview modal in step with
 * the form.
 */

class PageEditor {

    /**
     * @param adminController
     * @return {PageEditor}
     */
    constructor(adminController) {

        this.adminController = adminController;
        this.editor = null;
        this.form = null;
        this.previewModal = null;

        this.adminController.onRefreshUi(() => {
            this.init();
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Bind the page form once the widget editor is available
     * @returns {PageEditor}
     */
    init() {

        let form = document.getElementById('cms-page-form');

        if (!form || form.dataset.cmsPageEditor) {
            return this;
        }

        this.editor = this.adminController.getInstance('WidgetEditor', 'nails/module-cms');

        if (!this.editor) {
            return this;
        }

        form.dataset.cmsPageEditor = '1';
        this.form = form;

        form.addEventListener('submit', () => {
            this.syncTemplateData();
        });

        form.addEventListener('click', (event) => {

            let area = event.target.closest('.js-cms-page-area');

            if (area && form.contains(area)) {
                event.preventDefault();
                if (this.editor.isReady()) {
                    this.editor.show(area.dataset.area);
                }
                return;
            }

            if (event.target.closest('.js-cms-page-preview')) {
                event.preventDefault();
                this.showPreview();
            }
        });

        $(this.editor)
            .on('widgeteditor-ready', () => {
                this.populateEditor();
                form.querySelectorAll('.js-cms-page-area').forEach((button) => {
                    button.disabled = false;
                    button.classList.remove('disabled');
                });

                this.editor.addAction('Preview', 'default', () => {
                    this.showPreview();
                });
                this.editor.addAction('Publish Changes', 'success', () => {
                    this.submit('PUBLISH');
                });
                this.editor.addAction('Save Changes', 'primary', () => {
                    this.submit('SAVE');
                });
            })
            .on('widgeteditor-close', () => {
                this.syncTemplateData();
            });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Copy saved area JSON into the widget editor
     * @returns {PageEditor}
     */
    populateEditor() {

        let input = this.form.querySelector('#template-data');
        let raw = input ? input.value : '';

        if (!raw) {
            return this;
        }

        let areaData;

        try {
            areaData = JSON.parse(raw);
        } catch (e) {
            this.adminController.warn('Could not read saved template data');
            return this;
        }

        if (!areaData || typeof areaData !== 'object') {
            return this;
        }

        Object.keys(areaData).forEach((area) => {
            this.editor.setAreaData(area, areaData[area]);
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Write the active template's widget data back to the hidden field
     * @returns {PageEditor}
     */
    syncTemplateData() {

        if (!this.editor || !this.editor.isReady()) {
            return this;
        }

        let checked = this.form.querySelector('input[name="template"]:checked');
        let slug = checked ? checked.value : '';
        let input = this.form.querySelector('#template-data');

        if (!slug || !input) {
            return this;
        }

        let areaRoot = this.form.querySelector('#' + CSS.escape('template-area-' + slug));
        let data = {};

        if (areaRoot) {
            areaRoot.querySelectorAll('.js-cms-page-area').forEach((button) => {
                data[button.dataset.area] = this.editor.getAreaData(button.dataset.area);
            });
        }

        let next = JSON.stringify(data);

        if (input.value !== next) {
            input.value = next;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Submit via Save or Publish so the floating-bar button is the submitter
     * @param {String} action SAVE or PUBLISH
     * @returns {PageEditor}
     */
    submit(action) {

        this.syncTemplateData();

        let button = this.form.querySelector('button[name="action"][value="' + action + '"]');

        if (button && this.form.requestSubmit) {
            this.form.requestSubmit(button);
        } else {
            this.form.submit();
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Open a full-size preview of the draft in the admin modal
     * @returns {PageEditor}
     */
    showPreview() {

        this.syncTemplateData();

        let modal = this.previewModal || this.adminController.getInstance('Modal').create();
        this.previewModal = modal;
        modal.container.classList.add('cms-page-preview', 'is-loading');

        let frame = document.createElement('iframe');
        frame.title = 'Page preview';
        frame.addEventListener('load', () => {
            if (frame.getAttribute('src')) {
                modal.container.classList.remove('is-loading');
            }
        });

        modal
            .setTitle('Preview')
            .setBody(frame)
            .clearActions()
            .addAction('Save Changes', ['btn-primary'], () => {
                modal.hide();
                this.submit('SAVE');
            })
            .addAction('Publish Changes', ['btn-success'], () => {
                modal.hide();
                this.submit('PUBLISH');
            })
            .addAction('Close', ['btn-default'], () => {
                modal.hide();
            });

        modal.onHide(() => {
            frame.removeAttribute('src');
        });

        modal.show();

        $.ajax({
            url: window.SITE_URL + 'api/cms/pages/preview',
            method: 'POST',
            data: $(this.form).serialize(),
        })
            .done((response) => {
                let url = response && response.data && response.data.url;
                if (!url) {
                    this.showPreviewError(
                        (response && response.data && response.data.error) || 'Could not generate a preview.'
                    );
                    return;
                }
                frame.src = url;
            })
            .fail((response) => {
                let message = 'Could not generate a preview.';
                try {
                    let data = JSON.parse(response.responseText);
                    message = data.error || message;
                } catch (e) {
                    //  Keep the fallback message.
                }
                this.showPreviewError(message);
            });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Replace the preview with the reason it failed
     * @param {String} message
     * @returns {PageEditor}
     */
    showPreviewError(message) {

        if (!this.previewModal) {
            return this;
        }

        this.previewModal.container.classList.remove('is-loading');
        this.previewModal
            .setTitle('Could not generate a preview')
            .setBody('<p>' + this.escapeHtml(message) + '</p>')
            .clearActions()
            .addAction('OK', ['btn-primary'], (event, modal) => {
                modal.hide();
            });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {String} value
     * @returns {String}
     */
    escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}

export default PageEditor;
