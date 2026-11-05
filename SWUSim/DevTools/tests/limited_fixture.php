<?php
// REGRESSION-SKIP: a fixture endpoint for the browser harness, not a test (it builds a room and prints its ids).
// A two-seat Limited (Sealed) room for DevTools/ui-harness/swusim-limited-{room,builder}-xbrowser.mjs (spec
// docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md). Built the way the real paths build one
// (SWULimitedCreate / OnJoin, LimitedAction's start, the builder's Ready, SWULimitedStartMatch):
//   ?phase=setup      the host and a guest, settings untouched
//   ?phase=building   started: both pools opened (Single Set SOR, 6 packs), nobody has submitted
//   ?phase=playing    both Ready → the match is on (bestOf=3 for a Bo3)
//   ?phase=sideboard  a Bo3 whose game 1 seat 2 lost: the between-games builder is open
//   ?phase=done       a Bo1 seat 1 won
//   &accounts=claudebot1,claudebot2   the two seats' accounts (default)
// Moderators only. Prints {lobbyID, inviteCode, keys:{1,2}, ids:{1,2}, matchId, gameName, matchKeys:{1,2}, urls:{room, builder}}.
header('Content-Type: application/json');
chdir(__DIR__ . '/../../..');
include_once './AccountFiles/AccountSessionAPI.php';
$authError = CheckLoggedInUserMod();
if ($authError !== null && $authError !== '' && $authError !== false) { echo json_encode(['error' => 'moderators only']); exit; }
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/MatchFlow.php';
require_once './AccountFiles/AccountDatabaseAPI.php';
require_once './SWUSim/Limited/LimitedRoom.php';
require_once './SWUSim/Limited/LimitedSettings.php';
require_once './SWUSim/Limited/LimitedDeck.php';

$phase = strval($_GET['phase'] ?? 'setup');
if (!in_array($phase, ['setup', 'building', 'playing', 'sideboard', 'done'], true)) { echo json_encode(['error' => 'bad phase']); exit; }
$bestOf = ($phase === 'sideboard' || intval($_GET['bestOf'] ?? 1) === 3) ? 3 : 1;
$accounts = array_values(array_filter(array_map('trim', explode(',', strval($_GET['accounts'] ?? 'claudebot1,claudebot2')))));

$lobby = new stdClass();
$lobby->id = 'ltfx' . bin2hex(random_bytes(5));
$lobby->rootName = 'SWUSim'; $lobby->format = 'limited'; $lobby->queueType = 'bo1';
$lobby->isPrivate = true; $lobby->hostPlayerID = 1; $lobby->casterMode = false; $lobby->ready = false; $lobby->hostUserId = null;
$lobby->inviteCode = bin2hex(random_bytes(12));
$lobby->players = [];
foreach (array_slice($accounts, 0, 2) as $i => $u) {
    $row = LoadUserData($u);
    $p = new Player($i + 1, '', '', $row ? intval($row['usersId']) : null);
    if ($row) $p->setUsername(strval($row['usersUid']));
    $p->touch();
    $lobby->players[] = $p;
    if ($i === 0) SWULimitedCreate($lobby, $p, 'sealed'); else SWULimitedOnJoin($lobby, $p);
}
$lobby->numPlayers = count($lobby->players);

if ($phase !== 'setup') {
    $s = SWULimitedSettingsFromRequest(['packMode' => 'single', 'singleSet' => 'SOR', 'packCount' => 6, 'bestOf' => $bestOf], 'sealed');
    $lt = LTSetSettings($lobby->limited, $s, SWULimitedValidateSettings($s));
    $rng = SWULimitedRng(random_int(1, PHP_INT_MAX));
    $live = LTLiveSeats($lt);
    $rolled = SWULimitedRollLineups($s, count($live), $rng);
    $lineups = []; $pools = [];
    foreach ($live as $i => $pid) { $lineups[$pid] = $rolled[$i + 1]; $pools[$pid] = SWULimitedOpenSealedPool($lineups[$pid], $rng); }
    $lobby->limited = LTStart($lt, $lineups, $pools, SWULimitedSetsInPlay($s), 1, time());
    $lobby->state = 'started';
}
if (in_array($phase, ['playing', 'sideboard', 'done'], true)) {
    // Each seat builds the simplest legal deck: its first leader, the first base in play, its first 30 other cards.
    $bases = SWULimitedCommonBasesInPlay($lobby->limited['setsInPlay']);
    foreach ($lobby->players as $p) {
        $pid = intval($p->getPlayerID());
        $leader = ''; $main = [];
        foreach ($lobby->limited['pools'][$pid] as $c) {
            $t = CardType($c);
            if ($t === 'Leader') { if ($leader === '') $leader = $c; continue; }
            if ($t !== 'Base' && count($main) < 30) $main[] = $c;
        }
        $deck = ['leader' => $leader, 'base' => $bases[0], 'main' => $main];
        $lobby->limited = LTReady(LTSubmitDeck($lobby->limited, $pid, $deck), $pid);
        $p->setDeckLink(SWULimitedDeckJson($deck, $lobby->limited['pools'][$pid]));
        $p->setDeckOk(true); $p->setReady(true);
    }
}
apcu_store($lobby->id, $lobby, LOBBY_TTL_SECONDS);
apcu_store('invite:' . $lobby->inviteCode, $lobby->id, LOBBY_TTL_SECONDS);
SWURoomSave($lobby);

$matchId = ''; $game = ''; $matchKeys = [];
if (in_array($phase, ['playing', 'sideboard', 'done'], true)) {
    SWULimitedTick($lobby->id, time());   // claims and creates the match
    $lt = apcu_fetch($lobby->id)->limited;
    $matchId = strval($lt['match']['matchId'] ?? '');
    $game = strval($lt['match']['gameName'] ?? '');
    $m = SWUReadMatch($matchId);
    foreach ([1, 2] as $s) $matchKeys[$s] = strval($m['players'][strval($s)]['authKey'] ?? '');
    if ($phase === 'sideboard' || $phase === 'done') {
        $GLOBALS['SWUSTATS_HTTP_FAKE'] = fn($method, $url, $body) => ['status' => 200, 'body' => ['success' => true], 'raw' => '{}'];
        $GLOBALS['gDecisionQueueVariables'] = json_encode(['GAMEOVER_WINNER' => '1', 'GAMEOVER_WINNERS' => '1']);
        MatchAfterActionHook('SWUSim', $game);
        unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
    }
}
$l = apcu_fetch($lobby->id);
$keys = []; $ids = [];
foreach ($l->players as $i => $p) { $keys[$i + 1] = $p->getAuthKey(); $ids[$i + 1] = intval($p->getPlayerID()); }
$builder = $phase === 'sideboard'
    ? 'SWUSim/LimitedBuilder.php?' . http_build_query(['matchId' => $matchId, 'playerID' => 1, 'authKey' => $matchKeys[1]])
    : 'SWUSim/LimitedBuilder.php?' . http_build_query(['lobbyID' => $l->id]);
echo json_encode(['lobbyID' => $l->id, 'inviteCode' => $l->inviteCode, 'keys' => $keys, 'ids' => $ids, 'matchId' => $matchId,
                  'gameName' => $game, 'matchKeys' => $matchKeys, 'phase' => $l->limited['phase'],
                  'urls' => ['room' => 'SharedUI/WaitingRoom.php?lobby=' . rawurlencode($l->id), 'builder' => $builder]]);
