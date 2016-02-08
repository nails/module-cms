/* global domElement */
var tplTab       = $('.tpl-tab', domElement).first().html();
var tplField     = $('.tpl-fieldset', domElement).first().html();
var targetTabs   = $('ol.nails-cms-widget-editor-tabs li', domElement).last();
var targetFields = $('section.nails-cms-widget-editor-tabs', domElement);
var tabIndex     = 0;

// --------------------------------------------------------------------------

//  Prefill
var prefill = $('ol.nails-cms-widget-editor-tabs', domElement).data('prefill');
for (var i = 0; i < prefill.length; i++) {
    addTab(prefill[i].title, prefill[i].sub_title, prefill[i].body);
}

//  Switch to the first tab
switchToTab($('ol.nails-cms-widget-editor-tabs li a', domElement).first().data('index'));

// --------------------------------------------------------------------------

//  Bind listeners
//  Adding new tabs
domElement.on('click', '.js-action-add-tab', function() {
    var index = addTab();
    if (!$('ol.nails-cms-widget-editor-tabs li.selected', domElement).length) {
        switchToTab(index);
    }
});

//  Remove existing tabs
domElement.on('click', '.js-action-remove-tab', function() {
    removeTab($(this).data('index'));
    return false;
});

//  Switch to a tab
domElement.on('click', '.js-action-switch-tab', function() {
    switchToTab($(this).data('index'));
});

// --------------------------------------------------------------------------

//  Setup sortables
$('ol.nails-cms-widget-editor-tabs', domElement)
.disableSelection()
.sortable({
    items: 'li:not(.add-tab)',
    placeholder: 'sortable-placeholder',
    forcePlaceholderSize: true,
    distance: 3,
    start: function(e, ui) {
        // creates a temporary attribute on the element with the old index
        $(this).attr('data-previndex', ui.item.index());
    },
    stop: function(e, ui) {
        //  Move the appropriate fields into the appropriate position
        var index    = ui.item.find('a').data('index');
        var newIndex = ui.item.index();
        var oldIndex = $(this).attr('data-previndex');
        $(this).removeAttr('data-previndex');
        var fields = $('section.nails-cms-widget-editor-tabs > .fieldset[data-index=' + index + ']', domElement);

        //  Move to beginning
        targetFields.prepend(fields);

        //  Move it up newIndex times so that it's in the right order - hacky?
        var sibling;
        for (var i = 0; i < newIndex; i++) {
            sibling = fields.next();
            sibling.after(fields);
        }
    }
});

// --------------------------------------------------------------------------

function addTab(title, sub_title, body) {

    var html, index;

    index = tabIndex;
    tabIndex++;

    //  Add DOM elements
    html = Mustache.render(tplTab, {index: index});
    targetTabs.before(html);

    html = Mustache.render(tplField, {index: index, title: title, sub_title: sub_title, body:body});
    targetFields.append(html);

    //  WYSIWYG the textarea
    //  @todo

    return index;
}

function removeTab(index) {

    var isSelected = $('ol.nails-cms-widget-editor-tabs li a[data-index=' + index + ']', domElement)
        .parent().hasClass('selected');

    //  Remove DOM elements
    $('ol.nails-cms-widget-editor-tabs li a[data-index=' + index + ']', domElement).parent().remove();
    $('section.nails-cms-widget-editor-tabs > .fieldset[data-index=' + index + ']', domElement).remove();

    if (isSelected && !targetFields.is(':empty')) {
        switchToTab($('ol.nails-cms-widget-editor-tabs li a', domElement).first().data('index'));
    }
}

function switchToTab(index) {

    //  Swap tabs
    $('ol.nails-cms-widget-editor-tabs li.selected', domElement).removeClass('selected');
    $('ol.nails-cms-widget-editor-tabs li a[data-index=' + index + ']', domElement).parent().addClass('selected');

    //  Swap content
    $('section.nails-cms-widget-editor-tabs > .fieldset', domElement).addClass('hidden');
    $('section.nails-cms-widget-editor-tabs > .fieldset[data-index=' + index + ']', domElement).removeClass('hidden');

    _nails.addStripes();
}
