<?php
require_once '../../Core/NetworkingLibraries.php';
require_once '../../Core/HTTPLibraries.php';
require_once './Classes/LobbyBots.php';
require_once './Classes/LobbyStore.php';

$error = null;
$profile = strval($_POST['botProfile'] ?? '');
$snapshot = apcu_fetch(strval($_POST['lobbyID'] ?? ''));
$adapter = is_object($snapshot) ? LobbyAdapterFor($snapshot->rootName ?? '') : null; // Load dictionaries before acquiring the room lock.

// A bot that plays a deck (SWUSim: a pre-con, or the host's pasted list) has it resolved and format-checked HERE,
// outside the room lock, exactly like a human's deck: resolution may hit the network (UpdateLobbyDeck.php explains
// why that must never sit inside the lock). LobbyAddBot refuses a deck that failed.
$deck = null;
if ($adapter instanceof LobbyBotPolicyAdapter && is_object($snapshot)) {
    $input = $adapter->botDeckInput($snapshot, $profile, strval($_POST['botDeck'] ?? ''));
    $deck = ['input' => $input, 'validation' => trim($input) === ''
        ? ['ok' => false, 'message' => 'Paste a decklist for this bot.', 'identity' => ['cards' => []]]
        : $adapter->validateDeck($snapshot, $input)];
}

$lobby = LobbyMutate(strval($_POST['lobbyID'] ?? ''), function ($lobby) use (&$error, $profile, $deck) {
    try {
        LobbyAddBot($lobby, strval($_POST['authKey'] ?? ''), $profile,
            isset($_POST['seat']) ? intval($_POST['seat']) : null, $deck);
        return true;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
        return false;
    }
});
header('Content-Type: application/json');
echo json_encode(['success' => $error === null && $lobby !== null,
    'message' => $error ?? ($lobby === null ? 'Room unavailable or busy. Try again.' : 'Bot added.')]);
