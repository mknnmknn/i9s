<?php
/**
 * Batting Statistics Calculation Functions
 *
 * @package i9s_Tools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class I9S_Tools_Batting_Calculations {
    
    /**
     * Calculate Batting Average (BA)
     *
     * @param int|float $h Hits
     * @param int|float $ab At Bats
     * @return string Formatted batting average or '---'
     */
    public static function calculate_ba($h, $ab) {
        // Treat NULL as 0
        $h = $h ?? 0;
        $ab = $ab ?? 0;
        
        // Division by zero
        if ($ab == 0) {
            return '---';
        }
        
        $result = $h / $ab;
        
        // Format: .347 (no leading zero for values < 1) or 1.000 (with leading digit)
        if ($result >= 1) {
            return number_format($result, 3);
        } else {
            return '.' . substr(number_format($result, 3), 2);
        }
    }
    
    /**
     * Calculate On-Base Percentage (OBP)
     *
     * @param int|float $h Hits
     * @param int|float $bb Walks
     * @param int|float $ab At Bats
     * @param int|float $hbp Hit By Pitch (optional, defaults to 0)
     * @param int|float $sf Sacrifice Flies (optional, defaults to 0)
     * @return string Formatted OBP or '---'
     */
    public static function calculate_obp($h, $bb, $ab, $hbp = 0, $sf = 0) {
        // Treat NULL as 0
        $h = $h ?? 0;
        $bb = $bb ?? 0;
        $ab = $ab ?? 0;
        $hbp = $hbp ?? 0;
        $sf = $sf ?? 0;
        
        $numerator = $h + $bb + $hbp;
        $denominator = $ab + $bb + $hbp + $sf;
        
        // Division by zero
        if ($denominator == 0) {
            return '---';
        }
        
        $result = $numerator / $denominator;
        
        // Format: .406 (no leading zero for values < 1) or 1.000 (with leading digit)
        if ($result >= 1) {
            return number_format($result, 3);
        } else {
            return '.' . substr(number_format($result, 3), 2);
        }
    }
    
    /**
     * Calculate Slugging Percentage (SLG)
     *
     * @param int|float $h Hits
     * @param int|float $b2 Doubles
     * @param int|float $b3 Triples
     * @param int|float $hr Home Runs
     * @param int|float $ab At Bats
     * @return string Formatted SLG or '---'
     */
    public static function calculate_slg($h, $b2, $b3, $hr, $ab) {
        // Treat NULL as 0
        $h = $h ?? 0;
        $b2 = $b2 ?? 0;
        $b3 = $b3 ?? 0;
        $hr = $hr ?? 0;
        $ab = $ab ?? 0;
        
        // Division by zero
        if ($ab == 0) {
            return '---';
        }
        
        // Calculate singles
        $singles = $h - $b2 - $b3 - $hr;
        
        // Total bases
        $total_bases = ($singles * 1) + ($b2 * 2) + ($b3 * 3) + ($hr * 4);
        
        $result = $total_bases / $ab;
        
        // Format: .574 or 1.234 (show leading digit if >= 1)
        if ($result >= 1) {
            return number_format($result, 3);
        } else {
            return '.' . substr(number_format($result, 3), 2);
        }
    }
    
    /**
     * Calculate OPS (On-Base Plus Slugging)
     *
     * @param string|float $obp On-Base Percentage (can be pre-calculated or numeric)
     * @param string|float $slg Slugging Percentage (can be pre-calculated or numeric)
     * @return string Formatted OPS or '---'
     */
    public static function calculate_ops($obp, $slg) {
        // Handle if either stat is '---'
        if ($obp === '---' || $slg === '---') {
            return '---';
        }
        
        // Convert string format (.347 or 1.234) to float
        if (is_string($obp)) {
            // If starts with '.', prepend '0'
            if (strpos($obp, '.') === 0) {
                $obp = '0' . $obp;
            }
            $obp = floatval($obp);
        }
        
        if (is_string($slg)) {
            // If starts with '.', prepend '0'
            if (strpos($slg, '.') === 0) {
                $slg = '0' . $slg;
            }
            $slg = floatval($slg);
        }
        
        $result = $obp + $slg;
        
        // Format: .843 or 1.022 (show leading digit if >= 1)
        if ($result >= 1) {
            return number_format($result, 3);
        } else {
            return '.' . substr(number_format($result, 3), 2);
        }
    }
}
