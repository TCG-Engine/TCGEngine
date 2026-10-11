<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_poll_hides_replay_state.php
//
// HIDDEN-INFORMATION LEAK. The MatchReplay module stores the game's starting gamestate (BOTH decks in
// shuffled order, BOTH hands) and the raw input log in two global Value fields. The schema marks them
// `Visibility=None`, but the generated GetNextTurn.php echoed Value fields regardless, so every poll
// handed them to every viewer — an opponent could decode the MR1: blob mid-game and read their
// opponent's future draws. Nothing on the client reads them: Save Replay / import / playback all go
// through APIs/MatchReplay.php, which reads the gamestate server-side.
//
// The fix keeps both POSITIONS in the poll (the remote-frontend guide documents them) and sends the
// fields' empty value, "-". Needs SWUSim/GetNextTurn.php regenerated from zzGameCodeGenerator.php.
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

$fails = 0;
$check = function (bool $ok, string $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$gn = swuchat_make_game(SWUCHAT_SCHEMA_PREMIER);
$check($gn !== '', 'fixture game created');

// Any action starts the recording: the replay's initial state is captured lazily at the first one.
swuchat_http('ProcessInput.php?' . http_build_query([
  'gameName' => $gn, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'SWUSim',
  'mode' => 10002, 'cardID' => 'myHand-0!FSM!',
]));

// Precondition: the secret really exists on the server, or the absence checks below prove nothing.
$state = (string)@file_get_contents(dirname(__DIR__, 2) . "/SWUSim/Games/$gn/Gamestate.txt");
$recorded = preg_match('/^MR1:/m', $state) === 1;
$check($recorded, 'the server has recorded the replay initial state (an MR1: line in Gamestate.txt)');

foreach (['1', '2', 'S'] as $viewer) {
  $raw = swuchat_poll_full_raw($gn, $viewer);
  $parts = explode('<~>', $raw);
  $check(count($parts) > 31, "viewer $viewer: got a full board poll (" . count($parts) . ' fields)');
  $check(strpos($raw, 'MR1:') === false, "viewer $viewer: no MR1: replay blob anywhere in the poll");
  // Positions kept: NextTurnRender reads the replay fields at responseArr[30] and [31].
  $check(($parts[30] ?? null) === '-' && ($parts[31] ?? null) === '-', "viewer $viewer: both replay fields keep their position and read '-'"
    . (($parts[30] ?? '') !== '-' ? ' (field 30 starts ' . substr($parts[30] ?? '', 0, 12) . ')' : ''));
}

// ── Today's replays still save and reload: the server-side path never needed the poll copy ──
swuchat_http('ProcessInput.php?' . http_build_query([
  'gameName' => $gn, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'SWUSim', 'mode' => 10006,
]));
$dl = json_decode(swuchat_http('APIs/MatchReplay.php?' . http_build_query([
  'action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $gn, 'playerID' => '2', 'authKey' => 'testschema',
])), true);
$replay = $dl['replay'] ?? [];
$check(($dl['success'] ?? false) === true && str_contains(strval($replay['initialGamestate'] ?? ''), "\n") && count($replay['actions'] ?? []) >= 2,
  'after the game ends, Save Replay downloads the full initial gamestate and the recorded actions (' . count($replay['actions'] ?? []) . ')');

$ch = curl_init(SWUCHAT_BASE . 'APIs/MatchReplay.php?action=import');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode(['replay' => $replay]), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
$imp = json_decode((string)curl_exec($ch), true);
curl_close($ch);
$check(($imp['success'] ?? false) === true && ($imp['gameName'] ?? '') !== '', 'the downloaded replay imports into a playback game');

if (($imp['success'] ?? false) === true) {
  $q = ['gameName' => $imp['gameName'], 'playerID' => '1', 'authKey' => $imp['authKey'], 'folderPath' => 'SWUSim'];
  swuchat_http('ProcessInput.php?' . http_build_query($q + ['mode' => 11101]));   // replay Next
  $state = (string)@file_get_contents(dirname(__DIR__, 2) . "/SWUSim/Games/{$imp['gameName']}/Gamestate.txt");
  $cmds = [];
  foreach (explode("\n", $state) as $line) {
    if (str_starts_with($line, 'MR1:') && ($j = json_decode((string)gzdecode(base64_decode(substr(trim($line), 4))), true)) && isset($j['nextActionIndex'])) $cmds = $j;
  }
  $check(($cmds['nextActionIndex'] ?? 0) === 1 && ($cmds['playback'] ?? false) === true, 'replay Next steps the playback game forward one action');
  $raw = swuchat_http('SWUSim/GetNextTurn.php?' . http_build_query($q + ['lastUpdate' => 0, 'lastChatVersion' => 0, 'lastChatID' => 0]));
  $check(count(explode('<~>', $raw)) > 31 && strpos($raw, 'MR1:') === false, 'the playback game\'s own poll renders the board without the replay blob');
}

echo $fails === 0 ? "PASS\n" : "FAIL: $fails check(s)\n";
exit($fails === 0 ? 0 : 1);
