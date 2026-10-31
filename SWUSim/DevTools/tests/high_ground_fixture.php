<?php
// REGRESSION-SKIP: a fixture endpoint for the browser harness, not a test (it builds a room and prints its ids).
// High Ground room for DevTools/ui-harness/swusim-high-ground-xbrowser.mjs (spec docs/superpowers/specs/2026-10-30-swusim-
// high-ground-design.md). Built the way the real paths build one (SWUHighGroundCreate / OnJoin / StartMatch):
//   ?accounts=claudebot1,claudebot2,claudebot3   members logged in as these accounts, in join order (the first is host)
//   &guests=2                                     then this many guests ("Guest 1", "Guest 2")
//   &tonight=1                                    a Tonight table: Guest 1 went 1-2-1 against the host, who played two lists
//                                                 on the SAME leader + base (so both host rows show a deck name)
//   &live=1                                       a hill match is on: the first two in line
// Moderators only. Prints {lobbyID, inviteCode, keys:{playerID:authKey}, names:{playerID:name}, gameName, watchKey}.
header('Content-Type: application/json');
chdir(__DIR__ . '/../../..');
include_once './AccountFiles/AccountSessionAPI.php';
$authError = CheckLoggedInUserMod();
if ($authError !== null && $authError !== '' && $authError !== false) { echo json_encode(['error' => 'moderators only']); exit; }
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/HighGround.php';
require_once './AccountFiles/AccountDatabaseAPI.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './AppCore/SWU/CardImagePath.php';   // card names for the identity strip and Tonight

$deck = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));
$accounts = array_values(array_filter(array_map('trim', explode(',', strval($_GET['accounts'] ?? 'claudebot1')))));
$guests = max(0, min(10, intval($_GET['guests'] ?? 0)));

$lobby = new stdClass();
$lobby->id = 'hgfx' . bin2hex(random_bytes(5));
$lobby->rootName = 'SWUSim'; $lobby->format = 'premier'; $lobby->queueType = 'bo1';
$lobby->isPrivate = true; $lobby->hostPlayerID = 1; $lobby->casterMode = false; $lobby->ready = false;
$lobby->inviteCode = bin2hex(random_bytes(12));
$lobby->players = [];
$pid = 0;
$add = function (?array $user) use ($lobby, $deck, &$pid) {
    $p = new Player(++$pid, $deck, '', $user ? intval($user['usersId']) : null);
    if ($user) $p->setUsername(strval($user['usersUid']));
    $p->setDeckOk(true);
    $p->setDeckIdentity(['SOR_005'], 'SOR_023', [
        ['id' => 'SOR_005', 'name' => CardTitle('SOR_005') . ', ' . CardSubtitle('SOR_005'), 'url' => SWUCardImagePath('SOR_005', 'card'), 'kind' => 'leader', 'colors' => []],
        ['id' => 'SOR_023', 'name' => CardTitle('SOR_023'), 'url' => SWUCardImagePath('SOR_023', 'card'), 'kind' => 'base', 'colors' => []]]);
    $p->touch();
    $lobby->players[] = $p;
    if ($pid === 1) SWUHighGroundCreate($lobby, $p); else SWUHighGroundOnJoin($lobby, $p);
    return $p;
};
foreach ($accounts as $u) { $row = LoadUserData($u); if ($row) $add($row); }
for ($i = 0; $i < $guests; $i++) $add(null);
$lobby->numPlayers = count($lobby->players);

if (!empty($_GET['tonight'])) {
    $host = $lobby->players[0]; $g1 = null;
    foreach ($lobby->players as $p) if (intval($p->getUserId() ?? 0) <= 0) { $g1 = $p; break; }
    $seat = fn($p, $link, $name) => ['key' => $p->getAuthKey(), 'userId' => intval($p->getUserId() ?? 0), 'deckLink' => $link,
                                     'leader' => CardTitle('SOR_005'), 'base' => CardTitle('SOR_023'), 'deckName' => $name];
    if ($g1 !== null) {
        $s = [1 => $seat($host, 'fx-host-a', 'Aggro list'), 2 => $seat($g1, 'fx-guest', 'Guest list')];
        $hg = $lobby->highGround;
        $hg = HGRecordGame($hg, 'fx1', $s, 1); $hg = HGRecordGame($hg, 'fx2', $s, 2); $hg = HGRecordGame($hg, 'fx3', $s, 0);
        $hg = HGRecordGame($hg, 'fx4', [1 => $seat($host, 'fx-host-b', 'Control list'), 2 => $s[2]], 1);
        $lobby->highGround = $hg;
    }
}
apcu_store($lobby->id, $lobby, LOBBY_TTL_SECONDS);
apcu_store('invite:' . $lobby->inviteCode, $lobby->id, LOBBY_TTL_SECONDS);
SWURoomSave($lobby);

$game = '';
if (!empty($_GET['live'])) {
    LobbyMutate($lobby->id, function ($l) { foreach ($l->players as $p) $p->setReady(true); return true; });
    $start = SWUHighGroundStartMatch($lobby->id, $lobby->players[0]->getAuthKey());
    $game = strval($start['gameName'] ?? '');
}
$l = apcu_fetch($lobby->id);
$keys = []; $names = [];
foreach ($l->players as $p) { $keys[$p->getPlayerID()] = $p->getAuthKey(); $names[$p->getPlayerID()] = $l->highGround['names'][$p->getAuthKey()]; }
echo json_encode(['lobbyID' => $l->id, 'inviteCode' => $l->inviteCode, 'keys' => $keys, 'names' => $names, 'gameName' => $game,
                  'watchKey' => strval($l->highGround['watchKey'])]);
