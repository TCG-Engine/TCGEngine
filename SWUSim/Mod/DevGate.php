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

// Who may start an Arenabot game. OPENED TO EVERY LOGGED-IN PLAYER (owner, 2026-09-18) — it was
// admin-only from 2026-09-15 so approved moderators could try it and give feedback, and that trial is over.
// Local dev exactly as before (SWUIsLocalDevRequest); elsewhere, any account. The ONLY remaining gate is
// being signed in, because a bot game still creates a real game and needs a player identity.
// Used by the menu (SharedUI/Sites/SWUSim/MainMenu.php) AND the endpoint (APIs/Lobbies/JoinQueue.php), so a
// hand-built request cannot bypass the menu. Reads the session directly: the caller has already started it.
//
// ⚠ Bot games must stay OUT of real stats, and two independent layers keep them there — neither is affected
// by opening this gate, but both must hold if that ever changes: (1) 'botpractice' is localMode + enabled=false
// in AppCore/SWU/Formats.php, so SWUStatsFormats() excludes it; (2) the solo branch of JoinQueue calls
// SWUSetupGame() directly and never creates a MATCH, and only the match hook submits stats.
// Test: SWUSim/DevTools/tests/bot_practice_gate_test.php.
function SWUBotPracticeAllowed(): bool {
    if (SWUIsLocalDevRequest()) return true;
    return strval($_SESSION['useruid'] ?? '') !== '';
}

// JoinQueue's refusal for a Bot Practice request that SWUBotPracticeAllowed() rejects: the message, or null to proceed.
// A function so the refusal path is testable without a production web server (the local container's Apache runs with
// DEVENV=true, so every local HTTP request is "local dev").
function SWUBotPracticeRefusal(string $format): ?string {
    // "Arenabot" is the player-facing name of the botpractice format (owner, 2026-09-16); the id and this gate keep their names.
    // Since 2026-09-18 the only account that can be refused is a logged-OUT one, so the message says what to do
    // about it rather than naming a tester list that no longer gates anything.
    return ($format === 'botpractice' && !SWUBotPracticeAllowed()) ? 'Sign in to play Arenabot.' : null;
}
