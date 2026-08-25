<?php

require_once __DIR__ . '/../../Core/NetworkingLibraries.php';
require_once __DIR__ . '/Classes/Player.php';

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

$lobby = apcu_fetch($lobbyID);
if (!is_object($lobby) || strval($lobby->rootName ?? '') !== $rootName) {
  UpdateLobbyCasterModeFail('Private lobby not found.', 404);
}
if (empty($lobby->isPrivate)) UpdateLobbyCasterModeFail('Caster mode is only available for private games.');
if (!empty($lobby->ready) || intval($lobby->numPlayers ?? 0) !== 1 || !empty($lobby->gameName)) {
  UpdateLobbyCasterModeFail('Caster mode cannot be changed after an opponent joins.', 409);
}

$hostAuthenticated = false;
foreach (($lobby->players ?? []) as $player) {
  if (!($player instanceof Player)) continue;
  if (intval($player->getPlayerID()) !== intval($lobby->hostPlayerID ?? 1)) continue;
  if (!hash_equals(strval($player->getAuthKey()), $authKey)) continue;
  $hostAuthenticated = true;
  break;
}
if (!$hostAuthenticated) UpdateLobbyCasterModeFail('Authentication failed.', 403);

$lobby->casterMode = $casterMode;
apcu_store($lobbyID, $lobby, 600);

echo json_encode([
  'success' => true,
  'casterMode' => $casterMode,
]);

?>
