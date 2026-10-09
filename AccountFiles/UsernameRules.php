<?php
// THE username character rule, in one place. Every account-creation path validates through
// UsernameIsValid(): the signup form (Database/functions.inc.php invalidUid), the signup API
// (AccountFiles/SignupAPI.php) and Discord onboarding (AccountFiles/DiscordOnboarding.php). The Discord
// name suggestion (DiscordOAuthSuggestedUsername) strips with the same class, so a suggestion always passes.
//
// Rule (owner, 2026-10-09): ASCII letters, digits, period, dash, underscore. No spaces, no other characters.
// Was letters + digits only (ctype_alnum). Audited before widening: no username reaches a file path, URL
// route, gamestate/decision-queue text, cache/cookie key or LIKE query, so "." "-" "_" are inert everywhere.
// ⚠ SharedUI/Patreons.php and SharedUI/Render/Misc.php interpolate the username into SQL; they stay safe only
// while this class excludes quotes and backslashes. Never widen it to those without fixing them first.

// Length cap = the `users.usersUid` column, varchar(128) in every site's database (swusim, swudeck,
// fabsim, hellbreaksim — checked 2026-10-09). The character class is ASCII-only, so characters = bytes.
// Change both together; a longer name would be truncated or refused by MySQL instead of by this rule.
if (!defined('USERNAME_MAX_LENGTH')) define('USERNAME_MAX_LENGTH', 128);

if (!function_exists('UsernameIsValid')) {
    function UsernameIsValid($username): bool {
        // \z, not $: "$" also matches before a trailing newline, which would let "name\n" through.
        return is_string($username) && strlen($username) <= USERNAME_MAX_LENGTH
            && preg_match('/^[A-Za-z0-9._-]+\z/', $username) === 1;
    }
}

if (!function_exists('UsernameStripInvalidChars')) {
    function UsernameStripInvalidChars(string $s): string {
        return preg_replace('/[^A-Za-z0-9._-]/', '', $s);
    }
}

if (!function_exists('UsernameRuleMessage')) {
    function UsernameRuleMessage(): string {
        return 'The username can be up to ' . USERNAME_MAX_LENGTH . ' characters and can only contain letters, numbers, periods (.), dashes (-) and underscores (_).';
    }
}
