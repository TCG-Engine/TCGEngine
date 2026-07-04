<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../APIKeys/APIKeys.php';
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

$ttl = 600;

/**
 * Full game setup. Returns the new $gameName.
 *   $opts['forcedFirstPlayer'] : 1|2 to override the coin flip (null = random)
 *   $opts['resolvedDecks']     : [seat => SWUResolveDeckInput-shaped array] to inject
 *                                already-resolved decks (e.g. sideboarded Bo3 games)
 */
function SWUSetupGame($lobby, $opts = []) {
    global $gameName;
    $gameName = GetGameCounter(__DIR__ . '/Games');
    InitializeGamestate();
    WriteGamestate(__DIR__ . "/");
    ParseGamestate(__DIR__ . "/");

    // Persist the game mode (goldfish/hotseat) as a never-cleared GlobalEffect on P1 so all
    // runtime branching (SWUGameMode()) can read it. Normal games leave $mode '' and write nothing.
    $mode = '';
    if (isset($lobby->format)) {
        $f = strtolower((string)$lobby->format);
        if ($f === 'goldfish' || $f === 'hotseat') $mode = $f;
    }
    if ($mode === 'goldfish') AddGlobalEffects(1, 'SWU_MODE_GOLDFISH');
    if ($mode === 'hotseat')  AddGlobalEffects(1, 'SWU_MODE_HOTSEAT');

    $resolvedDecks = isset($opts['resolvedDecks']) && is_array($opts['resolvedDecks']) ? $opts['resolvedDecks'] : [];

    // ─── Step 1–2: Load decks (leader, base, main deck) for each player ───────────
    $playerCounter = 1;
    $deckLoadOk = true;
    foreach ($lobby->players as $player) {
        $player->setGamePlayerID($playerCounter);
        $injected = $resolvedDecks[$playerCounter] ?? null;
        if (!LoadPlayerDeck($playerCounter, $player->getDeckLink(), $player->getPreconstructedDeck(), $injected)) {
            // Goldfish P2 is an intentionally empty passive seat — its failed load must NOT block
            // the pregame (which is what left P1 unable to act). Every other failure still gates.
            if (!($mode === 'goldfish' && $playerCounter === 2)) $deckLoadOk = false;
        }
        ++$playerCounter;
    }

    // ─── Step 3: Determine first player ───────────────────────────────────────────
    // Forced (Bo3 / loser's choice) or random coin flip; first player holds initiative.
    $forced = $opts['forcedFirstPlayer'] ?? null;
    if ($mode === 'goldfish') $forced = 1;   // solo: the human seat always opens
    $firstPlayer = &GetFirstPlayer();
    $firstPlayer = ($forced === 1 || $forced === 2) ? $forced : random_int(1, 2);

    $turnPlayer = &GetTurnPlayer();
    $turnPlayer = $firstPlayer;

    $currentTurn = &GetTurnNumber();
    $currentTurn = 1;

    // Initiative starts with the first player, not yet taken this round.
    SetInitiativeCounter("P{$firstPlayer}_UNCLAIMED");

    if ($deckLoadOk) SetFlashMessage('');
    $currentPhase = &GetCurrentPhase();
    $currentPhase = 'APS';
    SetPhaseParameters("-");

    // ─── Steps 4–6: Draw opening hands, queue mulligan decisions, resource 2 ──────
    if ($deckLoadOk) {
        QueuePregameSetup($firstPlayer);
        AdvanceAndExecute("PASS");
        AutoAdvanceAndExecute();
        SaveUndoVersion($firstPlayer, "Start of Game");
    }

    WriteGamestate(__DIR__ . "/");
    $lobby->gameName = $gameName;
    SimGameWriteAuthKeysFromLobby('SWUSim', $gameName, $lobby);
    return $gameName;
}

