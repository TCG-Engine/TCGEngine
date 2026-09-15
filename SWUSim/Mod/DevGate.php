<?php
// Local-dev gate for Mod tools that mutate source/assets (the cosmetics uploader writes to
// Catalog.php + the asset dirs). getenv('DEVENV') is only populated for CLI here (not php-fpm
// over HTTP), so also accept requests whose Host is localhost/loopback — that identifies the
// local dev environment over HTTP while still blocking production (swustats.net).
function SWUIsLocalDevRequest(): bool {
    if (getenv('DEVENV') === 'true') return true;
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return str_starts_with($host, 'localhost')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '[::1]');
}

// Who may start a Bot Practice game (owner, 2026-09-15: admin-only, so other admins can try it and give feedback).
// Local dev exactly as before (SWUIsLocalDevRequest); elsewhere a logged-in approved moderator — the same list
// CheckLoggedInUserMod() gates the admin tools with (AccountFiles/AccountSessionAPI.php ApprovedModeratorUserNames).
// Used by the menu (SharedUI/Sites/SWUSim/MainMenu.php) AND the endpoint (APIs/Lobbies/JoinQueue.php), so a hand-built
// request cannot bypass the menu. Reads the session directly: the caller has already started it.
// Test: SWUSim/DevTools/tests/bot_practice_gate_test.php.
function SWUBotPracticeAllowed(): bool {
    if (SWUIsLocalDevRequest()) return true;
    $user = strval($_SESSION['useruid'] ?? '');
    if ($user === '') return false;
    require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
    return in_array($user, ApprovedModeratorUserNames(), true);
}

// JoinQueue's refusal for a Bot Practice request that SWUBotPracticeAllowed() rejects: the message, or null to proceed.
// A function so the refusal path is testable without a production web server (the local container's Apache runs with
// DEVENV=true, so every local HTTP request is "local dev").
function SWUBotPracticeRefusal(string $format): ?string {
    return ($format === 'botpractice' && !SWUBotPracticeAllowed()) ? 'Bot Practice is currently limited to approved testers.' : null;
}
