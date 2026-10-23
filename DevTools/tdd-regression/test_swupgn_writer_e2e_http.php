<?php
// SWU-PGN writer, end to end: a real private Premier game between claudebot1 and claudebot2, every move a real seat input
// through ProcessInput.php — a mulligan, plays, attacks, an undo, a bookmark taken and loaded, a concession. Then: the
// three files check clean, each player's file names none of the cards the OTHER player held hidden, and each export is
// that seat's own file.
// RUN UNDER APACHE — it reads the live game (Apache's APCu) to see each seat's legal moves:
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/DevTools/tdd-regression/test_swupgn_writer_e2e_http.php
error_reporting(E_ALL & ~E_DEPRECATED);
set_time_limit(0);
if (PHP_SAPI !== 'cli') header('Content-Type: text/plain');
chdir(__DIR__ . '/../..');
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
restore_error_handler();   // the engine below may notice; the checks are what this test reports
require_once './Core/EngineActionRunner.php';
require_once './APIs/Lobbies/Classes/Player.php';
EngineLoadRootRuntime('SWUSim');
require_once './SWUSim/CreateGame.php';
require_once './SWUSim/BotLegalActions.php';
if (PHP_SAPI === 'cli') { echo "FAIL: run under Apache (see the header)\n"; exit(1); }

$B = 'http://localhost/TCGEngine/';
function e2e_req(string $url, string $jar, ?array $post = null, ?int &$status = null): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)); curl_close($ch); return (string)$r;
}
$deckOf = fn(string $f) => trim(implode("\n", array_filter(explode("\n", file_get_contents("./SWUSim/Tests/BotFixtures/ash-meta-2026-09/$f.txt")),
    fn($l) => !str_starts_with($l, '#'))));

// ── A private Premier room, two accounts, started ───────────────────────────────────────────────────
$jar = [1 => tempnam(sys_get_temp_dir(), 'e2e1'), 2 => tempnam(sys_get_temp_dir(), 'e2e2')];
foreach ([1 => 'claudebot1', 2 => 'claudebot2'] as $s => $u) e2e_req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar[$s], ['submit' => '1', 'userID' => $u, 'password' => 'pass']);
$host = json_decode(e2e_req($B . 'APIs/Lobbies/JoinQueue.php', $jar[1], ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier',
    'deckLink' => $deckOf('darth-vader_jtl_yellow')]), true);
$guest = json_decode(e2e_req($B . 'APIs/Lobbies/JoinQueue.php', $jar[2], ['rootName' => 'SWUSim', 'privateInviteCode' => $host['inviteCode'] ?? '',
    'deckLink' => $deckOf('director-krennic_law_blue')]), true);
$sr = json_decode(e2e_req($B . 'APIs/Lobbies/StartRoom.php', $jar[1], ['rootName' => 'SWUSim', 'lobbyID' => $host['lobbyID'] ?? '', 'playerID' => 1,
    'authKey' => $host['authKey'] ?? '']), true);
$g = strval($sr['gameName'] ?? '');
SwuPgnTestCheck($g !== '' && intval($guest['playerID'] ?? 0) === 2, 'claudebot1 and claudebot2 are in a private Premier game', json_encode([$host, $guest, $sr]));
if ($g === '') SwuPgnTestFinish();
$key = [1 => strval($host['authKey']), 2 => strval($guest['authKey'])];
SwuPgnTestCheck(is_dir("./SWUSim/Games/$g/Pgn"), 'the game is recording');

// One seat input through the real endpoint.
$send = function (int $seat, array $a) use ($B, $jar, $key, $g): array {
    $q = ['gameName' => $g, 'playerID' => $seat, 'authKey' => $key[$seat], 'folderPath' => 'SWUSim', 'mode' => intval($a['mode']),
        'cardID' => strval($a['cardID'] ?? ''), 'buttonInput' => strval($a['buttonInput'] ?? ''), 'inputText' => strval($a['inputText'] ?? ''),
        'responseFormat' => 'json'];
    foreach ((array)($a['chkInput'] ?? []) as $i => $v) $q["chkInput[$i]"] = $v;
    return json_decode(e2e_req($B . 'ProcessInput.php?' . http_build_query($q), $jar[$seat]), true) ?? [];
};
// The live game, as the server has it now.
$load = function () use ($g) { global $gameName; $gameName = $g; $GLOBALS['gameName'] = $g; ParseGamestate('./SWUSim/'); };
$isPass = fn($a) => str_contains(strval($a['cardID'] ?? ''), '!CustomInput!Pass');

