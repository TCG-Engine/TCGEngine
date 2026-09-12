<?php
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyAdapter.php';
require_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
// LobbyAdapterFor includes this file inside a function. Generated dictionaries use
// globals, so publish their data arrays when this is their first include.
foreach (get_defined_vars() as $dictionaryName => $dictionaryValue) {
    if (str_ends_with($dictionaryName, 'Data') && is_array($dictionaryValue)) $GLOBALS[$dictionaryName] = $dictionaryValue;
}
require_once __DIR__ . '/Custom/DeckImport.php';
require_once __DIR__ . '/../FaBDeck/DeckService.php';

class FaBLobbyAdapter implements LobbyAdapter, LobbyBotAdapter {
    public function botProfiles(object $lobby): array {
        return $this->wantsWaitingRoom($lobby) ? [
            'goldfish' => ['name' => 'Goldfish bot', 'description' => '20 health; passes priority and skips its turns.'],
            'fai' => ['name' => 'Fai · Heuristic bot', 'description' => 'Draconic Ninja: attacks, blocks, pitches, and builds combat chains.'],
            'professor' => ['name' => 'Professor Teklovossen · Heuristic bot', 'description' => 'Round the Table: upgrades Evos, boosts attacks, and fires Teklo Blaster.'],
        ] : [];
    }

    public function configureBot(object $lobby, Player $player, string $profile): void {
        if (!isset($this->botProfiles($lobby)[$profile])) throw new InvalidArgumentException('Unknown bot profile.');
        $player->setBotProfile($profile);
        $player->setDeckOk(true);
        $player->setReady(true);
    }
    public function wantsWaitingRoom(object $lobby): bool {
        return !empty($lobby->isPrivate) && ($lobby->format ?? '') === 'upf';
    }

    public function seatModel(object $lobby): array {
        return ['maxPlayers' => 4, 'teams' => null, 'queueType' => 'bo1', 'fixedSeats' => true];
    }

    public function validateDeck(object $lobby, string $deckInput): array {
        $resolved = FaBResolveDeckInput($deckInput, $_SESSION['userid'] ?? null);
        $errors = empty($resolved['success'])
            ? [$resolved['message'] ?? 'Unable to read deck.'] : FaBUPFDeckErrors($resolved);
        if ($errors) return ['ok' => false, 'message' => implode(' ', $errors), 'identity' => ['cards' => []]];
        $hero = $resolved['hero'];
        return ['ok' => true, 'message' => '', 'identity' => ['cards' => [[
            'id' => $hero, 'name' => CardName($hero), 'kind' => 'hero',
            'url' => '/TCGEngine/FaBSim/WebpImages/' . rawurlencode($hero) . '.webp',
        ]]]];
    }

    public function startBlockers(object $lobby): array {
        $players = array_values(array_filter($lobby->players ?? [], fn($p) => $p instanceof Player));
        $errors = count($players) === 4 ? [] : ['UPF requires four players to start.'];
        foreach ($players as $player) {
            if ($player->getBotProfile() !== '' && !isset($this->botProfiles($lobby)[$player->getBotProfile()])) {
                $errors[] = 'A bot in this room is no longer available.';
            }
            if (!$player->getDeckOk()) $errors[] = 'Player ' . $player->getPlayerID() . ' needs a legal UPF deck.';
            if (!$player->getReady()) $errors[] = 'Player ' . $player->getPlayerID() . ' is not ready.';
        }
        return $errors;
    }
}
