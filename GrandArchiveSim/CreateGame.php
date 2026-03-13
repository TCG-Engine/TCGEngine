<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
//include_once __DIR__ . '/../RBDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../APIKeys/APIKeys.php';

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

$firstPlayer = &GetFirstPlayer();
//$firstPlayer = &FirstPlayerValue();
$firstPlayer = 1;
//$turnPlayer = &TurnPlayerValue();
$turnPlayer = &GetTurnPlayer();
$turnPlayer = $firstPlayer;
$currentTurn = &GetTurnNumber();
$currentTurn = 1;

$currentPhase = &GetCurrentPhase();
$currentPhase = 'WU';
SetPhaseParameters("-");
AdvanceAndExecute("PASS");
AutoAdvanceAndExecute();

WriteGamestate(__DIR__ . "/");

$lobby->gameName = $gameName;
//TODO: Handle $gameName = ""

function LoadPlayer($playerID, $deckLink, $preconstructedDeck = '') {
    global $tcgArchitectAPIKey;
    // For now, ignore deckLink and use the preconstructed deck
    // When preconstructedDeck is "Refractory" or empty, use the default deck
    $gameDeck = &GetDeck($playerID);
    $material = &GetMaterial($playerID);

    if (!empty($deckLink)) {
        // Extract UUID from a full URL or bare UUID string
        if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $deckLink, $matches)) {
            $uuid = $matches[1];
            $apiUrl = "https://api.tcgarchitect.com/api/decks/" . $uuid;
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'x-api-key: '. $tcgArchitectAPIKey
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $apiResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($curlError) error_log("TCGArchitect API curl error: " . $curlError);
            if ($apiResponse !== false) {
                $deckData = json_decode($apiResponse, true);
                if ($deckData && isset($deckData['cards'])) {
                    foreach ($deckData['cards'] as $card) {
                        $cardID = $card['id'];
                        $quantity = intval($card['pivot']['quantity']);
                        $deckType = $card['pivot']['deck_type'];
                        for ($i = 0; $i < $quantity; ++$i) {
                            if ($deckType === 'material') {
                                array_push($material, new Material($cardID));
                            } else {
                                array_push($gameDeck, new Deck($cardID));
                            }
                        }
                    }
                    Shuffle($gameDeck);
                    return;
                }
            }
        }
    }

    /*
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
        */
    $deck = ["5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai"];
    for($i=0; $i<count($deck); ++$i) {
        $cardID = $deck[$i];
        array_push($gameDeck, new Deck($cardID));
    }
    Shuffle($gameDeck);

    $materialDeck = ["LMyKyVC2O9","j6dkdoxyqt","59ipqa91r2","enxi6tshtu","2gv7DC0KID"];
    for($i=0; $i<count($materialDeck); ++$i) {
        $cardID = $materialDeck[$i];
        array_push($material, new Material($cardID));
    }
}

?>