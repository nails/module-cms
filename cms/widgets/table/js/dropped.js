var container, inpData, data, table;

function saveChange(inpData, instance) {
    console.log('saving');
    inpData.val(JSON.stringify(instance.getData()));
}

container = $('.handsontable', domElement).get(0);
inpData = $('.table-data', domElement);
data = JSON.parse(inpData.val());
table = new Handsontable(
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
            saveChange(inpData, this);
        },
        afterCreateCol: function(change, source) {
            saveChange(inpData, this);
        },
        afterCreateRow: function(change, source) {
            saveChange(inpData, this);
        },
        afterRemoveCol: function(change, source) {
            saveChange(inpData, this);
        },
        afterRemoveRow: function(change, source) {
            saveChange(inpData, this);
        }
    }
);
