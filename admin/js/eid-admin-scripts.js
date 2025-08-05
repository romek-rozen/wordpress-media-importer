jQuery(document).ready(function($) {
    $('#eid-scan-form').on('submit', function(e) {
        e.preventDefault();

        var scanButton = $('#eid-scan-button');
        var resultsContainer = $('#eid-scan-results');
        var formData = $(this).serialize();

        scanButton.prop('disabled', true).text('Scanning...');
        resultsContainer.html('<p>Scanning, please wait...</p>');

        $.ajax({
            url: ajaxurl, // ajaxurl is a global variable in WordPress admin
            type: 'POST',
            data: {
                action: 'eid_scan_content',
                nonce: eid_ajax.nonce,
                form_data: formData
            },
            success: function(response) {
                console.log('AJAX Response:', response); // Log the entire response
                if (response.data && response.data.debug) {
                    console.log('--- Server Debug Info ---');
                    console.log(response.data.debug);
                    console.log('-------------------------');
                }

                if (response.success) {
                    resultsContainer.html(response.data.html);
                } else {
                    resultsContainer.html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX Error:', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
                resultsContainer.html('<p class="error">An unexpected AJAX error occurred. Check the browser console for details.</p>');
            },
            complete: function() {
                scanButton.prop('disabled', false).text('Scan Now');
            }
        });
    });

    // Handle the "select all" checkbox for scan results
    $(document).on('change', '#eid-select-all', function() {
        $('#eid-import-form').find('input[name="media_items[]"]').prop('checked', this.checked);
    });

    // Handle the import form submission
    $(document).on('submit', '#eid-import-form', function(e) {
        e.preventDefault();

        var importButton = $('#eid-import-button');
        var selectedItems = $('input[name="media_items[]"]:checked');

        if (selectedItems.length === 0) {
            alert('Please select at least one item to import.');
            return;
        }

        importButton.prop('disabled', true).text('Importing...');
        
        var queue = [];
        selectedItems.each(function() {
            var item = $(this);
            queue.push({
                url: item.val(),
                post_id: item.data('post-id'),
                row_id: item.data('row-id')
            });
        });

        processImportQueue(queue, importButton);
    });

    function processImportQueue(queue, importButton) {
        if (queue.length === 0) {
            importButton.prop('disabled', false).text('Import Selected Media');
            alert('All selected items have been processed.');
            // Optionally, refresh the scan to show updated results
            $('#eid-scan-form').submit();
            return;
        }

        var item = queue.shift();
        var row = $('#eid-row-' + item.row_id);
        var statusSpan = row.find('.status');

        statusSpan.text('Importing...').css('color', 'orange');

        $.ajax({
            url: eid_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'eid_import_media_item',
                nonce: eid_ajax.nonce,
                url: item.url,
                post_id: item.post_id
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.text('Success: ' + response.data.message).css('color', 'green');
                    // Disable the checkbox for the successfully imported item
                    row.find('input[type="checkbox"]').prop('disabled', true).prop('checked', false);
                } else {
                    statusSpan.text('Error: ' + response.data.message).css('color', 'red');
                }
            },
            error: function() {
                statusSpan.text('Error: An unknown error occurred.').css('color', 'red');
            },
            complete: function() {
                // Process the next item in the queue
                processImportQueue(queue, importButton);
            }
        });
    }
});
