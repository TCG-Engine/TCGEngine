<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../FaBDeck/DeckService.php';

$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
$playerNumber = 1;
$passiveSeats = [];
foreach ($lobby->players as $player) {
    if ($playerNumber > 4) throw new RuntimeException('FaBSim supports a maximum of four seats.');
    $player->setGamePlayerID($playerNumber);
    $isPassiveGoldfishSeat = isset($lobby->goldfishPlayers)
        && is_array($lobby->goldfishPlayers)
        && in_array($playerNumber, $lobby->goldfishPlayers, true)
        && trim((string)$player->getDeckLink()) === ''
        && trim((string)$player->getPreconstructedDeck()) === '';
    if ($isPassiveGoldfishSeat) {
        $passiveSeats[] = $playerNumber;
        FaBEnsureGoldfishOpponent($playerNumber);
        ++$playerNumber;
        continue;
    }
    $resolved = FaBResolveDeckInput($player->getDeckLink(), method_exists($player, 'getUserId') ? $player->getUserId() : null);
    if (empty($resolved['success'])) throw new RuntimeException($resolved['message'] ?? 'Unable to load FaB deck.');
    FaBLoadPlayer($playerNumber, $resolved);
    ++$playerNumber;
}
if ($playerNumber <= 2) throw new RuntimeException('FaBSim requires at least two seats.');
$seatCount = min(4, $playerNumber - 1);
$seatList = implode('', range(1, $seatCount));
SetSeatOrder($seatList);
SetLiveSeats($seatList);

SetTurnPlayer(1);
SetTurnNumber(1);
SetCurrentPhase('SOT');
SetPhaseParameters('');
StartOfTurnPhase();
$initialState = FaBGetState();
$initialState['passiveSeats'] = $passiveSeats;
$initialState['gameMode'] = empty($passiveSeats) ? '' : 'GOLDFISH';
FaBSetState($initialState);
SetCurrentPhase('MAIN');
SetWinner(0);
SaveUndoVersion(1, 'Start of game');
WriteGamestate(__DIR__ . '/');

$lobby->gameName = $gameName;
if (!SimGameWriteAuthKeysFromLobby('FaBSim', $gameName, $lobby)) throw new RuntimeException('Unable to store FaBSim authentication metadata.');

function FaBLoadPlayer($playerID, $resolved) {
    $heroObj = AddHero($playerID, CardID:$resolved['hero'], Owner:$playerID, Controller:$playerID, Status:2);
    foreach ($resolved['weapons'] as $cardID) {
        AddWeapons($playerID, CardID:$cardID, Owner:$playerID, Controller:$playerID, Status:2);
    }
    foreach ($resolved['equipment'] as $cardID) {
        AddEquipment($playerID, CardID:$cardID, Owner:$playerID, Controller:$playerID, Status:2);
    }
    foreach ($resolved['mainDeck'] as $cardID) AddDeck($playerID, CardID:$cardID);
    $deck = &GetDeck($playerID);
    EngineShuffle($deck, true);
    $health = &GetHealth($playerID);
    $resources = &GetResources($playerID);
    $actionPoints = &GetActionPoints($playerID);
    $health = max(1, intval(CardHealth($resolved['hero'])) ?: 20);
    $resources = 0;
    $actionPoints = 1;
    DoDrawCard($playerID, max(1, intval(CardIntelligence($resolved['hero'])) ?: 4));
}

?>
