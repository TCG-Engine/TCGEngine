<?php
// LOCAL DEV ONLY — a FINISHED Twin Suns table for the rematch harness (DevTools/ui-harness/swusim-table-rematch-xbrowser.mjs).
//   GET ?            → builds a private 4-seat twinsuns match (seats 1-3 humans, seat 4 a bot; house rule Plan Scry 2),
//                      marks its game over (seat 1 wins) and returns {gameName, matchId, keys:{1,2,3}}.
//   GET ?deleteLobby=<id> → deletes a waiting room the harness's Rematch created (rooms must never be left behind).
// Refused unless the request is local dev (SWUIsLocalDevRequest). Spec: docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md
header('Content-Type: application/json');
chdir(__DIR__ . '/..');
require_once './SWUSim/Mod/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo json_encode(['error' => 'local dev only']); exit; }

require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';

if (isset($_GET['deleteLobby'])) {
    $id = preg_replace('/[^A-Za-z0-9]/', '', strval($_GET['deleteLobby']));
    LobbyMutate($id, fn($l) => 'delete');
    echo json_encode(['deleted' => $id, 'exists' => apcu_exists($id)]);
    exit;
}

include_once './SWUSim/MatchFlow.php';
$jd = json_decode(file_get_contents('./SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'), true);
$lines = ['Leader', '1 ' . $jd['leader']['id'], '1 ' . $jd['secondleader']['id'], 'Base', '1 ' . $jd['base']['id'], 'Deck'];
foreach ($jd['deck'] as $c) $lines[] = $c['count'] . ' ' . $c['id'];
$deck = implode("\n", $lines);

$lobby = new stdClass();
$lobby->isPrivate = true; $lobby->format = 'twinsuns'; $lobby->queueType = 'bo1'; $lobby->hostPlayerID = 1;
$lobby->houseRulesActive = ['planScry' => 2];
$lobby->players = [];
foreach ([1, 2, 3, 4] as $s) {
    $p = new Player($s, $deck, '', null);
    if ($s === 1) $p->setUsername('claudebot1');
    if ($s === 2) $p->setUsername('claudebot2');
    if ($s === 3) $p->setUsername('claudebot3');
    if ($s === 4) { $p->setBotProfile('precon:x'); $p->setUsername('Arenabot Alpha'); }
    $lobby->players[] = $p;
}
$matchId = SWUCreateMatchFromLobby($lobby);
if (!$matchId) { echo json_encode(['error' => 'match not created', 'flash' => GetFlashMessage()]); exit; }
// SWUSetupGame leaves the new game loaded: declare it over so the board opens its end-game menu on load.
global $gameName;
DecisionQueueController::StoreVariable('GAMEOVER_WINNER', '1');
DecisionQueueController::StoreVariable('GAMEOVER_WINNERS', '1');
WriteGamestate('./SWUSim/');
$m = SWUReadMatch($matchId);
$game = strval($m['games'][0]['gameName']);
MatchRecordGameResult('SWUSim', $matchId, $game, 1, 5, [1]);
$keys = [];
foreach (SWUReadMatch($matchId)['players'] as $s => $p) if (intval($s) <= 3) $keys[strval($s)] = $p['authKey'];
echo json_encode(['gameName' => $game, 'matchId' => $matchId, 'keys' => $keys]);
