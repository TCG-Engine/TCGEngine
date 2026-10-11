<?php
// End to end, through the real endpoints: a goldfish game where P1 mulligans, presses Undo until the
// mulligan prompt is back, and mulligans again — the second mulligan must redraw the SAME hand.
// Every request bumps the gamestate's update number, so this catches anything request-scoped that leaks
// into the shuffle seed (the in-process guards cannot: test_mulligan_undo_update_number.php explains).
// RUN VIA CLI (it calls Apache inside the container; logs in as claudebot1):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_mulligan_undo_e2e_http.php
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'NetworkingLibraries'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/TurnController.php'; include_once './SWUSim/Custom/GameLogic.php';

$B = 'http://localhost/TCGEngine/';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
function mue_req(string $url, string $jar, ?array $post = null): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
}
$jar = tempnam(sys_get_temp_dir(), 'mue');
mue_req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar, ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass']);
$deck = trim(implode("\n", array_filter(explode("\n", file_get_contents('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/darth-vader_jtl_yellow.txt')), fn($l) => !str_starts_with($l, '#'))));
$join = json_decode(mue_req($B . 'APIs/Lobbies/JoinQueue.php', $jar, ['rootName' => 'SWUSim', 'format' => 'goldfish', 'deckLink' => $deck]), true);
$check(!empty($join['success']) && !empty($join['gameName']), 'a goldfish game was created');
if (empty($join['gameName'])) { echo "\nCANNOT CONTINUE\n"; exit(1); }
$gn = (string)$join['gameName']; $key = (string)$join['authKey'];
$input = fn(int $mode, string $cardID = '') => json_decode(mue_req($B . 'ProcessInput.php?' . http_build_query(
    ['gameName' => $gn, 'playerID' => 1, 'authKey' => $key, 'folderPath' => 'SWUSim', 'mode' => $mode, 'cardID' => $cardID, 'responseFormat' => 'json']), $jar), true);

// Read the saved gamestate (what the next request will load).
$probe = 'mue_' . getmypid(); @mkdir("./SWUSim/Games/$probe", 0777, true);
$read = function () use ($gn, $probe) {
    global $gameName;
    copy("./SWUSim/Games/$gn/Gamestate.txt", "./SWUSim/Games/$probe/Gamestate.txt");
    $gameName = $probe; InitializeGamestate(); ParseGamestate('./SWUSim/');
    $live = fn($z) => array_values(array_filter($z, fn($o) => empty($o->removed)));
    $dq = $live((array)GetDecisionQueue(1));
    return ['hand' => array_map(fn($c) => $c->CardID, $live(GetHand(1))),
        'mulliganPending' => !empty($dq) && $dq[0]->Type === 'YESNO' && stripos((string)$dq[0]->Param, 'mulligan') !== false];
};

$s0 = $read();
$check($s0['mulliganPending'], 'the game opens on the mulligan prompt');
$check(!empty($input(100, 'YES')['success']), 'mulligan #1 accepted');
$hand1 = $read()['hand'];
$check(count($hand1) === 6 && $hand1 !== $s0['hand'], 'mulligan #1 drew a new hand');

$undos = 0;
while ($undos < 4) {
    $undos++;
    $input(10004);
    if ($read()['mulliganPending']) break;
}
$back = $read();
$check($back['mulliganPending'] && $back['hand'] === $s0['hand'], "Undo returns to the mulligan prompt with the opening hand (after $undos undo(s))");
// The undo point before the resource pick must not be a step that looks like nothing happened.
$check($undos === 1, "ONE Undo after a mulligan is enough to get back to the mulligan prompt (took $undos)");

$check(!empty($input(100, 'YES')['success']), 'mulligan #2 accepted');
$hand2 = $read()['hand'];
$check($hand2 === $hand1, 'the second mulligan redraws the SAME hand as the first'
    . "\n      first : " . implode(',', $hand1)
    . "\n      second: " . implode(',', $hand2));

// The game's input replay re-runs those shuffles: it must reproduce the live hands, undo and all.
$check(!empty($input(10006)['success']), 'P1 concedes (a replay can only be saved once the game is over)');
$dl = json_decode(mue_req($B . 'APIs/MatchReplay.php?' . http_build_query(['action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $gn, 'playerID' => 1, 'authKey' => $key]), $jar), true);
$check(!empty($dl['success']) && is_array($dl['replay'] ?? null), 'the replay downloads');
$ch = curl_init($B . 'APIs/MatchReplay.php?action=import');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode(['replay' => $dl['replay'] ?? null])]);
$imp = json_decode((string)curl_exec($ch), true); curl_close($ch);
$check(!empty($imp['success']), 'the replay imports');
$rg = (string)($imp['gameName'] ?? '');
$step = fn() => json_decode(mue_req($B . 'ProcessInput.php?' . http_build_query(['gameName' => $rg, 'playerID' => 1, 'authKey' => $imp['authKey'] ?? '', 'folderPath' => 'SWUSim', 'mode' => 11101, 'responseFormat' => 'json']), $jar), true);
$replayHand = function () use ($rg, $probe) {
    global $gameName;
    copy("./SWUSim/Games/$rg/Gamestate.txt", "./SWUSim/Games/$probe/Gamestate.txt");
    $gameName = $probe; InitializeGamestate(); ParseGamestate('./SWUSim/');
    return array_map(fn($c) => $c->CardID, array_values(array_filter(GetHand(1), fn($c) => empty($c->removed))));
};
$step();
$check($replayHand() === $hand1, 'replay: the first mulligan reproduces the live hand');
for ($i = 0; $i < $undos; $i++) $step();
$step();
$check($replayHand() === $hand2, 'replay: undo + the second mulligan reproduce the live hand (' . implode(',', $replayHand()) . ')');

array_map('unlink', glob("./SWUSim/Games/$probe/*") ?: []); @rmdir("./SWUSim/Games/$probe");
@unlink($jar);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
