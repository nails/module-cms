/**
 * CMS menu create/edit screen.
 *
 * Items are rendered by the view. This plugin sorts them, adds new ones from
 * the template, and confirms removal with the admin modal. Which of "page"
 * or "URL" is visible is Revealer's job.
 */

class MenuEditor {

    /**
     * @param adminController
     * @return {MenuEditor}
     */
    constructor(adminController) {

        this.adminController = adminController;
        this.modal = null;

        this.adminController.onRefreshUi(() => {
            this.init();
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Bind a menu editor once nestedSortable is on the page
     * @returns {MenuEditor}
     */
    init() {

        let root = document.querySelector('.js-cms-menu:not([data-cms-menu-bound])');

        if (!root) {
            return this;
        }

        if (typeof $.fn.nestedSortable !== 'function') {
            return this;
        }

        //  Measuring a list inside a hidden tab gives every item a zero
        //  offset, so drag never lands. Wait until the Items tab is showing.
        let page = root.closest('.tab-page');
        if (page && !page.classList.contains('active')) {
            return this;
        }

        root.dataset.cmsMenuBound = '1';

        let $tree = $(root).find('.js-cms-menu-tree').first();

        $tree.nestedSortable({
            handle: '.cms-menu-item__handle',
            items: 'li:visible',
            toleranceElement: '> div',
            listType: 'ol',
            forcePlaceholderSize: true,
            //  The default cancel list includes `button`. The handle is a
            //  span so a drag can start there; buttons and fields cannot.
            cancel: 'input,textarea,select,option,button,.select2-container',
            stop: (event, ui) => {
                this.updateParents($tree);
                let parent = ui.item.parent().closest('.cms-menu-item').get(0);
                if (parent) {
                    this.setCollapsed(parent, false);
                }
                this.syncChrome(root);
            },
        });

        root.addEventListener('click', (event) => {

            if (event.target.closest('.js-cms-menu-add')) {
                event.preventDefault();
                this.addItem(root, $tree);
                return;
            }

            if (event.target.closest('.js-cms-menu-collapse-all')) {
                event.preventDefault();
                this.setAllCollapsed(root, true);
                return;
            }

            if (event.target.closest('.js-cms-menu-expand-all')) {
                event.preventDefault();
                this.setAllCollapsed(root, false);
                return;
            }

            let toggle = event.target.closest('.js-cms-menu-toggle');

            if (toggle && root.contains(toggle)) {
                event.preventDefault();
                let item = toggle.closest('.cms-menu-item');
                this.setCollapsed(item, !item.classList.contains('is-collapsed'));
                return;
            }

            let remove = event.target.closest('.js-cms-menu-remove');

            if (remove && root.contains(remove)) {
                event.preventDefault();
                this.confirmRemove(remove.closest('.cms-menu-item'), root);
            }
        });

        //  The inactive link field stays filled while editing, so switching
        //  type does not throw the other value away. It is cleared on submit
        //  so each item posts exactly one of page or URL.
        let form = root.closest('form');
        if (form) {
            form.addEventListener('submit', () => {
                root.querySelectorAll('.js-cms-menu-tree .cms-menu-item').forEach((item) => {
                    let select = item.querySelector('.js-cms-menu-link');
                    if (!select) {
                        return;
                    }
                    if (select.value === 'url') {
                        let page = item.querySelector('.js-cms-menu-page');
                        if (page) {
                            page.value = '0';
                        }
                    } else {
                        let url = item.querySelector('.js-cms-menu-url');
                        if (url) {
                            url.value = '';
                        }
                    }
                });
            });
        }

        root.addEventListener('change', (event) => {
            if (!event.target.closest('.js-cms-menu-link')) {
                return;
            }

            let item = event.target.closest('.cms-menu-item');
            if (!item) {
                return;
            }

            //  Revealer shows the page field on this same change. Select2
            //  only binds visible selects, so scan again after the show.
            window.setTimeout(() => {
                this.adminController.refreshUi(item);
            }, 0);
        });

        this.syncChrome(root);

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Append a blank item and let Revealer bind its link-type select
     * @param {HTMLElement} root
     * @param {jQuery} $tree
     * @returns {MenuEditor}
     */
    addItem(root, $tree) {

        let template = root.querySelector('#cms-menu-item-template');

        if (!template) {
            return this;
        }

        let id = this.generateId(root);
        let html = template.innerHTML.replaceAll('%%CMS_MENU_ITEM_ID%%', id);
        let holder = document.createElement('div');
        holder.innerHTML = html.trim();
        let item = holder.firstElementChild;

        $tree.append(item);
        this.adminController.refreshUi(item);
        this.syncChrome(root);

        let label = item.querySelector('.js-cms-menu-label');
        if (label) {
            label.focus();
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Confirm, then drop the item and anything nested under it
     * @param {HTMLElement} item
     * @param {HTMLElement} root
     * @returns {MenuEditor}
     */
    confirmRemove(item, root) {

        if (!item) {
            return this;
        }

        if (!this.modal) {
            this.modal = this.adminController.getInstance('Modal').create();
        }

        this.modal
            .setTitle('Remove menu item?')
            .setBody(
                '<p>This removes the item and any items nested under it.</p>' +
                '<p>Save the menu to keep the change.</p>'
            )
            .clearActions()
            .addAction('Remove', ['btn-danger'], (event, modal) => {
                this.adminController.destroyUi(item);
                item.remove();
                this.syncChrome(root);
                modal.hide();
            })
            .addAction('Cancel', ['btn-default'], (event, modal) => {
                modal.hide();
            })
            .show();

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Hide or show an item's children
     * @param {HTMLElement} item
     * @param {Boolean} collapsed
     * @returns {MenuEditor}
     */
    setCollapsed(item, collapsed) {

        if (!item) {
            return this;
        }

        item.classList.toggle('is-collapsed', collapsed);

        let toggle = item.querySelector(':scope > .cms-menu-item__row .js-cms-menu-toggle');

        if (toggle) {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.setAttribute('aria-label', collapsed ? 'Expand nested items' : 'Collapse nested items');
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Collapse or expand every item that has children
     * @param {HTMLElement} root
     * @param {Boolean} collapsed
     * @returns {MenuEditor}
     */
    setAllCollapsed(root, collapsed) {

        root.querySelectorAll('.cms-menu-item').forEach((item) => {
            let nested = item.querySelector(':scope > ol.cms-menu__tree > .cms-menu-item');
            if (nested) {
                this.setCollapsed(item, collapsed);
            }
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Point each item at the item it was dropped under
     * @param {jQuery} $tree
     * @returns {MenuEditor}
     */
    updateParents($tree) {

        $tree.find('.js-cms-menu-parent').each(function() {
            let parentId = String($(this).closest('ol').closest('li').data('id') || '');
            if ($(this).val() !== parentId) {
                $(this).val(parentId).trigger('change');
            }
        });

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * Keep the empty state and the Items tab count in step with the list
     * @param {HTMLElement} root
     * @returns {MenuEditor}
     */
    syncChrome(root) {

        let count = root.querySelectorAll('.js-cms-menu-tree .cms-menu-item').length;
        let empty = root.querySelector('.js-cms-menu-empty');

        if (empty) {
            empty.hidden = count > 0;
        }

        let tab = document.querySelector('.group-cms.menus a[data-tab="tab-items"]');

        if (tab) {
            let badge = tab.querySelector('.cms-menu-count');

            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge cms-menu-count';
                tab.append(' ', badge);
            }

            badge.textContent = String(count);
        }

        root.querySelectorAll('.cms-menu-item').forEach((item) => {
            let toggle = item.querySelector(':scope > .cms-menu-item__row .js-cms-menu-toggle');
            let hasChildren = item.querySelector(':scope > ol.cms-menu__tree > .cms-menu-item');

            if (!toggle) {
                return;
            }

            toggle.hidden = !hasChildren;

            if (!hasChildren) {
                this.setCollapsed(item, false);
            }
        });

        let tools = root.querySelector('.cms-menu-toolbar__actions');

        if (tools) {
            let parents = root.querySelectorAll('.cms-menu-item > ol.cms-menu__tree > .cms-menu-item');
            tools.hidden = parents.length === 0;
        }

        return this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param {HTMLElement} root
     * @returns {String}
     */
    generateId(root) {

        let chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let id = '';

        do {
            id = 'newid-';
            for (let i = 0; i < 32; i++) {
                id += chars[Math.round(Math.random() * (chars.length - 1))];
            }
        } while (root.querySelector('.cms-menu-item[data-id="' + id + '"]'));

        return id;
    }
}

export default MenuEditor;
