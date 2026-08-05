<?php

function HellbreakDeckCardUsable(string $cardID): bool {
    if(!preg_match('/^[A-Za-z0-9_-]+$/', $cardID)) return false;
    if(!function_exists('CardType') || trim((string)CardType($cardID)) === '') return false;
    if(function_exists('CardReviewStatus') && CardReviewStatus($cardID) === 'rejected') return false;
    $image = __DIR__ . '/../concat/' . $cardID . '.webp';
    return is_file($image) && filesize($image) >= 8000;
}

function HellbreakParseDeckGamestateFile(string $path): array {
    if(!is_file($path) || !is_readable($path)) {
        return ['success' => false, 'message' => 'Saved Hellbreak deck was not found.'];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if(!is_array($lines) || count($lines) < 8) {
        return ['success' => false, 'message' => 'Saved Hellbreak deck is incomplete.'];
    }
    $cursor = 2;
    $readZone = function() use (&$lines, &$cursor): ?array {
        if(!isset($lines[$cursor])) return null;
        $countLine = trim((string)$lines[$cursor++]);
        if(!preg_match('/^\d+$/', $countLine)) return null;
        $count = intval($countLine);
        if($count < 0 || $cursor + $count > count($lines)) return null;
        $zone = [];
        for($i = 0; $i < $count; ++$i) {
            $parts = preg_split('/\s+/', trim((string)$lines[$cursor++]));
            $cardID = trim((string)($parts[0] ?? ''));
            if($cardID !== '') $zone[] = $cardID;
        }
        return $zone;
    };

    $p1Monster = $readZone();
    $readZone();
    $p1Locations = $readZone();
    $readZone();
    $p1MainDeck = $readZone();
    $readZone();
    if($p1Monster === null || $p1Locations === null || $p1MainDeck === null) {
        return ['success' => false, 'message' => 'Saved Hellbreak deck could not be parsed.'];
    }

    return [
        'success' => true,
        'monster' => $p1Monster,
        'locations' => $p1Locations,
        'mainDeck' => $p1MainDeck,
    ];
}

function HellbreakValidateResolvedDeck(array $deck): array {
    $monsters = array_values($deck['monster'] ?? []);
    $locations = array_values($deck['locations'] ?? []);
    $mainDeck = array_values($deck['mainDeck'] ?? []);

    if(count($monsters) !== 1) return ['success' => false, 'message' => 'A Hellbreak deck needs exactly one monster.'];
    if(count($locations) !== 2) return ['success' => false, 'message' => 'A Hellbreak deck needs exactly two locations.'];
    if(count($mainDeck) < 12) return ['success' => false, 'message' => 'A Hellbreak deck needs at least 12 main-deck cards for setup.'];

    foreach($monsters as $cardID) {
        if(!HellbreakDeckCardUsable((string)$cardID) || strtolower(trim((string)CardType($cardID))) !== 'monster') {
            return ['success' => false, 'message' => 'The selected monster is not playable.'];
        }
    }
    foreach($locations as $cardID) {
        if(!HellbreakDeckCardUsable((string)$cardID) || strtolower(trim((string)CardType($cardID))) !== 'location') {
            return ['success' => false, 'message' => 'Every selected location must be a playable location card.'];
        }
    }
    foreach($mainDeck as $cardID) {
        $type = strtolower(trim((string)CardType($cardID)));
        if(!HellbreakDeckCardUsable((string)$cardID) || $type === 'monster' || $type === 'location') {
            return ['success' => false, 'message' => 'The main deck contains a card that cannot be played.'];
        }
    }

    return [
        'success' => true,
        'message' => '',
        'monster' => (string)$monsters[0],
        'locations' => array_map('strval', $locations),
        'mainDeck' => array_map('strval', $mainDeck),
    ];
}

function HellbreakResolveDeckInput(string $deckLink): array {
    if(!preg_match('/^hellbreakdeck:(\d+)$/i', trim($deckLink), $matches)) {
        return ['success' => false, 'message' => 'Choose a saved Hellbreak deck.'];
    }
    $deckID = $matches[1];
    $parsed = HellbreakParseDeckGamestateFile(__DIR__ . '/../../HellbreakDeck/Games/' . $deckID . '/Gamestate.txt');
    if(empty($parsed['success'])) return $parsed;
    $validated = HellbreakValidateResolvedDeck($parsed);
    if(!empty($validated['success'])) $validated['deckID'] = $deckID;
    return $validated;
}

function HellbreakValidateDeckForQueue($deckLink, $preconstructedDeck = '', $userID = null): array {
    if(trim((string)$deckLink) !== '') return HellbreakResolveDeckInput((string)$deckLink);
    $preset = trim((string)$preconstructedDeck);
    if(strcasecmp($preset, 'HellbreakFixture') === 0 || strcasecmp($preset, 'HellbreakGamaDemo') === 0) {
        return ['success' => true, 'message' => ''];
    }
    return ['success' => false, 'message' => 'Choose a saved Hellbreak deck or a supported starter-deck preset.'];
}

function HellbreakLoadResolvedPlayer(int $player, array $deck): void {
    $monsterID = (string)$deck['monster'];
    $monsterData = function_exists('HellbreakFixtureCard') ? (HellbreakFixtureCard($monsterID) ?? []) : [];
    AddMonster($player, $monsterID, 2, (string)($monsterData['side'] ?? 'LURKING'), $player, $player, [], []);
    foreach($deck['locations'] as $cardID) AddLocationDeck($player, (string)$cardID);
    foreach($deck['mainDeck'] as $cardID) AddDeck($player, (string)$cardID);

    $health = &HealthValue($player); $health = 0;
    $topHealth = &TopHealthRemainingValue($player); $topHealth = 0;
    $blood = &BloodValue($player); $blood = 0;
    $malice = &MaliceValue($player); $malice = 0;
    $locationCommitment = &LocationCommitmentValue($player); $locationCommitment = '-';
    $bidCommitment = &BidCommitmentValue($player); $bidCommitment = '-';
    $mulliganCommitted = &MulliganCommittedValue($player); $mulliganCommitted = false;
}

?>
