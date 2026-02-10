<?php
/**
 * Career Statistics Calculations
 * 
 * Calculates career totals and formatted summary lines for players
 */

if (!defined('ABSPATH')) {
    exit;
}

class I9S_Tools_Career_Calculations {
    
    /**
     * Calculate career batting summary line
     * Format: "Career Batting: .338 BA .411 OBP .527 SLG. 89 HR. 1,892 PA (1920-1938)."
     * 
     * @param string $pid Player ID
     * @return string Formatted summary or empty string if no batting records
     */
    public static function calculate_career_batting_summary($pid) {
        global $wpdb;
        
        $results = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                MIN(yr) as first_year,
                MAX(yr) as last_year,
                SUM(ab) as total_ab,
                SUM(h) as total_h,
                SUM(`2b`) as total_2b,
                SUM(`3b`) as total_3b,
                SUM(hr) as total_hr,
                SUM(bb) as total_bb
            FROM {$wpdb->prefix}pods_playeryear_b
            WHERE pid = %s",
            $pid
        ));
        
        // No batting records (check for NULL or 0)
        if (!$results || !$results->total_ab || $results->total_ab == 0) {
            return '';
        }
        
        // Calculate career stats using same formulas as yearly
        // HBP and SF default to 0 (fields don't exist yet)
        $ba = I9S_Tools_Batting_Calculations::calculate_ba($results->total_h, $results->total_ab);
        $obp = I9S_Tools_Batting_Calculations::calculate_obp(
            $results->total_h, 
            $results->total_bb, 
            $results->total_ab,
            0,  // HBP - not tracked yet
            0   // SF - not tracked yet
        );
        $slg = I9S_Tools_Batting_Calculations::calculate_slg(
            $results->total_h,
            $results->total_2b,
            $results->total_3b,
            $results->total_hr,
            $results->total_ab
        );
        
        // Calculate PA (plate appearances) - just AB + BB for now
        $pa = $results->total_ab + $results->total_bb;
        
        // Format with commas
        $pa_formatted = number_format($pa, 0);
        
        // Build summary line
        $summary = sprintf(
            'Career Batting: %s BA %s OBP %s SLG. %d HR. %s PA (%d-%d).',
            $ba,
            $obp,
            $slg,
            $results->total_hr,
            $pa_formatted,
            $results->first_year,
            $results->last_year
        );
        
        return $summary;
    }
    
    /**
     * Calculate career pitching summary line
     * Format: "Career Pitching: 2.63 ERA. 1.09 WHIP. 1,654.2 IP (1920-1937)."
     * 
     * @param string $pid Player ID
     * @return string Formatted summary or empty string if no pitching records
     */
    public static function calculate_career_pitching_summary($pid) {
        global $wpdb;
        
        $results = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                MIN(yr) as first_year,
                MAX(yr) as last_year,
                SUM(ip) as total_ip,
                SUM(ha) as total_ha,
                SUM(w) as total_w,
                SUM(COALESCE(er, 0)) as total_er
            FROM {$wpdb->prefix}pods_playeryear_p
            WHERE pid = %s",
            $pid
        ));
        
        // No pitching records
        if (!$results || $results->total_ip == 0) {
            return '';
        }
        
        // Calculate career stats using same formulas as yearly
        $era = I9S_Tools_Pitching_Calculations::calculate_era($results->total_er, $results->total_ip);
        $whip = I9S_Tools_Pitching_Calculations::calculate_whip($results->total_ha, $results->total_w, $results->total_ip);
        
        // Format IP with commas, drop .0 for whole numbers
        $ip_formatted = number_format($results->total_ip, 1);
        // Remove .0 if it's a whole number
        if (substr($ip_formatted, -2) === '.0') {
            $ip_formatted = substr($ip_formatted, 0, -2);
        }
        
        // Build summary line
        $summary = sprintf(
            'Career Pitching: %s ERA. %s WHIP. %s IP (%d-%d).',
            $era,
            $whip,
            $ip_formatted,
            $results->first_year,
            $results->last_year
        );
        
        return $summary;
    }
    
    /**
     * Get batting totals for TOTALS row display
     * 
     * @param string $pid Player ID
     * @return object|null Object with totals or null if no records
     */
    public static function get_batting_totals($pid) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(ab) as ab,
                SUM(h) as h,
                SUM(`2b`) as doubles,
                SUM(`3b`) as triples,
                SUM(hr) as hr,
                SUM(bb) as bb,
                SUM(so) as so,
                SUM(sb) as sb,
                SUM(cs) as cs
            FROM {$wpdb->prefix}pods_playeryear_b
            WHERE pid = %s",
            $pid
        ));
    }
    
    /**
     * Get pitching totals for TOTALS row display
     * 
     * @param string $pid Player ID
     * @return object|null Object with totals or null if no records
     */
    public static function get_pitching_totals($pid) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(g) as g,
                SUM(gs) as gs,
                SUM(ip) as ip,
                SUM(ha) as ha,
                SUM(w) as w,
                SUM(k) as k,
                SUM(hra) as hra,
                SUM(COALESCE(ed, 0)) as ed,
                SUM(COALESCE(et, 0)) as et,
                SUM(COALESCE(ehbp, 0)) as ehbp,
                SUM(COALESCE(wp, 0)) as wp,
                SUM(COALESCE(bk, 0)) as bk,
                SUM(COALESCE(er, 0)) as er
            FROM {$wpdb->prefix}pods_playeryear_p
            WHERE pid = %s",
            $pid
        ));
    }
}
