/**
 * i9s Database Tools - Admin JavaScript
 */

(function($) {
    'use strict';
    
    // PlayerID Fix Tool
    const PlayerIDFix = {
        
        init: function() {
            $('#scan-playerids').on('click', this.scanPlayerIDs);
            $('#fix-playerids').on('click', this.fixPlayerIDs);
        },
        
        scanPlayerIDs: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $resultsDiv = $('#playerid-results');
            const $fixButton = $('#fix-playerids');
            
            // Disable button and show loading
            $button.prop('disabled', true).html('Scanning... <span class="i9s-loading"></span>');
            $resultsDiv.removeClass('show success warning error').empty();
            $fixButton.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: i9sTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'i9s_scan_playerids',
                    nonce: i9sTools.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PlayerIDFix.displayScanResults(response.data);
                        
                        // Enable fix button if issues found
                        if (response.data.found > 0) {
                            $fixButton.prop('disabled', false);
                        }
                    } else {
                        PlayerIDFix.displayError('Scan failed: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    PlayerIDFix.displayError('AJAX error: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Scan for Issues');
                }
            });
        },
        
        fixPlayerIDs: function(e) {
            e.preventDefault();
            
            // Confirm before proceeding
            if (!confirm('Are you sure you want to fix these PlayerIDs? This will update the database.\n\nMake sure you have a backup!')) {
                return;
            }
            
            const $button = $(this);
            const $resultsDiv = $('#playerid-results');
            const $scanButton = $('#scan-playerids');
            
            // Disable buttons and show loading
            $button.prop('disabled', true).html('Fixing... <span class="i9s-loading"></span>');
            $scanButton.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: i9sTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'i9s_fix_playerids',
                    nonce: i9sTools.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PlayerIDFix.displayFixResults(response.data);
                    } else {
                        PlayerIDFix.displayError('Fix failed: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    PlayerIDFix.displayError('AJAX error: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Fix PlayerIDs');
                    $scanButton.prop('disabled', false);
                }
            });
        },
        
        displayScanResults: function(data) {
            const $resultsDiv = $('#playerid-results');
            
            // Show debug info if available
            let debugHtml = '';
            if (data.debug) {
                debugHtml = '<div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 3px; font-size: 13px;">';
                debugHtml += '<strong>Debug Info:</strong><br>';
                debugHtml += 'Pod name: ' + (data.debug.pod_name || 'N/A') + '<br>';
                debugHtml += 'Total players: ' + (data.debug.total_players !== undefined ? data.debug.total_players : 'N/A') + '<br>';
                
                if (data.debug.sample_pids && data.debug.sample_pids.length > 0) {
                    debugHtml += '<br><strong>All PlayerIDs Scanned (' + data.debug.sample_pids.length + ' total):</strong><br>';
                    debugHtml += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 5px;">';
                    debugHtml += '<table style="font-size: 12px; width: 100%;">';
                    data.debug.sample_pids.forEach(function(sample) {
                        debugHtml += '<tr><td style="padding: 2px 15px 2px 2px;">' + sample.name + '</td><td><code>' + sample.pid + '</code></td></tr>';
                    });
                    debugHtml += '</table>';
                    debugHtml += '</div>';
                }
                debugHtml += '</div>';
            }
            
            if (data.found === 0) {
                $resultsDiv
                    .addClass('show success')
                    .html(debugHtml + '<h3>✓ No Issues Found</h3><p>All PlayerIDs are properly formatted!</p>');
                return;
            }
            
            let html = debugHtml; // Add debug section first
            html += '<h3>⚠ Found ' + data.found + ' Player(s) with Issues</h3>';
            html += '<table class="i9s-results-table">';
            html += '<thead><tr>';
            html += '<th>Player</th>';
            html += '<th>Current ID</th>';
            html += '<th>Will Become</th>';
            html += '<th>Batting Rows</th>';
            html += '<th>Pitching Rows</th>';
            html += '</tr></thead><tbody>';
            
            data.issues.forEach(function(issue) {
                html += '<tr>';
                html += '<td>' + issue.name + '</td>';
                html += '<td><code>' + issue.current_id + '</code></td>';
                html += '<td><code>' + issue.fixed_id + '</code></td>';
                html += '<td>' + issue.batting_records + '</td>';
                html += '<td>' + issue.pitching_records + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '<p style="margin-top: 10px;"><strong>Click "Fix PlayerIDs" to update these records.</strong></p>';
            
            $resultsDiv
                .addClass('show warning')
                .html(html);
        },
        
        displayFixResults: function(data) {
            const $resultsDiv = $('#playerid-results');
            
            if (data.errors.length > 0) {
                let html = '<h3>❌ Fix Failed</h3>';
                html += '<p>' + data.message + '</p>';
                html += '<ul>';
                data.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
                
                $resultsDiv
                    .removeClass('warning')
                    .addClass('show error')
                    .html(html);
            } else {
                $resultsDiv
                    .removeClass('warning')
                    .addClass('show success')
                    .html('<h3>✓ Success!</h3><p>' + data.message + '</p><p>You may want to clear your cache and verify the changes on your site.</p>');
                
                // Disable fix button since we're done
                $('#fix-playerids').prop('disabled', true);
            }
        },
        
        displayError: function(message) {
            $('#playerid-results')
                .addClass('show error')
                .html('<h3>❌ Error</h3><p>' + message + '</p>');
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        PlayerIDFix.init();
    });
    
})(jQuery);
