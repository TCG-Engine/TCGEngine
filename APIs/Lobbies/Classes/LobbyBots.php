<?php
require_once __DIR__ . '/TeamRooms.php';
require_once __DIR__ . '/LobbyAdapter.php';

// Called under LobbyMutate's lock so joining, adding, and starting cannot overfill a room.
// $deck: ['input' => string, 'validation' => validateDeck() result], resolved BEFORE the lock by the caller,
// for an adapter whose bots play a deck (LobbyBotPolicyAdapter::botDeckInput). Null = no deck (FaB).
function LobbyAddBot(object $lobby, string $authKey, string $profile, ?int $requestedSeat = null, ?array $deck = null): void {
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
    if ($adapter instanceof LobbyBotPolicyAdapter) {
        $wait = $adapter->botAddWaitSeconds($lobby, time());
        if ($wait > 0) throw new InvalidArgumentException("Bots can be added in {$wait}s, once nobody new has joined the room for a while.");
        if ($deck !== null && empty($deck['validation']['ok'])) {
            $why = strval($deck['validation']['message'] ?? '');
            throw new InvalidArgumentException('That deck cannot be used' . ($why !== '' ? ": $why" : '.'));
        }
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
    if ($deck !== null) LobbyApplyBotDeck($bot, strval($deck['input'] ?? ''), (array)$deck['validation']);
    $lobby->players[] = $bot;
    $lobby->numPlayers = count($lobby->players);
}

// The bot's deck, applied the way UpdateLobbyDeck.php applies a human's: leaders and base are derived FROM the
// identity cards, so the roster strip and the leader cache the start checks read can never disagree.
function LobbyApplyBotDeck(Player $bot, string $input, array $validation): void {
    $cards = !empty($validation['ok']) ? (array)($validation['identity']['cards'] ?? []) : [];
    $leaders = []; $base = '';
    foreach ($cards as $c) {
        if (($c['kind'] ?? '') === 'leader')                   $leaders[] = $c['id'];
        elseif (($c['kind'] ?? '') === 'base' && $base === '') $base = $c['id'];
    }
    $bot->setDeckIdentity($leaders, $base, $cards);
    $bot->setDeckOk(!empty($validation['ok']));
    if (!empty($validation['ok'])) $bot->setDeckLink($input);
}

// ── Bots as placeholders (Decision 6, SWUSim/docs/todo-twinsuns-fill-bot.md) ──────────────────────────────────
// In a room whose adapter opts in, a bot only holds a seat until a human wants it: a human joining a full room takes
// the seat of the OLDEST bot (FIFO, by joinedAt; ties by the lower playerID, which is also the earlier add).

function _LobbyBotsYield(object $lobby): bool {
    $a = LobbyAdapterFor(strval($lobby->rootName ?? ''));
    return $a instanceof LobbyBotPolicyAdapter && $a->botsYieldToHumans($lobby);
}

function _LobbyOldestBot(object $lobby): ?Player {
    $oldest = null;
    foreach (($lobby->players ?? []) as $p) {
        if (!($p instanceof Player) || $p->getBotProfile() === '') continue;
        if ($oldest === null
            || $p->getJoinedAt() < $oldest->getJoinedAt()
            || ($p->getJoinedAt() === $oldest->getJoinedAt() && intval($p->getPlayerID()) < intval($oldest->getPlayerID()))) {
            $oldest = $p;
        }
    }
    return $oldest;
}

// Can a HUMAN join this room? It has an empty seat, or a bot that will give its seat up. For the scans that run
// before the room lock; LobbyYieldBotSeatIfFull() makes it true under the lock.
function LobbyHasRoomForHuman(object $lobby): bool {
    if (intval($lobby->numPlayers ?? 0) < intval($lobby->maxPlayers ?? 0)) return true;
    return _LobbyBotsYield($lobby) && _LobbyOldestBot($lobby) !== null;
}

// Under the room lock, just before a human is seated: if the room is full and its bots yield, the oldest bot leaves.
// Returns the bot that left, or null.
function LobbyYieldBotSeatIfFull(object $lobby): ?Player {
    if (intval($lobby->numPlayers ?? 0) < intval($lobby->maxPlayers ?? 0) || !_LobbyBotsYield($lobby)) return null;
    $bot = _LobbyOldestBot($lobby);
    if ($bot === null) return null;
    $lobby->players = array_values(array_filter($lobby->players, fn($p) => $p !== $bot));
    $lobby->numPlayers = count(array_filter($lobby->players, fn($p) => $p instanceof Player));
    return $bot;
}
