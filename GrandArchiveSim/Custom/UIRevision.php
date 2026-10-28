<?php
// Opt-in concurrency guard for the React trial. Call only after authentication,
// acquiring the game write lock, and parsing the current authoritative state.
function GAUIRevisionError($expected, $current, $lock): ?string
{
    if (!is_resource($lock)) return 'The game could not be locked. Refresh and try again.';
    if (!is_string($expected) || !preg_match('/^(0|[1-9][0-9]{0,15})$/D', $expected)
        || strlen($expected) > strlen((string) PHP_INT_MAX)
        || (strlen($expected) === strlen((string) PHP_INT_MAX) && strcmp($expected, (string) PHP_INT_MAX) > 0)) {
        return 'Invalid expected revision.';
    }
    if (!is_int($current) || (int) $expected !== $current) return 'The game has changed. Refresh before acting.';
    return null;
}