// ── Play ────────────────────────────────────────────────────────────────────────────────────────────
$did = ['mulligan' => false, 'plays' => 0, 'attacks' => 0, 'undo' => false, 'bookmark' => 0, 'afterMark' => 0, 'loaded' => false];
$rejected = 0; $steps = 0;
while ($steps++ < 600) {
    $load();
    if (SWUGetGameWinner() !== 0) break;
    $seat = SWUBotPendingDecisionSeat();
    $deciding = $seat !== 0;
    if (!$deciding) $seat = GetCurrentPhase() === 'MAIN' ? intval(GetTurnPlayer()) : 0;
    if ($seat === 0) break;
    $legal = SWUBotLegalActions($g, $seat);
    $acts = array_values((array)($legal['actions'] ?? []));
    if (!$acts) break;
    $pick = $acts[0];
    if ($deciding) {
        // P1 takes a mulligan when first asked.
        $yes = array_values(array_filter($acts, fn($a) => strval($a['cardID'] ?? '') === 'YES'));
        if (!$did['mulligan'] && $seat === 1 && $yes && stripos(strval($legal['decisionTooltipRaw'] ?? '') . strval($legal['decisionParam'] ?? ''), 'mulligan') !== false) {
            $pick = $yes[0]; $did['mulligan'] = true;
        }
    } else {
        // An undo after the first play; then a bookmark; two more moves; then load it; then play on a little and concede.
        if ($did['plays'] >= 1 && !$did['undo']) {
            $r = $send($seat, ['mode' => 10004]); $did['undo'] = !empty($r['success']) || $r === []; continue;
        }
        if ($did['undo'] && $did['bookmark'] === 0 && $seat === 1) {
            $send(1, ['mode' => 10018, 'inputText' => 'e2e']);
            $load();
            for ($id = 1; $id <= 20 && $did['bookmark'] === 0; $id++) if (BookmarkRead($id) !== null) $did['bookmark'] = $id;
            continue;
        }
        if ($did['bookmark'] > 0 && !$did['loaded'] && $did['afterMark'] >= 2) {
            $r = $send(1, ['mode' => 10019, 'buttonInput' => strval($did['bookmark'])]); $did['loaded'] = true; continue;
        }
        if ($did['loaded'] && $did['attacks'] >= 1 && $did['plays'] >= 2) break;
        $attack = array_values(array_filter($acts, fn($a) => preg_match('/^my(Ground|Space)Arena-\d+!FSM!$/', strval($a['cardID'] ?? ''))));
        $play = array_values(array_filter($acts, fn($a) => str_starts_with(strval($a['cardID'] ?? ''), 'myHand-')));
        $pass = array_values(array_filter($acts, $isPass));
        $pick = $attack[0] ?? $play[0] ?? $pass[0] ?? $acts[0];
        if (isset($attack[0]) && $pick === $attack[0]) $did['attacks']++;
        if (isset($play[0]) && $pick === $play[0]) $did['plays']++;
        if ($did['bookmark'] > 0 && !$did['loaded']) $did['afterMark']++;
    }
    $r = $send($seat, $pick);
    if (isset($r['success']) && $r['success'] === false) $rejected++;
}
SwuPgnTestCheck($did['mulligan'] && $did['plays'] >= 2 && $did['attacks'] >= 1 && $did['undo'] && $did['bookmark'] > 0 && $did['loaded'],
    'played: a mulligan, plays, an attack, an undo, a bookmark taken and loaded', json_encode($did + ['steps' => $steps, 'rejected' => $rejected]));
$c = $send(2, ['mode' => 10006]);
SwuPgnTestCheck(!empty($c['success']), 'claudebot2 concedes', json_encode($c));

