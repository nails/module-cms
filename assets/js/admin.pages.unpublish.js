var NAILS_Admin_CMS_Pages_Unpublish;
NAILS_Admin_CMS_Pages_Unpublish = function() {
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * Construct the class
     * @return {void}
     */
    base.__construct = function() {
        $('select[name=redirect_behaviour]')
            .on('change', function(e) {
                var $field = $('input[name=redirect_url]').closest('.field')

                if ($(this).val() === 'URL') {
                    $field.show();
                } else {
                    $field.hide();
                }
            })
            .trigger('change');
    }

    // --------------------------------------------------------------------------

    return base.__construct();
}
