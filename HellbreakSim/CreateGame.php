<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/GameAuth.php';

// This endpoint is intentionally only match setup. Card actions and phase transitions will
// arrive with the Hellbreak rules implementation.
$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();

$playerNumber = 1;
foreach ($lobby->players as $player) {
    $player->setGamePlayerID($playerNumber);
    HellbreakScaffoldLoadPlayer($playerNumber);
    ++$playerNumber;
}

$gTurnNumber = 1;
$gFirstPlayer = 1;
$gTurnPlayer = 1;
$gCurrentPhase = 'SCAFFOLD';
$gPhaseParameters = '-';
WriteGamestate(__DIR__ . '/');

$lobby->gameName = $gameName;
if (!SimGameWriteAuthKeysFromLobby('HellbreakSim', $gameName, $lobby)) {
    throw new RuntimeException('Unable to store Hellbreak game authentication metadata.');
}

function HellbreakScaffoldLoadPlayer(int $player): void {
    $monster = '';
    $location = '';
    $main = [];
    foreach (GetAllCardIds() as $cardID) {
        $type = strtolower(trim((string)CardType($cardID)));
        if ($type === 'monster' && $monster === '') $monster = $cardID;
        elseif ($type === 'location' && $location === '') $location = $cardID;
        elseif ($type !== 'monster' && $type !== 'location') $main[] = $cardID;
    }
    if ($monster !== '') AddMonster($player, $monster);
    if ($location !== '') AddLocation($player, $location);
    foreach ($main as $cardID) AddDeck($player, $cardID);

    $deck = &GetDeck($player);
    if (count($deck) > 1) shuffle($deck);
    for ($i = 0; $i < 4 && $deck; ++$i) {
        $card = array_shift($deck);
        AddHand($player, $card->CardID);
    }
    for ($i = 0; $i < 8 && $deck; ++$i) {
        $card = array_shift($deck);
        AddHealthStack($player, $card->CardID);
    }
    $health = &HealthValue($player);
    $health = count(GetHealthStack($player)) * 2;
    $blood = &BloodValue($player);
    $blood = 0;
    $malice = &MaliceValue($player);
    $malice = 0;
}

?>
