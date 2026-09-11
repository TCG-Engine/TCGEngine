<?php
require_once '../../Core/NetworkingLibraries.php';
require_once '../../Core/HTTPLibraries.php';
require_once './Classes/LobbyBots.php';
require_once './Classes/LobbyStore.php';

$error = null;
$snapshot = apcu_fetch(strval($_POST['lobbyID'] ?? ''));
if (is_object($snapshot)) LobbyAdapterFor($snapshot->rootName ?? ''); // Load dictionaries before acquiring the room lock.
$lobby = LobbyMutate(strval($_POST['lobbyID'] ?? ''), function ($lobby) use (&$error) {
    try {
        LobbyAddBot($lobby, strval($_POST['authKey'] ?? ''), strval($_POST['botProfile'] ?? ''),
            isset($_POST['seat']) ? intval($_POST['seat']) : null);
        return true;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
        return false;
    }
});
header('Content-Type: application/json');
echo json_encode(['success' => $error === null && $lobby !== null,
    'message' => $error ?? ($lobby === null ? 'Room unavailable or busy. Try again.' : 'Bot added.')]);
