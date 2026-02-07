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
    LoadPlayer($playerCounter, $player->getDeckLink(), $player->getPreconstructedDeck());
    ++$playerCounter;
}

$bg1 = &GetBG1();
array_push($bg1, new BG1("GudnakTerrain"));
$bg2 = &GetBG2();
array_push($bg2, new BG2("GudnakTerrain"));
$bg3 = &GetBG3();
array_push($bg3, new BG3("GudnakTerrain"));
$bg4 = &GetBG4();
array_push($bg4, new BG4("GudnakTerrain"));
$bg5 = &GetBG5();
array_push($bg5, new BG5("GudnakTerrain"));
$bg6 = &GetBG6();
array_push($bg6, new BG6("GudnakTerrain"));
$bg7 = &GetBG7();
array_push($bg7, new BG7("GudnakTerrain"));
$bg8 = &GetBG8();
array_push($bg8, new BG8("GudnakTerrain"));
$bg9 = &GetBG9();
array_push($bg9, new BG9("GudnakTerrain"));

$firstPlayer = &GetFirstPlayer();
//$firstPlayer = &FirstPlayerValue();
$firstPlayer = 1;
//$turnPlayer = &TurnPlayerValue();
$turnPlayer = &GetTurnPlayer();
$turnPlayer = ($firstPlayer == 1) ? 2 : 1;
$actions = &GetActions($firstPlayer);
$actions = 2;

$currentPhase = &GetCurrentPhase();
$currentPhase = 'ACT';
SetPhaseParameters("-");
AdvanceAndExecute("PASS");
AutoAdvanceAndExecute();

WriteGamestate(__DIR__ . "/");

$lobby->gameName = $gameName;
//TODO: Handle $gameName = ""

function LoadPlayer($playerID, $deckLink, $preconstructedDeck = '') {
    // For now, ignore deckLink and use the preconstructed deck
    // When preconstructedDeck is "Refractory" or empty, use the default deck
    $gameDeck = &GetDeck($playerID);
    if($preconstructedDeck == '' || $preconstructedDeck == 'Refractory') {
        $deck = ["RYBF1DSKH","RYBF1DWNB","RYBF1HBTCS","RYBF1HSTDB","RYBF1SLSD","RYBF1SLSD","RYBF2DSKH","RYBF2DWNB","RYBF2HBLGF","RYBF2HSLRC","RYBF2SLSD","RYBF2SLSD","RYBF3DWNB","RYBF3HBLGR","RYBF3SLSD","RYBTBRRG","RYBTPDRL","RYBTRPDD","RYBTRPOS","RYBTTMPO"];
    }
    else if($preconstructedDeck == 'Gloaming') {
        $deck = ["GMBF1SPCH","GMBF2SPCH","GMBF2SPCH","GMBF1AMBT","GMBF2AMBT","GMBF3AMBT","GMBF1SKLS","GMBF1SKLS","GMBF2SKLS","GMBF3SKLS","GMBTBYNG","GMBTCNFN","GMBTMNTM","GMBTSLSW","GMBTWHTT","GMBF3HVRKG","GMBF2HDTHK","GMBF2HNCRM","GMBF1HNDMN","GMBF1HNDHR"];
    }
    else if($preconstructedDeck == 'Shardsworn') {
        $deck = ["SHBF1GBHT","SHBF1GBHT","SHBF1OGRB","SHBF1ORCS","SHBF2GBHT","SHBF2GBHT","SHBF2OGRB","SHBF2ORCS","SHBF3GBHT","SHBF3ORCS","SHBF1HELDR","SHBF1HELRN","SHBF2HMSTH","SHBF2HGRCK","SHBF3HELVV","SHBTDVSN","SHBTMBSH","SHBTSNAR","SHBTTOSS","SHBTTRBZ"];
    }
    else if($preconstructedDeck == 'Delguon') {
        $deck = ["DNBF1CHNT","DNBF2CHNT","DNBF3CHNT","DNBF1CSHB","DNBF1CSHB","DNBF2CSHB","DNBF2CSHB","DNBF3CSHB","DNBF1DRKS","DNBF2DRKS","DNBF3HNLKS","DNBF2HFGFT","DNBF1HFTSV","DNBF2HDRDV","DNBF1HSTNS","DNBTMLTN","DNBTRTHQ","DNBTLTMS","DNBTSCTN","DNBTSMRT"];
    }
    for($i=0; $i<count($deck); ++$i) {
        $cardID = $deck[$i];
        array_push($gameDeck, new Deck($cardID));
    }
    Shuffle($gameDeck);
    Draw($playerID, amount: 5);
    // Future: Add handling for other preconstructed decks or deckLink
}

?>