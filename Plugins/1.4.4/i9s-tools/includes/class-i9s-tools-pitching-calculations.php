<?php
/**
 * Pitching Statistics Calculation Functions
 *
 * @package i9s_Tools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class I9S_Tools_Pitching_Calculations {
    
    /**
     * Calculate Earned Run Average (ERA)
     *
     * @param int|float $er Earned Runs
     * @param int|float $ip Innings Pitched
     * @return string Formatted ERA or '---'
     */
    public static function calculate_era($er, $ip) {
        // Treat NULL as 0
        $er = $er ?? 0;
        $ip = $ip ?? 0;
        
        // Division by zero
        if ($ip == 0) {
            return '---';
        }
        
        $result = ($er * 9) / $ip;
        
        // Format: 3.45 (always 2 decimals)
        return number_format($result, 2);
    }
    
    /**
     * Calculate WHIP (Walks + Hits per Inning Pitched)
     *
     * @param int|float $ha Hits Allowed
     * @param int|float $w Walks
     * @param int|float $ip Innings Pitched
     * @return string Formatted WHIP or '---'
     */
    public static function calculate_whip($ha, $w, $ip) {
        // Treat NULL as 0
        $ha = $ha ?? 0;
        $w = $w ?? 0;
        $ip = $ip ?? 0;
        
        // Division by zero
        if ($ip == 0) {
            return '---';
        }
        
        $result = ($ha + $w) / $ip;
        
        // Format: 1.24 (always 2 decimals)
        return number_format($result, 2);
    }
    
    /**
     * Calculate Hits per 9 Innings (H/9)
     *
     * @param int|float $ha Hits Allowed
     * @param int|float $ip Innings Pitched
     * @return string Formatted H/9 or '---'
     */
    public static function calculate_h9($ha, $ip) {
        // Treat NULL as 0
        $ha = $ha ?? 0;
        $ip = $ip ?? 0;
        
        // Division by zero
        if ($ip == 0) {
            return '---';
        }
        
        $result = ($ha * 9) / $ip;
        
        // Format: 8.5 (always 1 decimal)
        return number_format($result, 1);
    }
    
    /**
     * Calculate Walks per 9 Innings (BB/9)
     *
     * @param int|float $w Walks
     * @param int|float $ip Innings Pitched
     * @return string Formatted BB/9 or '---'
     */
    public static function calculate_bb9($w, $ip) {
        // Treat NULL as 0
        $w = $w ?? 0;
        $ip = $ip ?? 0;
        
        // Division by zero
        if ($ip == 0) {
            return '---';
        }
        
        $result = ($w * 9) / $ip;
        
        // Format: 3.2 (always 1 decimal)
        return number_format($result, 1);
    }
    
    /**
     * Calculate Strikeouts per 9 Innings (K/9)
     *
     * @param int|float $k Strikeouts
     * @param int|float $ip Innings Pitched
     * @return string Formatted K/9 or '---'
     */
    public static function calculate_k9($k, $ip) {
        // Treat NULL as 0
        $k = $k ?? 0;
        $ip = $ip ?? 0;
        
        // Division by zero
        if ($ip == 0) {
            return '---';
        }
        
        $result = ($k * 9) / $ip;
        
        // Format: 9.7 (always 1 decimal)
        return number_format($result, 1);
    }
}
