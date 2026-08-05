<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Fixtures/QuickStartFixtures.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/GameAuth.php';

$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
$hellbreakCreateMode = isset($lobby->format) ? strtolower(strval($lobby->format)) : '';
$isTutorial = $hellbreakCreateMode === 'tutorial';

$fixtureSeats = 0;
$playerNumber = 1;
foreach($lobby->players as $player) {
    $player->setGamePlayerID($playerNumber);
    $deckLink = trim((string)$player->getDeckLink());
    if($deckLink !== '') {
        $resolved = HellbreakResolveDeckInput($deckLink);
        if(empty($resolved['success'])) {
            throw new RuntimeException((string)($resolved['message'] ?? 'Unable to load Hellbreak deck.'));
        }
        HellbreakLoadResolvedPlayer($playerNumber, $resolved);
    } else {
        $archetype = $playerNumber === 1 ? 'DRACULA' : 'JAWS';
        HellbreakLoadFixturePlayer($playerNumber, $archetype);
        ++$fixtureSeats;
    }
    $deck = &GetDeck($playerNumber);
    if(!$isTutorial) EngineShuffle($deck, true);
    HellbreakReindexZone($deck);
    ++$playerNumber;
}

while($playerNumber <= 2) {
    HellbreakLoadFixturePlayer($playerNumber, $playerNumber === 1 ? 'DRACULA' : 'JAWS');
    $deck = &GetDeck($playerNumber);
    if(!$isTutorial) EngineShuffle($deck, true);
    HellbreakReindexZone($deck);
    ++$fixtureSeats;
    ++$playerNumber;
}

$initiative = $isTutorial ? 1 : random_int(1, 2);
SetTurnNumber(0);
SetFirstPlayer($initiative);
SetInitiativePlayer($initiative);
SetTurnPlayer($initiative);
SetCurrentPhase('SETUP_LOCATION');
SetPhaseParameters('-');
SetPreviousActionPassLike(false);
SetSlumberPlayer(0);
SetSlumberUsed(false);
SetActionSequence(0);
SetWinner(0);
SetFixtureMode($fixtureSeats === 2);
$autoSetupPlayers = isset($lobby->goldfishPlayers) && is_array($lobby->goldfishPlayers)
    ? array_values(array_map('intval', $lobby->goldfishPlayers))
    : [];
DecisionQueueController::StoreVariable('HellbreakAutoSetupPlayers', $autoSetupPlayers);
if($isTutorial && function_exists('HellbreakTutorialInitialize')) HellbreakTutorialInitialize();
HellbreakBeginSetup();

WriteGamestate(__DIR__ . '/');

$lobby->gameName = $gameName;
if(!SimGameWriteAuthKeysFromLobby('HellbreakSim', $gameName, $lobby)) {
    throw new RuntimeException('Unable to store Hellbreak game authentication metadata.');
}

?>
