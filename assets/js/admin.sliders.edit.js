/* globals Mustache */
var NAILS_Admin_CMS_Sliders_Create_Edit;
NAILS_Admin_CMS_Sliders_Create_Edit = function() {
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    base.settingImgFor = null;
    base.schemeServe = '';
    base.schemeThumb = '';
    base.schemeScale = '';
    base.managerUrl = '';

    // --------------------------------------------------------------------------

    base.__construct = function() {

        $('#addSlide').on('click', function() {
            base.addSlide();
            return false;
        });

        $(document).on('click', '.btnSetImg', function() {
            if (base.managerUrl.length === 0) {
                console.log('Manager URL not available yet');
                return false;
            }
            base.setImg($(this));
            return false;
        });

        $(document).on('click', '.btnRemoveImg', function() {
            base.removeImg($(this));
            return false;
        });

        $(document).on('click', '.btnRemoveSlide', function() {
            base.removeSlide($(this));
            return false;
        });

        $('#slides tbody').sortable({
            handle: '.sortHandle',
            axis: 'y',
            start: function(e, ui) {
                ui.placeholder.height(ui.helper.outerHeight());
            },
        });

        $.get(window.SITE_URL + 'api/cdn/manager/url?bucket=cms-slider&callback=setImgCallback', function(data) {
            base.managerUrl = data.data;
        })
    };

    // --------------------------------------------------------------------------

    base.addSlides = function(slides) {
        for (var key in slides) {
            if (slides.hasOwnProperty(key)) {
                base.addSlide(slides[key]);
            }
        }
    };

    // --------------------------------------------------------------------------

    base.addSlide = function(slide) {
        var html;

        html = $('#templateSlideRow').html();
        html = Mustache.render(html, slide);

        $('#slides tbody').append(html);

        /**
         * Fix the width of the flexible table cells. We do this so that when sorting
         * the row doesn't collapse.
         */

        base.fixCellWidth();
    };

    // --------------------------------------------------------------------------

    base.removeSlide = function(elem) {
        $('<div>').text('Are you sure you wish to remove this slide?').dialog({
            title: 'Are you sure?',
            resizable: false,
            draggable: false,
            modal: true,
            buttons:
                {
                    OK: function() {
                        elem.closest('tr').remove();
                        $(this).dialog('close');
                    },
                    Cancel: function() {
                        $(this).dialog('close');
                    },
                }
        });
    };

    // --------------------------------------------------------------------------

    base.fixCellWidth = function() {
        //  Remove all inline styles
        $('#slides tbody tr td.caption').removeAttr('style');

        //  Recalculate, account for right border
        var width = $('#slides tbody tr td.caption:first').outerWidth() - 1;

        //  Reset styles
        $('#slides tbody tr td.caption').css('width', width);
    };

    // --------------------------------------------------------------------------

    base.setImg = function(elem) {
        //  Save which item we're setting the image for
        base.settingImgFor = elem;

        // --------------------------------------------------------------------------

        //  Open the CDN Manager
        var url;
        url = base.managerUrl;
        url += url.indexOf('?') >= 0 ? '&isModal=1' : '?isModal=1';

        $.fancybox.open({
            'href': url,
            'type': 'iframe',
            'iframe': {
                'preload': false // fixes issue with iframe and IE
            },
            'helpers': {
                'overlay': {
                    'locked': false
                }
            },
            'beforeLoad': function() {
                $('body').addClass('noScroll');
            },
            'afterClose': function() {
                base.settingImgFor = null;
                $('body').removeClass('noScroll');
            }
        });
    };

    // --------------------------------------------------------------------------

    base.setImgCallback = function(bucket, filename, objectId) {
        var container, file, thumbData, thumbUrl, thumb, serveData, serveUrl, serve;

        //  Get the container
        container = base.settingImgFor.closest('td.image');

        //  Update the input
        container.find('input').val(objectId);

        //  Remove any existing thumbnail
        container.find('img').remove();

        //  Add the new thumbnail
        file = filename.split('.');
        thumbData = {
            'width': 130,
            'height': 130,
            'bucket': bucket,
            'filename': file[0],
            'extension': '.' + file[1]
        };
        thumbUrl = Mustache.render(base.schemeScale, thumbData);
        thumb = $('<img>').attr('src', thumbUrl);

        //  Link to fullsize image
        serveData = {
            'bucket': bucket,
            'filename': file[0],
            'extension': '.' + file[1]
        };
        serveUrl = Mustache.render(base.schemeServe, serveData);
        serve = $('<a>').attr('href', serveUrl).addClass('fancybox');

        //  Insert into the DOM
        container.prepend(serve.html(thumb));

        //  Show the remove img button
        container.find('.btnRemoveImg').removeClass('hidden');
    };

    // --------------------------------------------------------------------------

    base.removeImg = function(elem) {
        var container;

        container = elem.closest('td.image');

        container.find('img').remove();
        container.find('input').val('');
        container.find('.btnRemoveImg').addClass('hidden');
    };

    // --------------------------------------------------------------------------

    base.setScheme = function(schemeType, scheme) {
        switch (schemeType) {

            case 'serve':

                base.schemeServe = scheme;
                break;

            case 'thumb':

                base.schemeThumb = scheme;
                break;

            case 'scale':

                base.schemeScale = scheme;
                break;
        }
    };

    // --------------------------------------------------------------------------

    base.setManagerUrl = function(url) {
        base.managerUrl = url;
    };

    // --------------------------------------------------------------------------

    return base.__construct();
};
