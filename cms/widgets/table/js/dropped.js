
var container, data, table

container = $('.handsontable', domElement).get(0);
inpData   = $('.table-data', domElement);
data      = JSON.parse(inpData.val());
table     = new Handsontable(
    container,
    {
        data: data,
        height: 350,
        rowHeaders: true,
        colHeaders: true,
        contextMenu: true,
        stretchH: 'all',
        columnSorting: false,
        afterChange: function(change, source) {
            inpData.val(JSON.stringify(this.getData()));
        }
    }
);
