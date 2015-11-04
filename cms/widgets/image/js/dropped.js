/**
 * Shortcuts
 */
var fieldWidth  = $('input[name=width]', domElement).closest('.field');
var fieldHeight = $('input[name=height]', domElement).closest('.field');
var fieldUrl    = $('input[name=url]', domElement).closest('.field');
var fieldTarget = $('select[name=target]', domElement).closest('.field');
var fieldAttr   = $('input[name=link_attr]', domElement).closest('.field');
var inpScaling  = $('select[name=scaling]', domElement);
var inpLinking  = $('select[name=linking]', domElement);

// --------------------------------------------------------------------------

/**
 * Hide all the things
 */
fieldWidth.hide();
fieldHeight.hide();
fieldUrl.hide();
fieldTarget.hide();
fieldAttr.hide();

// --------------------------------------------------------------------------

/**
 * Bind to the change event of the scale and link fields
 */
inpScaling.on('change', function()  {

    switch ($(this).val()) {

        case 'CROP' :
        case 'SCALE' :
            fieldWidth.show();
            fieldHeight.show();
            break;

        default :
            fieldWidth.hide();
            fieldHeight.hide();
            break;
    }

}).trigger('change');

inpLinking.on('change', function() {

    switch ($(this).val()) {

        case 'FULLSIZE' :
            fieldUrl.hide();
            fieldTarget.show().trigger('change');
            fieldAttr.show();
            break;

        case 'CUSTOM' :
            fieldUrl.show();
            fieldTarget.show().trigger('change');
            fieldAttr.show();
            break;

        default :
            fieldUrl.hide();
            fieldTarget.hide();
            fieldAttr.hide();
            break;
    }

}).trigger('change');
