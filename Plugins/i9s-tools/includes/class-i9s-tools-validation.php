<?php
/**
 * Data Validation Tools
 *
 * @package i9s_Tools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class I9S_Tools_Validation {
    
    /**
     * Initialize validation tools
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu_page'), 25);
    }
    
    /**
     * Add validation submenu page
     */
    public static function add_menu_page() {
        add_submenu_page(
            'i9s-tools',
            'Data Validation',
            'Data Validation',
            'manage_options',
            'i9s-validation',
            array(__CLASS__, 'render_page')
        );
    }
    
    /**
     * Render validation page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>Data Validation</h1>
            <p>Check for data quality issues across the database.</p>
            
            <div class="i9s-validation-section">
                <h2>Pitching Statistics</h2>
                
                <?php self::check_pitching_years_without_er(); ?>
                
                <?php self::check_pitching_years_zero_ip(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check for pitching years without ER values
     */
    private static function check_pitching_years_without_er() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT p.id, p.pid, p.yr, p.ip, p.g
            FROM {$wpdb->prefix}pods_playeryear_p p
            WHERE p.er IS NULL OR p.er = ''
            ORDER BY p.pid ASC, p.yr ASC
        ");
        
        ?>
        <div class="i9s-validation-check">
            <h3>Pitching Years Without ER</h3>
            <?php if (empty($results)) : ?>
                <p style="color: green;">✓ All pitching records have ER values.</p>
            <?php else : ?>
                <p style="color: orange;">⚠ Found <?php echo count($results); ?> pitching year(s) without ER values:</p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Player ID</th>
                            <th>Year</th>
                            <th>IP</th>
                            <th>G</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row->pid); ?></td>
                                <td><?php echo esc_html($row->yr); ?></td>
                                <td><?php echo esc_html($row->ip); ?></td>
                                <td><?php echo esc_html($row->g); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pods-manage-playeryear_p&action=edit&id=' . $row->id); ?>" target="_blank">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Check for pitching years with 0 IP
     */
    private static function check_pitching_years_zero_ip() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT p.id, p.pid, p.yr, p.g, p.gs, p.er
            FROM {$wpdb->prefix}pods_playeryear_p p
            WHERE p.ip = 0 OR p.ip IS NULL
            ORDER BY p.pid ASC, p.yr ASC
        ");
        
        ?>
        <div class="i9s-validation-check" style="margin-top: 30px;">
            <h3>Pitching Years with 0 IP</h3>
            <?php if (empty($results)) : ?>
                <p style="color: green;">✓ All pitching records have IP values.</p>
            <?php else : ?>
                <p style="color: orange;">⚠ Found <?php echo count($results); ?> pitching year(s) with 0 or NULL IP:</p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Player ID</th>
                            <th>Year</th>
                            <th>G</th>
                            <th>GS</th>
                            <th>ER</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row->pid); ?></td>
                                <td><?php echo esc_html($row->yr); ?></td>
                                <td><?php echo esc_html($row->g); ?></td>
                                <td><?php echo esc_html($row->gs); ?></td>
                                <td><?php echo esc_html($row->er); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pods-manage-playeryear_p&action=edit&id=' . $row->id); ?>" target="_blank">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize
I9S_Tools_Validation::init();
