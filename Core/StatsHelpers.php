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

?>
