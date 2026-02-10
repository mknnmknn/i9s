jQuery(document).ready(function($) {
    
    var table;
    
    // Load data via AJAX
    $.ajax({
        url: i9sData.ajaxurl,
        type: 'POST',
        data: {
            action: 'i9s_get_master_list',
            nonce: i9sData.nonce
        },
        success: function(response) {
            if (response.success) {
                initializeTable(response.data.data);
            } else {
                alert('Error loading player data: ' + response.data);
            }
        },
        error: function() {
            alert('Failed to load player data. Please refresh the page.');
        }
    });
    
    function initializeTable(data) {
        table = $('#master-player-list').DataTable({
            data: data,
            columns: [
                { data: 'name' },
                { data: 'pid_link', orderable: true, orderData: 1 },
                { data: 'pos' },
                { data: 'debut' },
                { data: 'years' },
                { data: 'wp_status' },
                { data: 'i9s_status' },
                { data: 'edit', orderable: false }
            ],
            order: [[0, 'asc']], // Default sort by name
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
            language: {
                search: "Quick search:",
                lengthMenu: "Show _MENU_ players per page",
                info: "Showing _START_ to _END_ of _TOTAL_ players",
                infoEmpty: "No players found",
                infoFiltered: "(filtered from _MAX_ total players)"
            },
            // Disable default search (we're using custom search boxes)
            searching: true
        });
        
        // Custom search for name field
        $('#search-name').on('keyup', function() {
            filterTable();
        });
        
        // Custom search for content field
        $('#search-content').on('keyup', function() {
            filterTable();
        });
        
        function filterTable() {
            var nameSearch = $('#search-name').val().toLowerCase();
            var contentSearch = $('#search-content').val().toLowerCase();
            
            $.fn.dataTable.ext.search.push(
                function(settings, searchData, index, rowData, counter) {
                    var nameMatch = true;
                    var contentMatch = true;
                    
                    // Name search - search in name_search field
                    if (nameSearch) {
                        nameMatch = rowData.name_search.toLowerCase().indexOf(nameSearch) !== -1;
                    }
                    
                    // Content search - search in content_search field
                    if (contentSearch) {
                        contentMatch = rowData.content_search.toLowerCase().indexOf(contentSearch) !== -1;
                    }
                    
                    return nameMatch && contentMatch;
                }
            );
            
            table.draw();
            
            // Clear the custom search function
            $.fn.dataTable.ext.search.pop();
        }
    }
});
