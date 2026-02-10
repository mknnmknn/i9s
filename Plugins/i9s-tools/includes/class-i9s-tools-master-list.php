<?php
/**
 * Master Player List - Admin page with sortable/searchable player table
 *
 * @package i9s_Tools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class I9S_Tools_Master_List {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'wp_ajax_i9s_get_master_list', array( $this, 'ajax_get_master_list' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    /**
     * Add menu page to WordPress admin
     */
    public function add_menu_page() {
        add_submenu_page(
            'i9s-tools',
            'Master Player List',
            'Master List',
            'manage_options',
            'i9s-master-list',
            array( $this, 'render_page' )
        );
    }
    
    /**
     * Enqueue scripts and styles for the master list page
     */
    public function enqueue_scripts( $hook ) {
        // Only load on our page
        if ( $hook !== 'i9s-tools_page_i9s-master-list' ) {
            return;
        }
        
        // DataTables is included in WordPress by default
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        
        // DataTables CSS and JS
        wp_enqueue_style( 
            'datatables', 
            'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
            array(),
            '1.13.7'
        );
        
        wp_enqueue_script( 
            'datatables', 
            'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
            array( 'jquery' ),
            '1.13.7',
            true
        );
        
        // Our custom script
        wp_enqueue_script(
            'i9s-master-list',
            I9S_TOOLS_URL . 'assets/js/master-list.js',
            array( 'jquery', 'datatables' ),
            I9S_TOOLS_VERSION,
            true
        );
        
        // Pass AJAX URL to JavaScript
        wp_localize_script( 'i9s-master-list', 'i9sData', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'i9s_master_list_nonce' )
        ));
        
        // Custom CSS
        wp_enqueue_style(
            'i9s-master-list',
            I9S_TOOLS_URL . 'assets/css/master-list.css',
            array(),
            I9S_TOOLS_VERSION
        );
    }
    
    /**
     * Render the master list page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1>Master Player List</h1>
            <p>All players in the database with statistics counts and quick access links.</p>
            
            <div class="i9s-search-container">
                <div class="i9s-search-box">
                    <label for="search-name">Search Name:</label>
                    <input type="text" id="search-name" placeholder="Search first/last/nickname...">
                </div>
                <div class="i9s-search-box">
                    <label for="search-content">Search Content:</label>
                    <input type="text" id="search-content" placeholder="Search intro/notes...">
                </div>
            </div>
            
            <table id="master-player-list" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>PID</th>
                        <th>Pos</th>
                        <th>Debut</th>
                        <th>Years (B/P)</th>
                        <th>WP Status</th>
                        <th>i9s Status</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" style="text-align:center;">
                            <em>Loading players...</em>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to get master list data
     */
    public function ajax_get_master_list() {
        // Verify nonce
        check_ajax_referer( 'i9s_master_list_nonce', 'nonce' );
        
        // Verify capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }
        
        global $wpdb;
        
        // Query all players with their data
        $query = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_name as slug,
                p.post_status,
                pm_pid.meta_value as pid,
                pm_first.meta_value as first_name,
                pm_last.meta_value as last_name,
                pm_nickname.meta_value as nickname,
                pm_pos.meta_value as pos,
                pm_debut.meta_value as yrdebut,
                pm_intro.meta_value as playerintro,
                pm_notes.meta_value as notes,
                p.post_content,
                (SELECT COUNT(*) FROM {$wpdb->prefix}pods_playeryear_b WHERE pid = pm_pid.meta_value) as batting_years,
                (SELECT COUNT(*) FROM {$wpdb->prefix}pods_playeryear_p WHERE pid = pm_pid.meta_value) as pitching_years,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as i9s_level
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_pid ON p.ID = pm_pid.post_id AND pm_pid.meta_key = 'pid'
            LEFT JOIN {$wpdb->postmeta} pm_first ON p.ID = pm_first.post_id AND pm_first.meta_key = 'first_name'
            LEFT JOIN {$wpdb->postmeta} pm_last ON p.ID = pm_last.post_id AND pm_last.meta_key = 'last_name'
            LEFT JOIN {$wpdb->postmeta} pm_nickname ON p.ID = pm_nickname.post_id AND pm_nickname.meta_key = 'nickname'
            LEFT JOIN {$wpdb->postmeta} pm_pos ON p.ID = pm_pos.post_id AND pm_pos.meta_key = 'pos'
            LEFT JOIN {$wpdb->postmeta} pm_debut ON p.ID = pm_debut.post_id AND pm_debut.meta_key = 'yrdebut'
            LEFT JOIN {$wpdb->postmeta} pm_intro ON p.ID = pm_intro.post_id AND pm_intro.meta_key = 'playerintro'
            LEFT JOIN {$wpdb->postmeta} pm_notes ON p.ID = pm_notes.post_id AND pm_notes.meta_key = 'notes'
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'i9s_level'
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'player'
            GROUP BY p.ID
            ORDER BY pm_last.meta_value ASC, pm_first.meta_value ASC
        ";
        
        $players = $wpdb->get_results( $query );
        
        // Format data for DataTables
        $data = array();
        foreach ( $players as $player ) {
            $public_url = get_permalink( $player->ID );
            $edit_url = get_edit_post_link( $player->ID );
            
            $name_display = $player->last_name . ', ' . $player->first_name;
            if ( ! empty( $player->nickname ) ) {
                $name_display .= ' (' . $player->nickname . ')';
            }
            
            // Format post status for display
            $status_display = ucfirst( $player->post_status );
            if ( $player->post_status === 'publish' ) {
                $status_display = 'Published';
            }
            
            $data[] = array(
                'name' => $name_display,
                'name_search' => $player->first_name . ' ' . $player->last_name . ' ' . $player->nickname,
                'pid' => $player->pid,
                'pid_link' => '<a href="' . esc_url( $public_url ) . '" target="_blank">' . esc_html( $player->pid ) . '</a>',
                'pos' => $player->pos,
                'debut' => $player->yrdebut,
                'years' => $player->batting_years . ' / ' . $player->pitching_years,
                'wp_status' => $status_display,
                'i9s_status' => $player->i9s_level ? $player->i9s_level : 'None',
                'edit' => '<a href="' . esc_url( $edit_url ) . '" target="_blank">Edit</a>',
                'content_search' => strip_tags( $player->playerintro . ' ' . $player->notes . ' ' . $player->post_content )
            );
        }
        
        wp_send_json_success( array(
            'data' => $data,
            'total' => count( $data )
        ));
    }
}
