<?php
require_once __DIR__ . '/TeamRooms.php';
require_once __DIR__ . '/LobbyAdapter.php';

// Called under LobbyMutate's lock so joining, adding, and starting cannot overfill a room.
function LobbyAddBot(object $lobby, string $authKey, string $profile, ?int $requestedSeat = null): void {
    $host = SWURoomFindPlayerByAuthKey($lobby, $authKey);
    if (!$host || $host->getBotProfile() !== '' || intval($host->getPlayerID()) !== intval($lobby->hostPlayerID ?? 0)) {
        throw new InvalidArgumentException('Only the host can add a bot.');
    }
    if (!empty($lobby->gameName) || !empty($lobby->ready) || ($lobby->state ?? 'open') !== 'open') {
        throw new InvalidArgumentException('This room is no longer open.');
    }
    $adapter = LobbyAdapterFor($lobby->rootName ?? '');
    if (!($adapter instanceof LobbyBotAdapter) || !$adapter->wantsWaitingRoom($lobby)
        || !isset($adapter->botProfiles($lobby)[$profile])) {
        throw new InvalidArgumentException('That bot is not available in this room.');
    }
    $players = array_values(array_filter($lobby->players ?? [], fn($p) => $p instanceof Player));
    if (count($players) >= $adapter->seatModel($lobby)['maxPlayers']) throw new InvalidArgumentException('The room is full.');
    $nextID = 1;
    foreach ($players as $player) $nextID = max($nextID, intval($player->getPlayerID()) + 1);
    $bot = new Player($nextID, '');
    LobbyEnsureFixedSeats($lobby);
    if (LobbyUsesFixedSeats($lobby)) {
        $taken = array_map(fn($p) => $p->getSeat(), $players);
        $free = array_values(array_diff(range(1, 4), $taken));
        $seat = $requestedSeat ?? ($free[0] ?? 0);
        if (!in_array($seat, $free, true)) throw new InvalidArgumentException('That slot is no longer available. Choose an empty slot.');
        $bot->setSeat($seat);
    }
    $adapter->configureBot($lobby, $bot, $profile);
    $lobby->players[] = $bot;
    $lobby->numPlayers = count($lobby->players);
}
