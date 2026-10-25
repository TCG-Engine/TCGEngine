<?php
// SWU-PGN sample game: a real private Premier game between claudebot1 and claudebot2, every move a real seat input
// through ProcessInput.php (the seat's own key), played to its end (a base destroyed) by a simple policy — attack when
// possible, else play a card, else pass. Optional: a mulligan, an undo and a bookmark taken + loaded. The game's three
// files (all-seeing, P1 view, P2 view) are copied to .superpowers/swupgn-samples/<label>/ and checked by our reader.
// Dev tool. RUN UNDER APACHE (it reads the live game from Apache's APCu to see each seat's legal moves):
//   docker exec otmtcge-swusim-web-server-1 curl -s "http://localhost/TCGEngine/DevTools/swupgn-sample-game.php?label=maul-vs-lando&deck1=darth-maul_lof_blue-force&deck2=lando-calrissian_law_blue&extras=1"
error_reporting(E_ALL & ~E_DEPRECATED);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/..');
require_once './Core/EngineActionRunner.php';
require_once './APIs/Lobbies/Classes/Player.php';
EngineLoadRootRuntime('SWUSim');
require_once './SWUSim/CreateGame.php';
require_once './SWUSim/BotLegalActions.php';

$label = preg_replace('/[^a-z0-9-]/', '', strtolower(strval($_GET['label'] ?? 'sample')));
$deckName = fn(string $k, string $d) => preg_replace('/[^a-z0-9_-]/', '', strval($_GET[$k] ?? $d));
$deck1 = $deckName('deck1', 'darth-vader_jtl_yellow'); $deck2 = $deckName('deck2', 'director-krennic_law_blue');
$extras = !empty($_GET['extras']);
$maxSteps = max(50, intval($_GET['maxSteps'] ?? 2500));
$B = 'http://localhost/TCGEngine/';
function sg_req(string $url, string $jar, ?array $post = null): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
}
// A fixture deck by name, or one of the samples' own decks (.superpowers/swupgn-samples/decks/<name>.txt).
$deckOf = function (string $f) {
    $path = is_file("./.superpowers/swupgn-samples/decks/$f.txt") ? "./.superpowers/swupgn-samples/decks/$f.txt" : "./SWUSim/Tests/BotFixtures/ash-meta-2026-09/$f.txt";
    return trim(implode("\n", array_filter(explode("\n", file_get_contents($path)), fn($l) => !str_starts_with($l, '#'))));
};
$sayYes = !empty($_GET['yes']);
$useAbilities = !empty($_GET['abilities']);   // use leader and unit Actions when offered (each once a round), before attacking
$usedThisRound = [];   // answer YES to every "you may" (except the mulligan): more of each card's effects happen

$jar = [1 => tempnam(sys_get_temp_dir(), 'sg1'), 2 => tempnam(sys_get_temp_dir(), 'sg2')];
foreach ([1 => 'claudebot1', 2 => 'claudebot2'] as $s => $u) sg_req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar[$s], ['submit' => '1', 'userID' => $u, 'password' => 'pass']);
$host = json_decode(sg_req($B . 'APIs/Lobbies/JoinQueue.php', $jar[1], ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'deckLink' => $deckOf($deck1)]), true);
$guest = json_decode(sg_req($B . 'APIs/Lobbies/JoinQueue.php', $jar[2], ['rootName' => 'SWUSim', 'privateInviteCode' => $host['inviteCode'] ?? '', 'deckLink' => $deckOf($deck2)]), true);
$sr = json_decode(sg_req($B . 'APIs/Lobbies/StartRoom.php', $jar[1], ['rootName' => 'SWUSim', 'lobbyID' => $host['lobbyID'] ?? '', 'playerID' => 1, 'authKey' => $host['authKey'] ?? '']), true);
$g = strval($sr['gameName'] ?? '');
if ($g === '') { echo "FAIL: no game: " . json_encode([$host, $guest, $sr]) . "\n"; exit; }
$key = [1 => strval($host['authKey']), 2 => strval($guest['authKey'])];
echo "game $g ($deck1 vs $deck2)\n";

