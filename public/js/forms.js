$(function () {
    // Add more fields
    $('#addSearchInput').on('click', function (e) {
        console.log('something');

        var field = '<div class="form-row mb-2">\n' +
            '    <div class="col">\n' +
            '        <input type="text" class="form-control" name="customFieldKeys[]" placeholder="<<Example>>">\n' +
            '    </div>\n' +
            '    <div class="col">\n' +
            '        <input type="text" class="form-control" name="customFieldValues[]" placeholder="Example Value">\n' +
            '    </div>\n' +
            '</div>';

        $('.searchAndReplaceFields').append(field);
    });

    console.log('loaded');
});
