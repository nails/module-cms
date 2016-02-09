/* globals console, CKEDITOR */
var NAILS_Admin_CMS_Blocks_Create;
NAILS_Admin_CMS_Blocks_Create = function()
{
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * Construct the class
     * @return {Void}
     */
    base.__construct = function()
    {
        base.initTypeChange();
    };

    // --------------------------------------------------------------------------

    /**
     * Binds to the `type` select box
     * @return {Void}
     */
    base.initTypeChange = function()
    {
        $('select[name=type]').on('change', function() {

            base.typeChanged();
        });

        base.typeChanged();
    };

    // --------------------------------------------------------------------------

    /**
     * Triggered when the `type` select changes and shows/renders the appropriate fields
     * @return {Void}
     */
    base.typeChanged = function()
    {
        var type = $('select[name=type]').val();

        //  Hide all the fields
        $('.default-value').hide();

        //  Destroy the richtext editor, if there is one
        if (typeof(CKEDITOR.instances['default-value-richtext-editor']) !== 'undefined')
        {
            console.log('Destroy');
            CKEDITOR.instances['default-value-richtext-editor'].destroy();
        }

        //  Custom actions dependant on the block type
        if (type === 'richtext') {

            console.log('IT LIVES');
            CKEDITOR.replace(
                'default-value-richtext-editor',
                {
                    'customConfig': window.NAILS.URL + 'js/ckeditor.config.default.min.js'
                }
            );
        }

        //  Show the relevant field
        $('#default-value-' + type).show();
    };

    // --------------------------------------------------------------------------

    return base.__construct();
}();