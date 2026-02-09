<?php
/**
 * Admin Menu and Dashboard Handler
 */

class I9S_Tools_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            'i9s Database Tools',           // Page title
            'i9s Tools',                     // Menu title
            'manage_options',                // Capability
            'i9s-tools',                     // Menu slug
            array($this, 'render_dashboard'),// Callback
            'dashicons-database-view',       // Icon
            80                               // Position
        );
    }
    
    /**
     * Enqueue CSS and JS for admin pages
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'i9s-tools') === false) {
            return;
        }
        
        wp_enqueue_style(
            'i9s-tools-admin',
            I9S_TOOLS_URL . 'assets/css/admin.css',
            array(),
            I9S_TOOLS_VERSION
        );
        
        wp_enqueue_script(
            'i9s-tools-admin',
            I9S_TOOLS_URL . 'assets/js/admin.js',
            array('jquery'),
            I9S_TOOLS_VERSION,
            true
        );
        
        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('i9s-tools-admin', 'i9sTools', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('i9s_tools_nonce')
        ));
    }
    
    /**
     * Render the main dashboard
     */
    public function render_dashboard() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap i9s-tools-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="i9s-tools-intro">
                <p>Welcome to the i9s Database Tools. These utilities help you manage your player database and Pods data.</p>
                <p><strong>⚠️ Important:</strong> Always backup your database before running any bulk operations!</p>
            </div>
            
            <div class="i9s-tools-grid">
                
                <!-- PlayerID Dash Fix Tool -->
                <div class="i9s-tool-card">
                    <h2>🔧 Fix PlayerID Dashes</h2>
                    <p>Identifies and fixes PlayerIDs with multiple consecutive dashes (e.g., <code>oms---000ale</code> → <code>oms-000ale</code>)</p>
                    
                    <div class="i9s-tool-actions">
                        <button type="button" class="button button-secondary" id="scan-playerids">
                            Scan for Issues
                        </button>
                        <button type="button" class="button button-primary" id="fix-playerids" disabled>
                            Fix PlayerIDs
                        </button>
                    </div>
                    
                    <div id="playerid-results" class="i9s-results"></div>
                </div>
                
                <!-- Data Validation -->
                <div class="i9s-tool-card">
                    <h2>✅ Data Validation</h2>
                    <p>Check for missing data, orphaned records, and data quality issues</p>
                    <div class="i9s-tool-actions">
                        <a href="<?php echo admin_url('admin.php?page=i9s-validation'); ?>" class="button button-primary">
                            Run Validation Checks
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
}
