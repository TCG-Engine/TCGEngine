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
// DRAFT rooms (Plan 3): &mode=draft&players=N (3–8, claudebot1..N unless &accounts=…)&pickSeconds=S (default 300)
//   ?phase=setup      the host and N-1 guests      ?phase=drafting   started: the leader draft is on (3 packs, Single Set)
//   ?phase=building   the draft is over (every seat picked the first card each time), nobody has submitted
//   ?phase=built      every seat built and Readied
//   SWISS (Plan 4), Draft only: ?phase=round1   built → round 1's matches made (seat 1 of each table is its higher seat)
//   ?phase=between    round 1 played (seat 1 of every table won)      ?phase=done   all three rounds played the same way
//   &bestOf=3 for Bo3 tables (each win is recorded 2–0)      &dropSeat=N   that seat is then marked dropped (kicked)
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
$mode = strval($_GET['mode'] ?? 'sealed') === 'draft' ? 'draft' : 'sealed';
$phases = $mode === 'draft' ? ['setup', 'drafting', 'building', 'built', 'round1', 'between', 'done'] : ['setup', 'building', 'playing', 'sideboard', 'done'];
if (!in_array($phase, $phases, true)) { echo json_encode(['error' => 'bad phase']); exit; }
$bestOf = ($phase === 'sideboard' || intval($_GET['bestOf'] ?? 1) === 3) ? 3 : 1;
$players = $mode === 'draft' ? max(3, min(8, intval($_GET['players'] ?? 3))) : 2;
$defaultAccounts = implode(',', array_map(fn($i) => "claudebot$i", range(1, $players)));
$accounts = array_values(array_filter(array_map('trim', explode(',', strval($_GET['accounts'] ?? $defaultAccounts)))));

$lobby = new stdClass();
$lobby->id = 'ltfx' . bin2hex(random_bytes(5));
$lobby->rootName = 'SWUSim'; $lobby->format = 'limited'; $lobby->queueType = 'bo1';
$lobby->isPrivate = true; $lobby->hostPlayerID = 1; $lobby->casterMode = false; $lobby->ready = false; $lobby->hostUserId = null;
$lobby->inviteCode = bin2hex(random_bytes(12));
$lobby->players = [];
foreach (array_slice($accounts, 0, $players) as $i => $u) {
    $row = LoadUserData($u);
    $p = new Player($i + 1, '', '', $row ? intval($row['usersId']) : null);
    if ($row) $p->setUsername(strval($row['usersUid']));
    $p->touch();
    $lobby->players[] = $p;
    if ($i === 0) SWULimitedCreate($lobby, $p, $mode); else SWULimitedOnJoin($lobby, $p);
}
$lobby->numPlayers = count($lobby->players);

