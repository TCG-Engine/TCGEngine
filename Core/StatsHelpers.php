<?php
// Helper functions for statistics-related calculations

/**
 * Return the number of full weeks elapsed since the given reference date.
 *
 * - The reference date string should be in a format parseable by DateTime.
 * - Returns 0 on error or if $now is before the reference date.
 * - Example: reference = '2025-09-20'. If today is 2025-09-27, returns 1.
 *
 * @param string $refDateString
 * @param DateTime|null $now
 * @return int
 */
function GetWeekSinceRef($refDateString = '2025-09-20', DateTime $now = null) {
    try {
        $refDate = new DateTime($refDateString);
        $now = $now ?: new DateTime();
        if ($now < $refDate) {
            return 0;
        }
        $days = (int)$refDate->diff($now)->format('%a');
        $week = (int) floor($days / 7);
        return max(0, $week);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Build display labels for the week dropdowns, one per week from 0..$currentWeek.
 *
 * Each label reads like "Wk 40 · Apr 25 – May 1", where the date span is the
 * 7-day window for that week starting from the same reference date used by
 * GetWeekSinceRef(). The array is 0-indexed so json_encode() yields a JS array
 * that can be indexed directly by week number; the option value stays the raw
 * week number so the stats APIs are unaffected.
 *
 * @param int $currentWeek
 * @param string $refDateString
 * @return string[]
 */
function GetWeekLabels($currentWeek, $refDateString = '2025-09-20') {
    $labels = array();
    try {
        for ($w = 0; $w <= $currentWeek; ++$w) {
            $start = new DateTime($refDateString);
            $start->modify('+' . ($w * 7) . ' days');
            $end = clone $start;
            $end->modify('+6 days');
            $labels[$w] = 'Wk ' . $w . ' · ' . $start->format('M j') . ' – ' . $end->format('M j');
        }
    } catch (Exception $e) {
        // Fallback to bare week numbers if date math ever fails.
        $labels = array();
        for ($w = 0; $w <= $currentWeek; ++$w) {
            $labels[$w] = 'Wk ' . $w;
        }
    }
    return $labels;
}

?>
