<?php

require_once __DIR__ . '/../../Core/NetworkingLibraries.php';
require_once __DIR__ . '/Classes/Player.php';
require_once __DIR__ . '/Classes/LobbyStore.php';

header('Content-Type: application/json');

function UpdateLobbyCasterModeFail($message, $status = 400)
{
  http_response_code($status);
  echo json_encode(['success' => false, 'message' => $message]);
  exit;
}

$rootName = strval($_POST['rootName'] ?? '');
$lobbyID = strval($_POST['lobbyID'] ?? '');
$playerID = intval($_POST['playerID'] ?? 0);
$authKey = strval($_POST['authKey'] ?? '');
$casterModeRaw = strtolower(trim(strval($_POST['casterMode'] ?? '0')));
$casterMode = $casterModeRaw === '1' || $casterModeRaw === 'true';

if ($rootName !== 'AzukiSim' || $lobbyID === '' || $playerID !== 1 || $authKey === '') {
  UpdateLobbyCasterModeFail('Invalid caster-mode request.');
}

// This one cannot actually race — it refuses unless numPlayers === 1 — so routing it through
// LobbyMutate is for consistency: no lobby write lives outside LobbyStore.php any more.
$err = null; $code = 400;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($rootName, $authKey, $casterMode, &$err, &$code) {
  if (strval($lobby->rootName ?? '') !== $rootName) { $err = 'Private lobby not found.'; $code = 404; return false; }
  if (empty($lobby->isPrivate)) { $err = 'Caster mode is only available for private games.'; return false; }
  if (!empty($lobby->ready) || intval($lobby->numPlayers ?? 0) !== 1 || !empty($lobby->gameName)) {
    $err = 'Caster mode cannot be changed after an opponent joins.'; $code = 409; return false;
  }
  $hostAuthenticated = false;
  foreach (($lobby->players ?? []) as $player) {
    if (!($player instanceof Player)) continue;
    if (intval($player->getPlayerID()) !== intval($lobby->hostPlayerID ?? 1)) continue;
    if (!hash_equals(strval($player->getAuthKey()), $authKey)) continue;
    $hostAuthenticated = true;
    break;
  }
  if (!$hostAuthenticated) { $err = 'Authentication failed.'; $code = 403; return false; }
  $lobby->casterMode = $casterMode;
  return true;
});
if ($err !== null)   UpdateLobbyCasterModeFail($err, $code);
if ($lobby === null) UpdateLobbyCasterModeFail('Private lobby not found.', 404);

echo json_encode([
  'success' => true,
  'casterMode' => $casterMode,
]);

?>
