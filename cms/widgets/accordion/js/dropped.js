/* global domElement, Mustache, _nails, _nails_admin */
var tplPanel     = $('.tpl-panel', domElement).first().html();
var tplField     = $('.tpl-fieldset', domElement).first().html();
var targetPanels = $('ol.nails-cms-widget-editor-accordion li', domElement).last();
var targetFields = $('section.nails-cms-widget-editor-accordion', domElement);
var panelIndex   = 0;

// --------------------------------------------------------------------------

function addPanel(title, body) {

    var html, index;

    index = panelIndex;
    panelIndex++;

    //  Add DOM elements
    html = Mustache.render(tplPanel, {index: index});
    targetPanels.before(html);

    html = $(Mustache.render(tplField, {index: index, title: title, body:body}));
    targetFields.append(html);

    return index;
}

function switchToPanel(index) {

    //  Destroy WYSIWYG
    window.NAILS.ADMIN.getInstance('Wysiwyg').destroy(domElement);

    //  Swap panels
    $('ol.nails-cms-widget-editor-accordion li.selected', domElement).removeClass('selected');
    $('ol.nails-cms-widget-editor-accordion li a[data-index=' + index + ']', domElement).parent().addClass('selected');

    //  Swap content
    $('section.nails-cms-widget-editor-accordion > .fieldset', domElement).addClass('hidden');
    var newFields = $('section.nails-cms-widget-editor-accordion > .fieldset[data-index=' + index + ']', domElement).removeClass('hidden');

    //  Build WYSIWYG
    window.NAILS.ADMIN.refreshUi();
}

function removePanel(index) {

    var isSelected = $('ol.nails-cms-widget-editor-accordion li a[data-index=' + index + ']', domElement)
        .parent().hasClass('selected');

    //  Remove DOM elements
    $('ol.nails-cms-widget-editor-accordion li a[data-index=' + index + ']', domElement).parent().remove();
    $('section.nails-cms-widget-editor-accordion > .fieldset[data-index=' + index + ']', domElement).remove();

    if (isSelected && !targetFields.is(':empty')) {
        switchToPanel($('ol.nails-cms-widget-editor-accordion li a', domElement).first().data('index'));
    }
}

// --------------------------------------------------------------------------

//  Prefill
var prefill = $('ol.nails-cms-widget-editor-accordion', domElement).data('prefill');
for (var i = 0; i < prefill.length; i++) {
    addPanel(prefill[i].title, prefill[i].body);
}

//  Switch to the first panel
switchToPanel($('ol.nails-cms-widget-editor-accordion li a', domElement).first().data('index'));

// --------------------------------------------------------------------------

//  Bind listeners
//  Adding new panels
domElement.on('click', '.js-action-add-panel', function() {
    var index = addPanel();
    if (!$('ol.nails-cms-widget-editor-accordion li.selected', domElement).length) {
        switchToPanel(index);
    }
});

//  Remove existing panels
domElement.on('click', '.js-action-remove-panel', function() {
    removePanel($(this).data('index'));
    return false;
});

//  Switch to a panel
domElement.on('click', '.js-action-switch-panel', function() {
    switchToPanel($(this).data('index'));
});

// --------------------------------------------------------------------------

//  Setup sortables
$('ol.nails-cms-widget-editor-accordion', domElement)
.disableSelection()
.sortable({
    items: 'li:not(.add-panel)',
    placeholder: 'sortable-placeholder',
    forcePlaceholderSize: true,
    distance: 3,
    stop: function(e, ui) {
        //  Move the appropriate fields into the appropriate position
        var index    = ui.item.find('a').data('index');
        var newIndex = ui.item.index();
        var fields = $('section.nails-cms-widget-editor-accordion > .fieldset[data-index=' + index + ']', domElement);

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
