<?php
/**
 * Auto-generate pyr_auto field for PlayerYear records
 *
 * @package i9s_Tools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class I9S_Tools_PYR_AutoGen {
    
    /**
     * Initialize the hooks
     */
    public function __construct() {
        // Hook into Pods save for batting records
        add_action( 'pods_api_post_save_pod_item_playeryear_b', array( $this, 'autogenerate_batting' ), 10, 3 );
        
        // Hook into Pods save for pitching records
        add_action( 'pods_api_post_save_pod_item_playeryear_p', array( $this, 'autogenerate_pitching' ), 10, 3 );
    }
    
    /**
     * Auto-generate pyr_auto for batting records
     *
     * @param array $pieces The Pods save data
     * @param bool $is_new_item Whether this is a new item
     * @param int $id The item ID
     */
    public function autogenerate_batting( $pieces, $is_new_item, $id ) {
        global $wpdb;
        
        // Get the pid and yr values that were just saved
        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT pid, yr FROM {$wpdb->prefix}pods_playeryear_b WHERE id = %d",
            $id
        ) );
        
        // If we have both pid and yr, generate pyr_auto
        if ( $record && ! empty( $record->pid ) && ! empty( $record->yr ) ) {
            $pyr_auto = $record->pid . ' (' . $record->yr . ')';
            
            // Update the pyr_auto field
            $wpdb->update(
                $wpdb->prefix . 'pods_playeryear_b',
                array( 'pyr_auto' => $pyr_auto ),
                array( 'id' => $id ),
                array( '%s' ),
                array( '%d' )
            );
        }
    }
    
    /**
     * Auto-generate pyr_auto for pitching records
     *
     * @param array $pieces The Pods save data
     * @param bool $is_new_item Whether this is a new item
     * @param int $id The item ID
     */
    public function autogenerate_pitching( $pieces, $is_new_item, $id ) {
        global $wpdb;
        
        // Get the pid and yr values that were just saved
        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT pid, yr FROM {$wpdb->prefix}pods_playeryear_p WHERE id = %d",
            $id
        ) );
        
        // If we have both pid and yr, generate pyr_auto
        if ( $record && ! empty( $record->pid ) && ! empty( $record->yr ) ) {
            $pyr_auto = $record->pid . ' (' . $record->yr . ')';
            
            // Update the pyr_auto field
            $wpdb->update(
                $wpdb->prefix . 'pods_playeryear_p',
                array( 'pyr_auto' => $pyr_auto ),
                array( 'id' => $id ),
                array( '%s' ),
                array( '%d' )
            );
        }
    }
}

// Initialize the class
new I9S_Tools_PYR_AutoGen();