$send = function (int $seat, array $a) use ($B, $jar, $key, $g): array {
    $q = ['gameName' => $g, 'playerID' => $seat, 'authKey' => $key[$seat], 'folderPath' => 'SWUSim', 'mode' => intval($a['mode']),
        'cardID' => strval($a['cardID'] ?? ''), 'buttonInput' => strval($a['buttonInput'] ?? ''), 'inputText' => strval($a['inputText'] ?? ''), 'responseFormat' => 'json'];
    foreach ((array)($a['chkInput'] ?? []) as $i => $v) $q["chkInput[$i]"] = $v;
    return json_decode(sg_req($B . 'ProcessInput.php?' . http_build_query($q), $jar[$seat]), true) ?? [];
};
$load = function () use ($g) { global $gameName; $gameName = $g; $GLOBALS['gameName'] = $g; ParseGamestate('./SWUSim/'); };
$did = ['mulligan' => !$extras, 'plays' => 0, 'attacks' => 0, 'undo' => !$extras, 'bookmark' => $extras ? 0 : -1, 'afterMark' => 0, 'loaded' => !$extras];
$steps = 0; $last = ''; $repeat = 0;
while ($steps++ < $maxSteps) {
    $load();
    if (SWUGetGameWinner() !== 0) break;
    $seat = SWUBotPendingDecisionSeat();
    $deciding = $seat !== 0;
    if (!$deciding) $seat = GetCurrentPhase() === 'MAIN' ? intval(GetTurnPlayer()) : 0;
    if ($seat === 0) { echo "stopped: nobody to act (phase " . GetCurrentPhase() . ")\n"; break; }
    $legal = SWUBotLegalActions($g, $seat);
    $acts = array_values((array)($legal['actions'] ?? []));
    if (!$acts) { echo "stopped: no legal actions for P$seat\n"; break; }
    $pick = $acts[0];
    if ($deciding) {
        $yes = array_values(array_filter($acts, fn($a) => strval($a['cardID'] ?? '') === 'YES'));
        $isMulligan = stripos(strval($legal['decisionTooltipRaw'] ?? '') . strval($legal['decisionParam'] ?? ''), 'mulligan') !== false;
        if (!$did['mulligan'] && $seat === 1 && $yes && $isMulligan) {
            $pick = $yes[0]; $did['mulligan'] = true;
        } elseif ($sayYes && $yes && !$isMulligan) {
            $pick = $yes[0];
        }
    } else {
        if ($did['plays'] >= 1 && !$did['undo']) { $send($seat, ['mode' => 10004]); $did['undo'] = true; continue; }
        if ($did['undo'] && $did['bookmark'] === 0 && $seat === 1) {
            $send(1, ['mode' => 10018, 'inputText' => 'sample']); $load();
            for ($id = 1; $id <= 20 && $did['bookmark'] === 0; $id++) if (BookmarkRead($id) !== null) $did['bookmark'] = $id;
            continue;
        }
        if ($did['bookmark'] > 0 && !$did['loaded'] && $did['afterMark'] >= 2) { $send(1, ['mode' => 10019, 'buttonInput' => strval($did['bookmark'])]); $did['loaded'] = true; continue; }
        $attack = array_values(array_filter($acts, fn($a) => preg_match('/^my(Ground|Space)Arena-\d+!FSM!$/', strval($a['cardID'] ?? ''))));
        $play = array_values(array_filter($acts, fn($a) => str_starts_with(strval($a['cardID'] ?? ''), 'myHand-')));
        $other = array_values(array_filter($acts, fn($a) => str_contains(strval($a['cardID'] ?? ''), 'DeployLeader')));
        $pass = array_values(array_filter($acts, fn($a) => str_contains(strval($a['cardID'] ?? ''), '!CustomInput!Pass')));
        $round = intval(GetTurnNumber());
        $ability = !$useAbilities ? [] : array_values(array_filter($acts, function ($a) use ($seat, $round, $usedThisRound) {
            $c = strval($a['cardID'] ?? '');
            return (str_contains($c, '!CustomInput!LeaderAbility') || str_contains($c, '!CustomInput!Activate')) && empty($usedThisRound["$round|$seat|$c"]);
        }));
        if ($ability) { $usedThisRound["$round|$seat|" . strval($ability[0]['cardID'])] = true; $send($seat, $ability[0]); continue; }
        $pick = $attack[0] ?? $other[0] ?? $play[0] ?? $pass[0] ?? $acts[0];
        if (isset($attack[0]) && $pick === $attack[0]) $did['attacks']++;
        if (isset($play[0]) && $pick === $play[0]) $did['plays']++;
        if ($did['bookmark'] > 0 && !$did['loaded']) $did['afterMark']++;
    }
    // A move the engine refuses leaves the same choice up: try the next legal one.
    $sig = $seat . '|' . json_encode($acts);
    $repeat = $sig === $last ? $repeat + 1 : 0; $last = $sig;
    if ($repeat > 0) $pick = $acts[$repeat % count($acts)];
    $send($seat, $pick);
}
$load();
echo "steps $steps, winner " . SWUGetGameWinner() . ', ' . json_encode($did) . "\n";

$out = "./SWUSim/Games/$g/Pgn/out";
$dest = "./.superpowers/swupgn-samples/$label";
@mkdir($dest, 0777, true);
require_once './AppCore/SWU/SwuPgn/SwuPgn.php';
foreach (['all', 'p1', 'p2'] as $k) {
    if (!is_file("$out/$k.swupgn")) { echo "MISSING $k.swupgn (the game did not finish?)\n"; continue; }
    $text = file_get_contents("$out/$k.swupgn");
    $file = "$dest/$label" . ($k === 'all' ? '' : "_$k") . '.swupgn';
    file_put_contents($file, $text);
    $doc = SwuPgnParse($text);
    $problems = _SwuPgnFileProblems($text);
    echo sprintf("%-60s %6d events  %s\n", $file, count($doc['events']), $problems ? 'PROBLEMS: ' . implode(' | ', array_slice($problems, 0, 3)) : 'validates, folds to its keyframes');
}
@unlink($jar[1]); @unlink($jar[2]);
