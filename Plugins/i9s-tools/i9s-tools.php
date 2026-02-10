<?php
/**
 * Plugin Name: i9s Database Tools
 * Plugin URI: https://i9s.org
 * Description: Admin tools for managing i9s player database and Pods data
 * Version: 1.4.4
 * Author: i9s Development
 * Author URI: https://i9s.org
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('I9S_TOOLS_VERSION', '1.4.4');
define('I9S_TOOLS_PATH', plugin_dir_path(__FILE__));
define('I9S_TOOLS_URL', plugin_dir_url(__FILE__));

// Include required files
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-admin.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-playerid-fix.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-pyr-autogen.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-pyr-autogenerate.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-master-list.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-batting-calculations.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-pitching-calculations.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-career-calculations.php';
require_once I9S_TOOLS_PATH . 'includes/class-i9s-tools-validation.php';

// Initialize the plugin
function i9s_tools_init() {
    // Only load in admin
    if (is_admin()) {
        new I9S_Tools_Admin();
        new I9S_Tools_Master_List();
    }
    
    // Auto-generate pyr_auto fields (runs on all requests, not just admin)
    new I9s_Tools_Pyr_Autogenerate();
}
add_action('plugins_loaded', 'i9s_tools_init');

// Activation hook
register_activation_hook(__FILE__, 'i9s_tools_activate');
function i9s_tools_activate() {
    // Nothing to do on activation yet
    // Could add capability checks or initial setup here
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'i9s_tools_deactivate');
function i9s_tools_deactivate() {
    // Cleanup if needed
}

// Global wrapper functions for Code Snippets
// These allow Code Snippets to call plugin class methods

function i9s_calculate_ba($h, $ab) {
    return I9S_Tools_Batting_Calculations::calculate_ba($h, $ab);
}

function i9s_calculate_obp($h, $bb, $ab, $hbp = 0, $sf = 0) {
    return I9S_Tools_Batting_Calculations::calculate_obp($h, $bb, $ab, $hbp, $sf);
}

function i9s_calculate_slg($h, $doubles, $triples, $hr, $ab) {
    return I9S_Tools_Batting_Calculations::calculate_slg($h, $doubles, $triples, $hr, $ab);
}

function i9s_calculate_ops($obp, $slg) {
    return I9S_Tools_Batting_Calculations::calculate_ops($obp, $slg);
}

function i9s_calculate_era($er, $ip) {
    return I9S_Tools_Pitching_Calculations::calculate_era($er, $ip);
}

function i9s_calculate_whip($ha, $w, $ip) {
    return I9S_Tools_Pitching_Calculations::calculate_whip($ha, $w, $ip);
}

function i9s_calculate_h9($ha, $ip) {
    return I9S_Tools_Pitching_Calculations::calculate_h9($ha, $ip);
}

function i9s_calculate_bb9($w, $ip) {
    return I9S_Tools_Pitching_Calculations::calculate_bb9($w, $ip);
}

function i9s_calculate_k9($k, $ip) {
    return I9S_Tools_Pitching_Calculations::calculate_k9($k, $ip);
}
