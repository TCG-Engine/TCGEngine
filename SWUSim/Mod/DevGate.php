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

// Who may start an Arenabot game. OPEN TO EVERYONE, GUESTS INCLUDED (owner, 2026-09-21: "we can remove the account
// requirement"). History: admin-only from 2026-09-15 (moderator trial), every logged-in player from 2026-09-18.
// A guest needs no identity here — Goldfish and Hotseat already run guests down the same solo path.
// Kept as a function, not deleted, because it is the one switch that closes Arenabot again: the menu
// (SharedUI/Sites/SWUSim/MainMenu.php) AND the endpoint (APIs/Lobbies/JoinQueue.php) both consult it, so a
// hand-built request cannot bypass the menu.
//
// ⚠ Bot games must stay OUT of real stats, and two independent layers keep them there — neither is affected
// by opening this gate, but both must hold if that ever changes: (1) 'botpractice' is localMode + enabled=false
// in AppCore/SWU/Formats.php, so SWUStatsFormats() excludes it; (2) the solo branch of JoinQueue calls
// SWUSetupGame() directly and never creates a MATCH, and only the match hook submits stats.
// Test: SWUSim/DevTools/tests/bot_practice_gate_test.php.
function SWUBotPracticeAllowed(): bool {
    return true;
}

// JoinQueue's refusal for a Bot Practice request that SWUBotPracticeAllowed() rejects: the message, or null to proceed.
// A function so the refusal path is testable without a production web server (the local container's Apache runs with
// DEVENV=true, so every local HTTP request is "local dev").
function SWUBotPracticeRefusal(string $format): ?string {
    // "Arenabot" is the player-facing name of the botpractice format (owner, 2026-09-16); the id and this gate keep their names.
    // Unreachable while the gate is open to everyone (2026-09-21); if it closes again, signing in would not help, so the
    // message does not say to.
    return ($format === 'botpractice' && !SWUBotPracticeAllowed()) ? 'Arenabot is not available right now.' : null;
}

// Who may CREATE a High Ground room: moderators only, for now (owner, 2026-11-02). Anyone can still JOIN one through its
// invite link — joining never sends the highGround flag, so this gate never sees a join. The menu (only mods see the
// button) and the endpoint (APIs/Lobbies/JoinQueue.php) both consult it, so a hand-built request cannot bypass the menu.
// Opening it to everyone again is `return true;` here. Test: SWUSim/DevTools/tests/high_ground_create_gate_test.php.
function SWUHighGroundCreateAllowed(): bool {
    require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
    return CheckLoggedInUserMod() === '';
}

// JoinQueue's refusal for a High Ground creation that SWUHighGroundCreateAllowed() rejects: the message, or null to proceed.
function SWUHighGroundCreateRefusal(): ?string {
    return SWUHighGroundCreateAllowed() ? null : 'Creating a High Ground room is limited to moderators for now.';
}
