<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../RBDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';

include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

$ttl = 600;

// ASSUMES: $lobby
$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
WriteGamestate(__DIR__ . "/");
ParseGamestate(__DIR__ . "/");

$playerCounter = 1;
foreach ($lobby->players as $player) {
    $player->setGamePlayerID($playerCounter);
    LoadPlayer($playerCounter, $player->getDeckLink());
    ++$playerCounter;
}

$bg1 = &GetBG1();
array_push($bg1, new BG1("GudnakTerrain"));

$firstPlayer = &GetFirstPlayer();
//$firstPlayer = &FirstPlayerValue();
$firstPlayer = 1;
//$turnPlayer = &TurnPlayerValue();
$turnPlayer = &GetTurnPlayer();
$turnPlayer = ($firstPlayer == 1) ? 2 : 1;

$currentPhase = &GetCurrentPhase();
$currentPhase = 'ACT';
SetPhaseParameters("-");
AdvanceAndExecute("PASS");
AutoAdvanceAndExecute();

WriteGamestate(__DIR__ . "/");

$lobby->gameName = $gameName;
//TODO: Handle $gameName = ""

function LoadPlayer($playerID, $deckLink) {
    if($deckLink == "") {
        return;
    }

    $gameDeck = &GetDeck($playerID);

    $deck = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    for($i=0; $i<count($deck); ++$i) {
      $cardID = $deck[$i];
      array_push($gameDeck, new Deck($cardID));
    }

    Shuffle($gameDeck);
    Draw($playerID, amount: 5);
}

?>