// ── The three files ─────────────────────────────────────────────────────────────────────────────────
$out = "./SWUSim/Games/$g/Pgn/out";
$text = [];
foreach (['all', 'p1', 'p2'] as $k) $text[$k] = is_file("$out/$k.swupgn") ? file_get_contents("$out/$k.swupgn") : '';
SwuPgnTestCheck($text['all'] !== '' && $text['p1'] !== '' && $text['p2'] !== '', 'the game wrote all, p1 and p2');
foreach ($text as $k => $t) if ($t !== '') SwuPgnTestEq(_SwuPgnFileProblems($t), [], "$k.swupgn validates and folds to its own keyframes");
$all = $text['all'] !== '' ? SwuPgnParse($text['all']) : ['events' => [], 'headers' => [], 'decks' => []];
$types = array_count_values(array_column($all['events'], 't'));
SwuPgnTestCheck(($types['MULLIGAN'] ?? 0) >= 1 && ($types['ATTACK'] ?? 0) >= 1 && ($types['UNDO'] ?? 0) >= 1
    && (($types['PLAY'] ?? 0) + ($types['PLAY_EVENT'] ?? 0) + ($types['PLAY_UPGRADE'] ?? 0)) >= 1,
    'the record has the mulligan, plays, an attack and its UNDO marks', json_encode($types));
$end = array_values(array_filter($all['events'], fn($e) => $e['t'] === 'GAME_END'))[0] ?? [];
SwuPgnTestEq([$end['winner'] ?? null, $end['reason'] ?? null, $all['headers']['Result'] ?? null], [1, 'Concession', 'P1'], 'GAME_END: P1 by concession');
SwuPgnTestCheck(intval($all['headers']['Undos'] ?? 0) >= 2, '[Undos] counts both rewinds (the undo and the bookmark load)', strval($all['headers']['Undos'] ?? ''));

// ── Leak check: a card a player held hidden all game is named nowhere in the other player's file ─────
// By card (SET#n — copy numbers differ between the files). Public = named by any record outside a draw/search into or
// out of a hidden zone; a card both decks run is skipped (its name is the other player's own card too).
$deckIds = function (int $seat) use ($all): array {   // DECKS lines: {"p":1, "deck":[["SOR#95",3], ...]}
    $ids = [];
    foreach ($all['decks'] ?? [] as $d) if (intval($d['p'] ?? 0) === $seat) foreach ((array)($d['deck'] ?? []) as $x) $ids[SwuPgnBaseId(strval(is_array($x) ? $x[0] : $x))] = true;
    return $ids;
};
$public = [];
foreach ($all['events'] as $e) {
    if (isset($e['keyframe']) || in_array($e['t'], ['DRAW', 'SEARCH', 'MULLIGAN', 'SHUFFLE'], true)) continue;
    if ($e['t'] === 'MOVE' && in_array($e['from'] ?? '', ['deck', 'hand'], true) && in_array($e['to'] ?? '', ['deck', 'hand'], true)) continue;
    foreach (['card', 'atk', 'def', 'tgt', 'src', 'target', 'attachedTo', 'token', 'by', 'defeatedBy'] as $f) if (is_string($e[$f] ?? null)) $public[SwuPgnBaseId($e[$f])] = true;
    foreach ((array)($e['offered'] ?? []) as $o) if (is_string($o)) $public[SwuPgnBaseId($o)] = true;
}
foreach ([[2, 'p1'], [1, 'p2']] as [$owner, $file]) {
    $hidden = array_keys(array_diff_key($deckIds($owner), $public, $deckIds(3 - $owner)));
    $leaks = array_values(array_filter($hidden, fn($id) => preg_match('/(?<![A-Z0-9#])' . preg_quote($id, '/') . '(?![0-9])/', $text[$file]) === 1));
    SwuPgnTestCheck(count($hidden) > 0 && $leaks === [], "$file.swupgn names none of the " . count($hidden) . " cards P$owner kept hidden", json_encode($leaks));
}

// ── Exports: each seat gets its own file ────────────────────────────────────────────────────────────
foreach ([1, 2] as $s) {
    $r = json_decode(e2e_req($B . 'SWUSim/SwuPgnExport.php?' . http_build_query(['gameName' => $g, 'playerID' => $s, 'authKey' => $key[$s]]), $jar[$s], null, $st), true);
    SwuPgnTestCheck($st === 200 && strval($r['pgn'] ?? '') === $text["p$s"], "seat $s's export is p$s.swupgn", "$st " . substr(json_encode($r), 0, 200));
}

@unlink($jar[1]); @unlink($jar[2]);
SwuPgnTestFinish();