// Backward-compatible entrypoint: the queue still does `include '.../CreateGame.php'`
// with an ambient $lobby in scope. Only auto-run when that ambient lobby exists.
if (isset($lobby) && is_object($lobby)) {
    SWUSetupGame($lobby);
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Resolve and load a deck for one player.
 * Returns true on success, false on failure (caller should abort game setup).
 */
function LoadPlayerDeck($playerID, $deckLink, $preconstructedDeck = '', $resolved = null) {
    if ($resolved === null) {
        if (empty($deckLink)) {
            SetFlashMessage("Player $playerID has no deck link. Please provide a SWUDeck or SWUDB deck URL.");
            return false;
        }
        $resolved = SWUResolveDeckInput($deckLink);
    }

    if (!is_array($resolved) || empty($resolved['success'])) {
        $msg = is_array($resolved) ? ($resolved['message'] ?? 'Unknown error') : 'Unknown error';
        SetFlashMessage("Could not load deck for Player $playerID: $msg");
        return false;
    }

    if (!empty($resolved['unresolved'])) {
        $missing = implode(', ', array_slice($resolved['unresolved'], 0, 5));
        $extra = count($resolved['unresolved']) > 5 ? ' (and more)' : '';
        SetFlashMessage("Warning: some cards for Player $playerID could not be resolved: $missing$extra");
    }

    // Leader → Leader zone
    if (!empty($resolved['leader'])) {
        $leaderZone = &GetLeader($playerID);
        $newLeader = new Leader($resolved['leader']);
        $newLeader->Ready = true;
        // The generated zone constructor defaults absent numeric fields to -1 (not the schema's
        // Damage:number=0), and `new Leader($cardID)` passes only the CardID — so Damage would
        // start at -1. Force it to 0 so the leader begins undamaged.
        $newLeader->Damage = 0;
        array_push($leaderZone, $newLeader);
    }

    // Base → Base zone
    if (!empty($resolved['base'])) {
        $baseZone = &GetBase($playerID);
        $newBase  = new Base($resolved['base']);
        // Same constructor-default gap as the leader above: `new Base($cardID)` would leave
        // Damage at the -1 fallback instead of the schema default 0, making every base start at
        // -1 damage (so a base attack renders one counter short). Force it undamaged.
        $newBase->Damage = 0;
        // Seed the per-game use budget for repeatable base Actions (e.g. LOF_022 Mystic Monastery).
        // These track remaining uses in NumUses and are exempt from the per-round refill.
        global $baseActionNumUses;
        if (isset($baseActionNumUses[$resolved['base']])) {
            $newBase->NumUses = intval($baseActionNumUses[$resolved['base']]);
        }
        array_push($baseZone, $newBase);
    }

    // Main deck → Deck zone (shuffled)
    if (!empty($resolved['mainDeck'])) {
        $gameDeck = &GetDeck($playerID);
        foreach ($resolved['mainDeck'] as $cardID) {
            array_push($gameDeck, new Deck($cardID));
        }
        EngineShuffle($gameDeck, true);
    }

    return true;
}

/**
 * Queue all pregame setup decisions.
 *
 * CR 5.2.1 setup order:
 *   d. Shuffle decks and draw opening hands (6 cards each).
 *   e. Choose whether to mulligan (one per player; initiative holder decides first).
 *   f. Resource two cards (each player chooses 2 from their opening hand).
 */
function QueuePregameSetup($firstPlayer) {
    $secondPlayer = ($firstPlayer == 1) ? 2 : 1;

    // Step d: Draw the opening hand for each player simultaneously.
    // Default is 6; a player's base may modify this (JTL_021 Colossus −1, JTL_028 Nabat Village +3).
    $handSize = function ($player) {
        $baseArr = &GetBase($player);
        $baseID  = !empty($baseArr) ? $baseArr[0]->CardID : '';
        return max(0, 6 + SWUStartingHandModifier($baseID));
    };
    $baseSuppressesMulligan = function ($player) {
        $baseArr = &GetBase($player);
        $baseID  = !empty($baseArr) ? $baseArr[0]->CardID : '';
        return SWUBaseSuppressesMulligan($baseID);
    };

    $p1Hand = $handSize(1);
    for ($i = 0; $i < $p1Hand; ++$i) {
        DoDrawCard(1, 1);
    }
    $p2Hand = $handSize(2);
    for ($i = 0; $i < $p2Hand; ++$i) {
        DoDrawCard(2, 1);
    }

    // Step e: Mulligan — initiative holder decides first.
    // Block 10 ensures YESNO sits at the front of the queue, before resource choices (block 50).
    // A base may forbid the mulligan (JTL_028 Nabat Village) — skip the decision for that player.
    if (!$baseSuppressesMulligan($firstPlayer)) {
        DecisionQueueController::AddDecision($firstPlayer, "YESNO", "mulligan", 10,
            tooltip:"Take_a_mulligan_(discard_hand_and_draw_6_new_cards)?");
        DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "MulliganDecision|$firstPlayer", 10);
    }

    // Goldfish: P2 is an empty passive seat (no hand to mulligan). Skip its mulligan decision so it
    // never blocks P1's pregame. Its ChooseStartingResource entries (block 50) auto-drain (no cards).
    $skipGoldfishBot = (SWUGameMode() === 'goldfish' && $secondPlayer === 2);
    if (!$baseSuppressesMulligan($secondPlayer) && !$skipGoldfishBot) {
        DecisionQueueController::AddDecision($secondPlayer, "YESNO", "mulligan", 10,
            tooltip:"Take_a_mulligan_(discard_hand_and_draw_6_new_cards)?");
        DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "MulliganDecision|$secondPlayer", 10);
    }

    // Step f: Resource 2 cards — each player chooses 2 from their hand.
    // Block 50 keeps these behind the mulligan decisions (block 10) in the queue.
    DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "ChooseStartingResource", 50,
        tooltip:"Choose_a_card_to_resource_(1/2)");
    DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "ChooseStartingResource", 50,
        tooltip:"Choose_a_card_to_resource_(2/2)");

    DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "ChooseStartingResource", 50,
        tooltip:"Choose_a_card_to_resource_(1/2)");
    DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "ChooseStartingResource", 50,
        tooltip:"Choose_a_card_to_resource_(2/2)");
}