if ($mode === 'draft' && $phase !== 'setup') {
    $s = SWULimitedSettingsFromRequest(['packMode' => 'single', 'singleSet' => 'SOR', 'packCount' => 3, 'bestOf' => $bestOf], 'draft');
    $lt = LTSetSettings($lobby->limited, $s, SWULimitedValidateSettings($s));
    $seed = random_int(1, PHP_INT_MAX);
    $o = SWULimitedOpenDraft($s, LTLiveSeats($lt), SWULimitedRng($seed));
    $lt = LTDraftBegin($lt, $o['order'], $o['leaderPacks'], $o['cardPacks'], $o['aspects'], $seed, max(5, intval($_GET['pickSeconds'] ?? LT_PICK_SECONDS)), time());
    $lt['lineups'] = $o['lineups']; $lt['setsInPlay'] = SWULimitedSetsInPlay($s);
    if ($phase !== 'drafting') {   // everyone picks the first card each time until the draft is over
        for ($guard = 0; $guard < 2000 && $lt['phase'] === 'drafting'; $guard++) {
            foreach ($lt['draft']['order'] as $pid) { $f = LTDraftFront($lt['draft'], $pid); if ($f) $lt = LTDraftPick($lt, $pid, $f['id'], $f['cards'][0], time()); }
        }
    }
    if (in_array($phase, ['built', 'round1', 'between', 'done'], true)) {
        $bases = SWULimitedCommonBasesInPlay($lt['setsInPlay']);
        foreach (LTLiveSeats($lt) as $pid) {
            $leader = ''; $main = [];
            foreach ($lt['pools'][$pid] as $c) { $t = CardType($c); if ($t === 'Leader') { if ($leader === '') $leader = $c; continue; } if ($t !== 'Base' && count($main) < 30) $main[] = $c; }
            $lt = LTReady(LTSubmitDeck($lt, $pid, ['leader' => $leader, 'base' => $bases[0], 'main' => $main]), $pid);
            foreach ($lobby->players as $p) if (intval($p->getPlayerID()) === $pid) { $p->setDeckLink(SWULimitedDeckJson($lt['seats'][$pid]['deck'], $lt['pools'][$pid])); $p->setDeckOk(true); $p->setReady(true); }
        }
        $lt = LTMarkBuilt($lt) ?? $lt;
    }
    $lobby->limited = $lt;
    $lobby->state = 'started';
} elseif ($phase !== 'setup') {
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
if ($mode === 'sealed' && in_array($phase, ['playing', 'sideboard', 'done'], true)) {
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

// Swiss: round 1 is made by the room's own tick; every table is then won by its match seat 1, through the real result path
// (MatchRecordGameResult + the gameResultRecorded hook), and later rounds are started as the host would.
function LimitedFixturePlayRound(string $lobbyID, int $bestOf): void {
    $lt = apcu_fetch($lobbyID)->limited;
    foreach ($lt['swiss']['pairings'][$lt['swiss']['round']] as $x) {
        if (!empty($x['bye'])) continue;
        $games = $bestOf === 3 ? [$x['gameName'], $x['gameName'] . 'b'] : [$x['gameName']];
        foreach ($games as $g) MatchRecordGameResult('SWUSim', $x['matchId'], $g, 1, 1);   // round 1 of the game: no deck stats
        MatchHook('SWUSim', 'gameResultRecorded', SWUReadMatch($x['matchId']), end($games));
    }
}
if ($mode === 'draft' && in_array($phase, ['round1', 'between', 'done'], true)) {
    SWULimitedTick($lobby->id, time());
    if ($phase !== 'round1') LimitedFixturePlayRound($lobby->id, $bestOf);
    if ($phase === 'done') {
        for ($r = 2; $r <= LT_SWISS_ROUNDS && apcu_fetch($lobby->id)->limited['phase'] === 'between'; $r++) {
            LobbyMutate($lobby->id, function ($l) { foreach (LTLiveSeats($l->limited) as $pid) $l->limited = LTReady($l->limited, $pid) ?? $l->limited; return true; });
            SWULimitedTryStartRound($lobby->id, time(), true);
            LimitedFixturePlayRound($lobby->id, $bestOf);
        }
    }
}

// &dropSeat=N (Draft Swiss phases): that seat is marked dropped afterwards (as a kick would), keeping its Player.
if ($mode === 'draft' && intval($_GET['dropSeat'] ?? 0) > 0) {
    $drop = intval($_GET['dropSeat']);
    LobbyMutate($lobby->id, function ($l) use ($drop) { $l->limited = LTDropSeat($l->limited, $drop, 'kicked'); return true; });
}

$matchId = ''; $game = ''; $matchKeys = [];
if ($mode === 'sealed' && in_array($phase, ['playing', 'sideboard', 'done'], true)) {
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
                  // Swiss: the current round's tables, by lobby playerID (= the seat number here)
                  'tables' => array_map(fn($x) => ['a' => $x['a'], 'b' => $x['b'], 'bye' => !empty($x['bye']), 'gameName' => $x['gameName']],
                                        $l->limited['swiss']['pairings'][intval($l->limited['swiss']['round'] ?? 0)] ?? []),
                  'urls' => ['room' => 'SharedUI/WaitingRoom.php?lobby=' . rawurlencode($l->id), 'builder' => $builder,
                             'draft' => 'SWUSim/LimitedDraft.php?lobbyID=' . rawurlencode($l->id)]]);
