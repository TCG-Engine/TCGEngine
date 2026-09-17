<?php
// DEV/TEST ONLY — rewind a seat's inactivity clock so a 75s/150s timeout can be exercised without
// sleeping. Refuses outside the dev environment (Core/GameAuth.php SimGameIsDevelopmentEnvironment).
//   ?gameName=<n>&seat=<1-4>&back=<seconds>   rewinds acted[seat] AND seen[seat]
//   &actedOnly=1                              rewinds acted only (stall WITHOUT a disconnect)
header('Content-Type: application/json');
require_once __DIR__ . '/../../Core/GameAuth.php';
require_once __DIR__ . '/../../Core/GamePresence.php';
if (!SimGameIsDevelopmentEnvironment()) { http_response_code(403); echo '{}'; exit; }

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['gameName'] ?? ''));
$seat = intval($_GET['seat'] ?? 0);
$back = max(0, intval($_GET['back'] ?? 0));
$actedOnly = strval($_GET['actedOnly'] ?? '') === '1';
if ($gameName === '' || $seat < 1) { echo '{"error":"gameName and seat are required"}'; exit; }

$p = PresenceRead($gameName);
$now = time();
$p['acted'][$seat] = intval($p['acted'][$seat] ?? $now) - $back;
if (!$actedOnly) $p['seen'][$seat] = intval($p['seen'][$seat] ?? $now) - $back;
if (intval($p['since']) > 0) $p['since'] = intval($p['since']) - $back;
// Rewind the WHOLE presence timeline, including any open vote's wait extension ('until') — otherwise a
// rewound clock still reads as "inside a wait" and the prompt stays hidden.
foreach (($p['votes'] ?? []) as $t => $v) {
    if (intval($v['until'] ?? 0) > 0)  $p['votes'][$t]['until']  = intval($v['until']) - $back;
    if (intval($v['opened'] ?? 0) > 0) $p['votes'][$t]['opened'] = intval($v['opened']) - $back;
}
PresenceWrite($gameName, $p);
echo json_encode(PresenceRead($gameName));
