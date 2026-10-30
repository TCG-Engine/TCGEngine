<?php

// Runtime storage under a root is read by PHP from disk and must never be one GET away. The root .htaccess answers 404
// for each storage folder (404, not 403, so a probe cannot confirm an id exists).
//
// 2026-10-30: <Root>/Matches/ was served on prod (petranaki.net …/SWUSim/Matches/MatchIDCounter.txt → 200). Every
// Matches/M<n>/Match.json holds both seats' auth keys and match ids are sequential, so any seat of any match could be
// taken over; the persistent-lobby rooms (Matches/rooms/*.ser) hold seat keys too.
//
// Dev Apache does not apply .htaccess, so this checks the rules themselves: each RewriteRule ... [R=404] pattern, as
// Apache would apply it (case-insensitive with NC), against real storage paths and real pages.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

$fails = 0;
function check($cond, $msg) { global $fails; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $fails++; }

$rules = [];
foreach (file('.htaccess') as $line) {
    if (preg_match('/^\s*RewriteRule\s+(\S+)\s+-\s+\[([^\]]*)\]/', $line, $mm) && str_contains($mm[2], 'R=404')) {
        $rules[] = '#' . str_replace('#', '\#', $mm[1]) . '#' . (str_contains($mm[2], 'NC') ? 'i' : '');
    }
}
// Apache matches the URL path relative to the .htaccess directory (no leading slash).
$denied = function (string $path) use ($rules) {
    foreach ($rules as $re) if (preg_match($re, $path)) return true;
    return false;
};

check(count($rules) >= 5, 'the deny rules were read: ' . count($rules));
foreach ([
    'SWUSim/Matches/M1000/Match.json'            => 'a match record (seat auth keys)',
    'SWUSim/Matches/MatchIDCounter.txt'          => 'the match id counter',
    'SWUSim/Matches/rooms/abc123.ser'            => 'a saved persistent room (seat keys)',
    'SWUSim/Matches/rooms/invite-0a1b2c.json'    => 'a room invite index (the invite code)',
    'GrandArchiveSim/Matches/M5/Match.json'      => 'another root\'s match record',
    'SWUSim/Games/1234/Gamestate.txt'            => 'a gamestate',
    'SWUSim/BugReports/1/report.json'            => 'a bug report',
] as $path => $what) check($denied($path), "$what is not served: $path");

foreach ([
    'SWUSim/MatchFlow.php', 'SWUSim/MatchHooks.php', 'SWUSim/EndGameInfo.php', 'Core/Match/MatchFlow.php',
    'SharedUI/MainMenu.php', 'APIs/Lobbies/PollLobbyUpdates.php', 'NextTurn.php',
] as $page) check(!$denied($page), "a real page is still served: $page");

echo $fails === 0 ? "PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
