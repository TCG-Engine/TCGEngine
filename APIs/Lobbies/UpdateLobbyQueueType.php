<?php
// The host of a SWUSim 1v1 waiting room picks the next match's length: Best of 1 or Best of 3.
// POST rootName=SWUSim, lobbyID, authKey (the host's seat key), queueType = bo1 | bo3.
// Everyone else in the room is set to not Ready — they agreed to the old length.
// docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md (call 6)
require_once __DIR__ . '/../../Core/NetworkingLibraries.php';
require_once __DIR__ . '/Classes/Player.php';
require_once __DIR__ . '/Classes/LobbyStore.php';
require_once __DIR__ . '/../../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../../SWUSim/Rooms.php';   // SWURoomSave — file scope on purpose

header('Content-Type: application/json');

function UpdateLobbyQueueTypeFail($message, $status = 400)
{
  http_response_code($status);
  echo json_encode(['success' => false, 'message' => $message]);
  exit;
}

$rootName  = strval($_POST['rootName'] ?? '');
$lobbyID   = strval($_POST['lobbyID'] ?? '');
$authKey   = strval($_POST['authKey'] ?? '');
$queueType = strtolower(trim(strval($_POST['queueType'] ?? '')));

if ($rootName !== 'SWUSim' || $lobbyID === '' || $authKey === '') UpdateLobbyQueueTypeFail('Invalid request.');
if (!in_array($queueType, ['bo1', 'bo3'], true) || SWUGetQueueType($queueType) === null) UpdateLobbyQueueTypeFail('Unknown match length.');

SWURoomRehydrate($lobbyID);
$err = null; $code = 400;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($rootName, $authKey, $queueType, &$err, &$code) {
  if (strval($lobby->rootName ?? '') !== $rootName) { $err = 'Room not found.'; $code = 404; return false; }
  if (intval($lobby->maxPlayers ?? 0) !== 2) { $err = 'Match length is chosen for 1v1 rooms only.'; return false; }
  // A public lobby's length is the queue it waits in — changing it would move it to another queue.
  if (empty($lobby->isPrivate)) { $err = 'Match length is chosen in private rooms only.'; return false; }
  if (!empty($lobby->gameName)) { $err = 'The match has already started.'; $code = 409; return false; }
  $host = null;
  foreach (($lobby->players ?? []) as $p) {
    if (($p instanceof Player) && intval($p->getPlayerID()) === intval($lobby->hostPlayerID ?? 1)) { $host = $p; break; }
  }
  if ($host === null || !hash_equals(strval($host->getAuthKey()), $authKey)) { $err = 'Only the host can change the match length.'; $code = 403; return false; }
  if (strval($lobby->queueType ?? 'bo1') === $queueType) return false;   // no change, nobody un-readied
  $lobby->queueType = $queueType;
  foreach ($lobby->players as $p) if (($p instanceof Player) && $p !== $host && $p->getBotProfile() === '') $p->setReady(false);
  return true;
});
if ($err !== null)   UpdateLobbyQueueTypeFail($err, $code);
if ($lobby === null) UpdateLobbyQueueTypeFail('Room not found.', 404);
if (!empty($lobby->roomMatchId)) SWURoomSave($lobby);

echo json_encode(['success' => true, 'queueType' => strval($lobby->queueType ?? $queueType)]);
