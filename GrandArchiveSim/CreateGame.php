<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../APIKeys/APIKeys.php';

include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

$ttl = 600;

function LoadDefaultGoldfishLoadout($playerID) {
    $gameDeck = &GetDeck($playerID);
    $material = &GetMaterial($playerID);

    $deck = ["5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai","5n874ubgai"];
    for($i = 0; $i < count($deck); ++$i) {
        array_push($gameDeck, new Deck($deck[$i]));
    }
    EngineShuffle($gameDeck, true);

    $materialDeck = ["LMyKyVC2O9","j6dkdoxyqt","59ipqa91r2","enxi6tshtu","2gv7DC0KID"];
    for($i = 0; $i < count($materialDeck); ++$i) {
        array_push($material, new Material($materialDeck[$i]));
    }
}

// Goldfish dummy opponent: EMPTY main deck + a single Lv0 champion (Spirit of Fire) so GA's pregame
// (which requires each player to place a Lv0 champion) completes. The human seat tests its own curve.
function LoadEmptyGoldfishLoadout($playerID) {
    $material = &GetMaterial($playerID);
    array_push($material, new Material("LMyKyVC2O9")); // Spirit of Fire (Lv0 Spirit champion)
    // Main deck intentionally left empty.
}

// Full game setup. Callable (Match system spawns multiple games): $opts['resolvedDecks'] =
// [seat => resolver-output] to load pre-resolved decks; $opts['forcedFirstPlayer'] = 1|2 to override
// who goes first. Backward-compat: including this file with an ambient $lobby still auto-runs setup.
function GASetupGame($lobby, $opts = []) {
    global $gameName;
    $gameName = GetGameCounter(__DIR__ . '/Games');
    InitializeGamestate();
    WriteGamestate(__DIR__ . "/");
    ParseGamestate(__DIR__ . "/");

    // Persist the game mode (goldfish/hotseat) so the UI/mode checks can read it (GAGameMode()).
    $gaMode = '';
    if (isset($lobby->format)) {
        $f = strtolower((string)$lobby->format);
        if ($f === 'goldfish' || $f === 'hotseat') $gaMode = $f;
    }
    if ($gaMode !== '') DecisionQueueController::StoreVariable("GameMode", $gaMode);

    $goldfishPlayers = [];
    if(isset($lobby->goldfishPlayers) && is_array($lobby->goldfishPlayers)) {
        foreach($lobby->goldfishPlayers as $goldfishPlayer) {
            $playerNum = intval($goldfishPlayer);
            if($playerNum > 0) $goldfishPlayers[] = $playerNum;
        }
    }
    if(function_exists('SetGoldfishPlayers')) {
        SetGoldfishPlayers($goldfishPlayers);
    }

    $playerCounter = 1;
    foreach ($lobby->players as $player) {
        $player->setGamePlayerID($playerCounter);
        $injected = $opts['resolvedDecks'][$playerCounter] ?? null;
        if(in_array($playerCounter, $goldfishPlayers, true)) {
            // Goldfish: the opponent seat has an EMPTY main deck (nothing to draw/play) — the human seat
            // just tests its own draws/curve. GA requires each player to place a Lv0 champion in pregame,
            // so the dummy still gets a single Lv0 champion (Spirit of Fire) and no main deck.
            LoadEmptyGoldfishLoadout($playerCounter);
        } else if (is_array($injected)) {
            LoadResolvedDeck($playerCounter, $injected);   // Match system: pre-resolved (+ sideboarded) deck
        } else {
            LoadPlayer($playerCounter, $player->getDeckLink(), $player->getPreconstructedDeck());
        }
        ++$playerCounter;
    }

    $firstPlayer = &GetFirstPlayer();
    $firstPlayer = (isset($opts['forcedFirstPlayer']) && in_array($opts['forcedFirstPlayer'], [1,2], true))
        ? intval($opts['forcedFirstPlayer']) : 1;
    $turnPlayer = &GetTurnPlayer();
    $turnPlayer = $firstPlayer;
    $currentTurn = &GetTurnNumber();
    $currentTurn = 1;

    SetFlashMessage('');
    $currentPhase = &GetCurrentPhase();
    $currentPhase = 'WU';
    SetPhaseParameters("-");
    QueuePregameStartingChampionSetup();
    AdvanceAndExecute("PASS");
    AutoAdvanceAndExecute();
    SaveUndoVersion($firstPlayer, "Pregame Starting Champion");

    // Stamp the match ref into the gamestate so the GA client overlay (window.GAMatchId) recognizes a
    // match game. Canonical cross-game state is the pointer files (Core/Match); this is client-detection only.
    if (isset($opts['matchId']) && $opts['matchId'] !== '') {
        DecisionQueueController::StoreVariable('MatchId', strval($opts['matchId']));
        DecisionQueueController::StoreVariable('GameNumber', strval(intval($opts['gameNumber'] ?? 1)));
    }

    WriteGamestate(__DIR__ . "/");

    $lobby->gameName = $gameName;
    SimGameWriteAuthKeysFromLobby('GrandArchiveSim', $gameName, $lobby);
    return $gameName;
}

// Backward-compatible entrypoint: the queue still does `include '.../CreateGame.php'` with an ambient
// $lobby in scope (goldfish/hotseat + legacy). Only auto-run when that ambient lobby exists.
if (isset($lobby) && is_object($lobby)) {
    GASetupGame($lobby);
}

// Load an already-resolved deck (Match system passes pre-resolved / sideboarded decks). Mirrors the
// resolved-deck path of LoadPlayer. The sideboard is NOT placed on the field — it lives in the Match.
function LoadResolvedDeck($playerID, array $resolved) {
    $gameDeck = &GetDeck($playerID);
    $material = &GetMaterial($playerID);
    foreach (($resolved['material'] ?? []) as $cardID) { array_push($material, new Material($cardID)); }
    foreach (($resolved['mainDeck'] ?? []) as $cardID) { array_push($gameDeck, new Deck($cardID)); }
    EngineShuffle($gameDeck, true);
}

function LoadPlayer($playerID, $deckLink, $preconstructedDeck = '') {
    // For now, ignore deckLink and use the preconstructed deck
    // When preconstructedDeck is "Refractory" or empty, use the default deck
    $gameDeck = &GetDeck($playerID);
    $material = &GetMaterial($playerID);

    if (!empty($deckLink)) {
        $resolved = GrandArchiveResolveDeckInput($deckLink);
        if ($resolved['success']) {
            foreach ($resolved['material'] as $cardID) {
                array_push($material, new Material($cardID));
            }
            foreach ($resolved['mainDeck'] as $cardID) {
                array_push($gameDeck, new Deck($cardID));
            }
            if (!empty($resolved['unresolved'])) {
                error_log("Free-text deck import: unresolved cards for player $playerID: " . implode(', ', $resolved['unresolved']));
            }
            EngineShuffle($gameDeck, true);
            return;
        }

        error_log("Deck import failed for player $playerID: " . $resolved['message']);
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
    EngineShuffle($gameDeck, true);

    $materialDeck = ["LMyKyVC2O9","j6dkdoxyqt","59ipqa91r2","enxi6tshtu","2gv7DC0KID"];
    for($i=0; $i<count($materialDeck); ++$i) {
        $cardID = $materialDeck[$i];
        array_push($material, new Material($cardID));
    }
}

?>
