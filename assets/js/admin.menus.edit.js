/* globals Mustache */
var NAILS_Admin_CMS_Menus_Create_Edit;
NAILS_Admin_CMS_Menus_Create_Edit = function(items) {
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * The item's template
     * @type {String}
     */
    base.itemTemplate = '';

    /**
     * The length of the ID to generate
     * @type {Number}
     */
    base.idLength = 32;

    // --------------------------------------------------------------------------

    /**
     * Constructs the edit page
     * @param  {array} items The menu items
     * @return {void}
     */
    base.__construct = function(items) {
        base.itemTemplate = $('#template-item').html();

        //  Init NestedSortable
        $('div.nested-sortable').each(function() {
            var sortable, container, html, target;

            //  Get the sortable item
            sortable = $(this);

            //  Get the container
            container = sortable.children('ol.nested-sortable').first();

            //  Build initial menu items
            for (var key in items) {
                if (items.hasOwnProperty(key)) {
                    html = Mustache.render(base.itemTemplate, items[key]);

                    //  Does this have a parent? If so then we need to append it there
                    if (items[key].parent_id !== null && items[key].parent_id !== '') {

                        //  Find the parent and append to it's <ol class="nested-sortable-sub">
                        target = $('li.target-' + items[key].parent_id + ' ol.nested-sortable-sub').first();

                        if (target.length === 0) {
                            target = container;
                        }

                    } else {

                        target = container;
                    }

                    target.append(html);

                    //  If the page_id is set, then make sure it's selected in the dropdown
                    if (parseInt(items[key].page_id, 10) > 0) {

                        target.find('li:last option[value=' + items[key].page_id + ']').prop('selected', true);
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Sortitize!
            container.nestedSortable({
                'handle': 'div.handle',
                'items': 'li',
                'toleranceElement': '> div',
                'stop': function() {
                    //  Update parents
                    base.updateParentIds(container);
                }
            });

            // --------------------------------------------------------------------------

            //  Bind to add button
            sortable.find('a.add-item').on('click', function() {
                var _data = {
                    id: base.generateId()
                };

                var html = Mustache.render(base.itemTemplate, _data);

                container.append(html);

                return false;
            });
        });

        // --------------------------------------------------------------------------

        //  Bind to remove buttons
        $(document).on('click', 'a.item-remove', function() {
            var _obj = $(this);

            $('<div>')
                .html('<p>This will remove this menu item (and any children) from the interface.</p><p>You will still need to "Save Changes" to commit the removal</p>')
                .dialog(
                    {
                        title: 'Are you sure?',
                        resizable: false,
                        draggable: false,
                        modal: true,
                        dialogClass: "no-close",
                        buttons:
                            {
                                OK: function() {
                                    _obj.closest('li.target').remove();
                                    $(this).dialog("close");
                                },
                                Cancel: function() {
                                    $(this).dialog("close");
                                }
                            }
                    })
                .show();

            return false;
        });
    };

    // --------------------------------------------------------------------------

    /**
     * Updates each menu item's parent ID field
     * @param  {Object} container The container object to restrict to
     * @return {void}
     */
    base.updateParentIds = function(container) {
        $('input.input-parent_id', container).each(function() {
            var parentId = $(this).closest('ol').closest('li').data('id');
            $(this).val(parentId);
        });
    };

    // --------------------------------------------------------------------------

    /**
     * Generates a unique ID for the page
     * @return {String}
     */
    base.generateId = function() {
        var chars, idStr;

        chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {

            idStr = 'newid-';

            for (var i = base.idLength; i > 0; --i) {

                idStr += chars[Math.round(Math.random() * (chars.length - 1))];
            }

        } while ($('li.target-' + idStr).length > 0);

        return idStr;
    };

    // --------------------------------------------------------------------------

    return base.__construct(items);
};