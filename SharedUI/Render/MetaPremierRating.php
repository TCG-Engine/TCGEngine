<?php
// Profile "Meta Premier" panel — the owner's OWN rating only; there is no leaderboard (owner, 2026-10-03).
// docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §5.3. Renders nothing when logged out, and the
// empty state (never an error) when migration 17 has not been run.
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../../SWUSim/MetaPremier.php';

function RenderMetaPremierRating($userId, ?mysqli $conn = null): string {
    $userId = (int)$userId;
    if ($userId <= 0) return '';
    try { $conn = $conn ?? GetLocalMySQLConnection(); } catch (Throwable $e) { $conn = null; }
    $rows = '';
    foreach (SWUQueueTypeDefinitions() as $qt => $def) {
        if (!SWUFormatAllowsQueueType('metapremier', $qt)) continue;   // the Bo1 row appears when its switch flips
        $r = null;
        try { $r = $conn ? SWUMetaPremierGetRating($conn, $userId, $qt) : null; } catch (Throwable $e) { $r = null; }
        $label = htmlspecialchars($def['displayName'], ENT_QUOTES, 'UTF-8');
        if (!$r || intval($r['games']) === 0) {
            $rows .= "<li class='mp-row'><span class='mp-label'>$label</span><span class='mp-value'>No rated matches yet</span></li>";
            continue;
        }
        // While RD is high the number is still settling — shown as "1500?" (Lichess convention).
        $num = (string)round(floatval($r['rating'])) . (floatval($r['rd']) > MP_PROVISIONAL_RD ? '?' : '');
        $rows .= "<li class='mp-row'><span class='mp-label'>$label</span><span class='mp-value'>$num</span>"
               . "<span class='mp-record'>" . intval($r['wins']) . '–' . intval($r['losses']) . ' · ' . intval($r['games']) . " matches</span></li>";
    }
    return "<div class='metaPremierRating container bg-black'><h2>Meta Premier</h2>"
         . "<p class='note'>Your rating is visible only to you. A ? means it's still settling.</p>"
         . "<ul class='mp-list'>$rows</ul></div>";
}
