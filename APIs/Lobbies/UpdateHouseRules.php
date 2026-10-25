<?php
// Waiting-room House Rules: cast/withdraw a vote (op=vote, rule, on=0|1) or, host only, set a rule's number
// (op=value, rule, value=N). The rules themselves live in Classes/HouseRules.php; the menu comes from the sim's
// LobbyHouseRulesAdapter, so a sim without one refuses every call. The page reads the result from its next poll.
// docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/LobbyAdapter.php";
require_once "./Classes/LobbyStore.php";
require_once "./Classes/HouseRules.php";

$response = new stdClass();
function _houseRulesFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID = $_POST['lobbyID'] ?? '';
$authKey = $_POST['authKey'] ?? '';
$op      = strval($_POST['op'] ?? '');
$rule    = strval($_POST['rule'] ?? '');
if ($op !== 'vote' && $op !== 'value') _houseRulesFail($response, 'Unknown house rule action.');

$snapshot = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$snapshot) _houseRulesFail($response, 'Room not found.');
// Resolve the menu OUTSIDE the lock — LobbyMutate's callback must not do I/O, and the adapter may load sim files.
$adapter = LobbyAdapterFor(strval($snapshot->rootName ?? ''));
$defs = ($adapter instanceof LobbyHouseRulesAdapter) ? $adapter->houseRuleDefs($snapshot) : [];
if (empty($defs)) _houseRulesFail($response, 'This room has no house rules.');

$err = null;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($authKey, $op, $rule, $defs, &$err) {
  // ('starting' too — StartRoom has already frozen the set; LobbyHouseRulesSetVote/SetValue refuse it as well.)
  if (!empty($lobby->gameName) || in_array(($lobby->state ?? ''), ['starting', 'started'], true)) { $err = 'The game is already starting.'; return false; }
  $me = null;
  foreach (($lobby->players ?? []) as $p) {
    if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), strval($authKey))) { $me = $p; break; }
  }
  if (!$me) { $err = 'Authentication failed.'; return false; }
  $err = ($op === 'vote')
    ? LobbyHouseRulesSetVote($lobby, $defs, intval($me->getPlayerID()), $rule, ($_POST['on'] ?? '') === '1')
    : LobbyHouseRulesSetValue($lobby, $defs, intval($me->getPlayerID()), $rule, intval($_POST['value'] ?? 0));
  return $err === null;
});
if ($lobby === null && $err === null) _houseRulesFail($response, 'Room not found or busy — try again.');
if ($err !== null) _houseRulesFail($response, $err);

$response->success = true;
header('Content-Type: application/json');
echo json_encode($response);
