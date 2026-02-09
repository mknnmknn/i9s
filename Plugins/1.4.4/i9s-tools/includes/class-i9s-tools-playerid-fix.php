<?php
/**
 * PlayerID Dash Fix Utility
 * Handles scanning and fixing PlayerIDs with multiple consecutive dashes
 */

class I9S_Tools_PlayerID_Fix {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_i9s_scan_playerids', array($this, 'ajax_scan_playerids'));
        add_action('wp_ajax_i9s_fix_playerids', array($this, 'ajax_fix_playerids'));
    }
    
    /**
     * AJAX handler: Scan for PlayerIDs with multiple dashes
     */
    public function ajax_scan_playerids() {
        // Verify nonce
        check_ajax_referer('i9s_tools_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $results = $this->scan_for_issues();
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler: Fix PlayerIDs with multiple dashes
     */
    public function ajax_fix_playerids() {
        // Verify nonce
        check_ajax_referer('i9s_tools_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $results = $this->fix_playerids();
        
        wp_send_json_success($results);
    }
    
    /**
     * Scan for PlayerIDs with multiple consecutive dashes
     * 
     * @return array Results of the scan
     */
    private function scan_for_issues() {
        global $wpdb;
        
        $issues = array();
        
        // Get all players with their PlayerIDs
        $players = pods('player');
        
        if (!$players) {
            return array(
                'found' => 0,
                'issues' => array(),
                'message' => 'Could not initialize Pods object for "player"',
                'debug' => array('pods_object_exists' => false)
            );
        }
        
        // IMPORTANT: Call find() with unlimited results BEFORE total()
        $players->find(array('limit' => -1));
        $total_players = $players->total();
        
        // Debug: check what we got back
        $debug_info = array(
            'pods_object_exists' => true,
            'total_players' => $total_players,
            'pod_name' => 'player'
        );
        
        if ($total_players == 0) {
            return array(
                'found' => 0,
                'issues' => array(),
                'message' => 'No players found in the player pod',
                'debug' => $debug_info
            );
        }
        
        // Debug: collect sample PlayerIDs
        $sample_pids = array();
        
        while ($players->fetch()) {
            $player_id = $players->field('pid');
            $player_name = $players->field('post_title');
            $post_id = $players->id();
            
            // Collect ALL players for debugging
            $sample_pids[] = array(
                'name' => $player_name,
                'pid' => $player_id
            );
            
            // Check for multiple consecutive dashes
            if (preg_match('/--+/', $player_id)) {
                // Create the fixed version
                $fixed_id = preg_replace('/--+/', '-', $player_id);
                
                // Count related records
                $batting_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}pods_playeryear_b WHERE pid = %s",
                    $player_id
                ));
                
                $pitching_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}pods_playeryear_p WHERE pid = %s",
                    $player_id
                ));
                
                $issues[] = array(
                    'post_id' => $post_id,
                    'name' => $player_name,
                    'current_id' => $player_id,
                    'fixed_id' => $fixed_id,
                    'batting_records' => (int) $batting_count,
                    'pitching_records' => (int) $pitching_count
                );
            }
        }
        
        return array(
            'found' => count($issues),
            'issues' => $issues,
            'message' => sprintf('Scanned %d players, found %d with multiple dashes', $total_players, count($issues)),
            'debug' => array(
                'total_players' => $total_players,
                'pod_name' => 'player',
                'sample_pids' => $sample_pids
            )
        );
    }
    
    /**
     * Fix PlayerIDs by replacing multiple dashes with single dashes
     * 
     * @return array Results of the fix operation
     */
    private function fix_playerids() {
        global $wpdb;
        
        // First, scan to get the list
        $scan_results = $this->scan_for_issues();
        
        if ($scan_results['found'] == 0) {
            return array(
                'fixed' => 0,
                'errors' => array(),
                'message' => 'No issues found to fix'
            );
        }
        
        $fixed_count = 0;
        $errors = array();
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($scan_results['issues'] as $issue) {
                $old_id = $issue['current_id'];
                $new_id = $issue['fixed_id'];
                $post_id = $issue['post_id'];
                
                // Update Player pod
                $player_updated = update_post_meta($post_id, 'pid', $new_id);
                
                // Update PlayerYears_b
                $batting_updated = $wpdb->update(
                    $wpdb->prefix . 'pods_playeryear_b',
                    array('pid' => $new_id),
                    array('pid' => $old_id),
                    array('%s'),
                    array('%s')
                );
                
                // Update PlayerYears_p
                $pitching_updated = $wpdb->update(
                    $wpdb->prefix . 'pods_playeryear_p',
                    array('pid' => $new_id),
                    array('pid' => $old_id),
                    array('%s'),
                    array('%s')
                );
                
                if ($player_updated !== false && $batting_updated !== false && $pitching_updated !== false) {
                    $fixed_count++;
                } else {
                    $errors[] = sprintf(
                        'Failed to update %s (%s → %s)',
                        $issue['name'],
                        $old_id,
                        $new_id
                    );
                }
            }
            
            // If no errors, commit
            if (empty($errors)) {
                $wpdb->query('COMMIT');
                $message = sprintf('Successfully fixed %d player(s)', $fixed_count);
            } else {
                $wpdb->query('ROLLBACK');
                $message = 'Errors occurred, changes rolled back';
            }
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            $errors[] = 'Exception: ' . $e->getMessage();
            $message = 'Fatal error occurred, changes rolled back';
        }
        
        return array(
            'fixed' => $fixed_count,
            'errors' => $errors,
            'message' => $message
        );
    }
}

// Initialize the class
new I9S_Tools_PlayerID_Fix();
