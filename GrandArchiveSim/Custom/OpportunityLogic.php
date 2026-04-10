<?php
// GrandArchiveSim/Custom/OpportunityLogic.php
// Custom logic for the "Opportunity" card in the Grand Archive Sim.


/**
 * Check if a player currently has Opportunity to act at fast speed.
 * A player has Opportunity when:
 *  - The EffectStack is non-empty (a spell/ability is pending resolution), OR
 *  - Combat is active (between attack declaration and cleanup)
 */
function HasOpportunity($player) {
    $effectStack = &GetEffectStack();
    if(!empty($effectStack)) return true;
    if(IsCombatActive()) return true;
    return false;
}

// =============================================================================
// Opportunity / Priority System
// =============================================================================

/**
 * Get the list of fast-speed cards a player can play from their hand.
 * Returns an array of mzID strings from the player's own perspective (e.g. "myHand-0").
 *
 * @param int $player The player to check.
 * @return array Array of mzID strings for fast-speed hand cards.
 */
// Cards with [Class Bonus] Fast Activation — cardID => required class(es)
$cbFastActivationCards = [
    "a5igwbsmks" => ["TAMER"], // Spirited Falconer
    "itwys9kf4r" => ["ASSASSIN"], // Cloaked Executioner
    "cqadnk9iz0" => ["TAMER"], // Baby Green Slime
    "2bbmoqk2c7" => ["GUARDIAN"], // Rose, Eternal Paragon
    "f0ht2tsn0y" => ["GUARDIAN"], // Astarte, Celestial Dawn
    "jozihslnhz" => ["ASSASSIN"], // Sinister Mindreaver
    "yky280mtts" => ["TAMER"], // Flamebreak Chorus
    "uPn9SZdqrr" => ["GUARDIAN"], // Gustguard Bastion
];

// Cards with unconditional Fast Activation (no class bonus required)
$unconditionalFastCards = [
    "aljx2ru1w3" => true, // Flashfire Horse
];

function GetPlayableFastCards($player) {
    global $cbFastActivationCards, $unconditionalFastCards;
    $hand = &GetHand($player);
    $fastCards = [];
    for($i = 0; $i < count($hand); $i++) {
        $obj = $hand[$i];
        if(isset($obj->removed) && $obj->removed) continue;
        $speed = CardSpeed($obj->CardID);
        if($speed === true) {
            $fastCards[] = "myHand-" . $i;
        } elseif(isset($cbFastActivationCards[$obj->CardID]) && IsClassBonusActive($player, $cbFastActivationCards[$obj->CardID])) {
            $fastCards[] = "myHand-" . $i;
        } elseif(isset($unconditionalFastCards[$obj->CardID])) {
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "zeig1e49wb" && GetShiftingCurrents($player) === "NORTH") {
            // Solar Pinnacle: Fast Activation while SC faces North
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "0oyxjld8jh") {
            // Guan Yu, Prime Exemplar: Fast Activation if a Human ally you controlled died this turn
            $deadAllies = AllyDestroyedTurnCards($player);
            foreach($deadAllies as $deadCardID => $deadCount) {
                if(PropertyContains(CardSubtypes($deadCardID), "HUMAN")) {
                    $fastCards[] = "myHand-" . $i;
                    break;
                }
            }
        } elseif(GlobalEffectCount($player, "w1wgpeifd0") > 0 && PropertyContains(CardType($obj->CardID), "ALLY")) {
            // Expeditious Opening: next ally activated this turn gets fast activation
            $fastCards[] = "myHand-" . $i;
        } elseif(GlobalEffectCount($player, "t4owmcva0f") > 0
            && PropertyContains(CardType($obj->CardID), "ACTION")
            && PropertyContains(CardClasses($obj->CardID), "RANGER")) {
            // Bombastic Sprint: next Ranger action activated this turn gets fast activation
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "B1EbF6jcYF" && IsAliceBonusActive($player)) {
            // Golden Gambit: [Alice Bonus] Fast Activation
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "pvzvqx16w4" && GetChampionDamageTakenThisTurn($player) >= 35) {
            // Annihilation: [Damage 35+] Fast Activation
            $fastCards[] = "myHand-" . $i;
        }
    }

    // Diao Chan inherited ability: include eligible Reaction Spells from memory
    // Only when it's the opponent's turn, champion is awake, and has glimmer
    $turnPlayer = GetTurnPlayer();
    if($player != $turnPlayer && ChampionHasInLineage($player, "00xbh8oc00")) {
        $champObj = GetPlayerChampion($player);
        if($champObj !== null && $champObj->Status == 2 && !HasNoAbilities($champObj)) {
            $glimmerCount = GetCounterCount($champObj, "glimmer");
            if($glimmerCount > 0) {
                $memory = &GetMemory($player);
                for($mi = 0; $mi < count($memory); ++$mi) {
                    if(isset($memory[$mi]->removed) && $memory[$mi]->removed) continue;
                    $memCardID = $memory[$mi]->CardID;
                    if(PropertyContains(CardSubtypes($memCardID), "REACTION")
                        && PropertyContains(CardSubtypes($memCardID), "SPELL")) {
                        $spellCost = intval(CardCost_reserve($memCardID));
                        if($spellCost <= $glimmerCount) {
                            $fastCards[] = "myMemory-" . $mi;
                        }
                    }
                }
            }
        }
    }

    return $fastCards;
}

// --- EffectStack Opportunity ---------------------------------------------------
// After a card enters the EffectStack, the player who activated it gets priority
// first (they can chain more fast cards), then the opponent. Both must pass for
// the topmost card to resolve.

/**
 * DQ handler: After a card is placed on the EffectStack and costs are paid,
 * grant Opportunity. Per rules, the player who activated receives priority first.
 *
 * $player = the player who just placed a card on the EffectStack.
 */
$customDQHandlers["EffectStackOpportunity"] = function($player, $parts, $lastDecision) {
    $effectStack = &GetEffectStack();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = &GetEffectStack();
    if(empty($effectStack)) return;

    $otherPlayer = ($player == 1) ? 2 : 1;

    // Active player gets priority first (per rules: they can chain)
    $fastCards = GetPlayableFastCards($player);
    if(!empty($fastCards)) {
        $cardList = implode("&", $fastCards);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackActiveResponse|$otherPlayer", 100, "", 1);
    } else {
        // Active player can't respond, check opponent
        $fastCards2 = GetPlayableFastCards($otherPlayer);
        if(!empty($fastCards2)) {
            $cardList = implode("&", $fastCards2);
            DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
        } else {
            // Neither can respond, auto-resolve
            ResolveTopOfEffectStack();
        }
    }
};

/**
 * DQ handler: active player responded to EffectStack Opportunity.
 * $parts[0] = the other player's ID.
 */
$customDQHandlers["EffectStackActiveResponse"] = function($player, $parts, $lastDecision) {
    $otherPlayer = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Active player passed, check opponent
        $fastCards = GetPlayableFastCards($otherPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
        } else {
            // Both passed (opponent has no cards), resolve
            ResolveTopOfEffectStack();
        }
    } else {
        // Active player played a fast card — they keep priority
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
    }
};

/**
 * DQ handler: opponent responded to EffectStack Opportunity.
 */
$customDQHandlers["EffectStackOpponentResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Both players passed, resolve top of stack
        ResolveTopOfEffectStack();
    } else {
        // Opponent played a fast card — they get priority
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
    }
};

/**
 * DQ handler: After a card resolves from the EffectStack and all its abilities
 * finish, check whether there are more cards on the stack to resolve.
 * If stack is non-empty, grant Opportunity (turn player gets priority first after resolution).
 * If stack is empty, check for a pending Opportunity window (combat/ability) and re-grant it.
 *
 * Uses high block (200) so it runs after any ability decisions (block 1-100).
 */
$customDQHandlers["PostResolutionCheck"] = function($player, $parts, $lastDecision) {
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = &GetEffectStack();

    if(!empty($effectStack)) {
        // More cards to resolve — turn player gets priority first (per rules)
        $turnPlayer = GetTurnPlayer();
        $otherPlayer = ($turnPlayer == 1) ? 2 : 1;

        $fastCards = GetPlayableFastCards($turnPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "EffectStackActiveResponse|$otherPlayer", 100, "", 1);
        } else {
            $fastCards2 = GetPlayableFastCards($otherPlayer);
            if(!empty($fastCards2)) {
                $cardList = implode("&", $fastCards2);
                DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
                DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
            } else {
                ResolveTopOfEffectStack();
            }
        }
    } else {
        // Stack is empty — check for a pending Opportunity window (combat/ability)
        $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
        if($pendingHandler !== null && $pendingHandler !== "") {
            $firstPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityFirstPlayer") ?? GetTurnPlayer());
            $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? GetTurnPlayer());
            // Re-grant the Opportunity window (re-checks fast cards for both players)
            GrantOpportunityWindow($firstPlayer, $pendingHandler, $nextPlayer);
        }
    }
};

/**
 * Resolve the top card of the EffectStack.
 *
 * Swaps $playerID to match the card owner so that all my/their zone references
 * resolve correctly, then calls the generated CardActivated() wrapper (which
 * stores mzID, tracks MacroTurnIndex, calls OnCardActivated, and processes
 * ability decisions). After resolution, queues PostResolutionCheck to handle
 * remaining EffectStack entries.
 */
function ResolveTopOfEffectStack() {
    $effectStack = &GetEffectStack();
    DecisionQueueController::CleanupRemovedCards();
    if(empty($effectStack)) return;

    $topIndex = count($effectStack) - 1;
    $topObj = $effectStack[$topIndex];
    if($topObj == null) {
        $topIndex = $topIndex - 1;
        if($topIndex < 0) return;
        $topObj = $effectStack[$topIndex];
        if($topObj == null) return;
    }
    $cardOwner = $topObj->Controller;
    $topMZ = "EffectStack-" . $topIndex;

    // Swap $playerID to the card owner for correct my/their resolution
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $cardOwner;

    // Call the generated CardActivated() wrapper, which:
    //  - Stores mzID variable for ability code
    //  - Tracks MacroTurnIndex
    //  - Calls OnCardActivated (moves card, fires abilities)
    //  - Calls ExecuteStaticMethods to process any ability decisions
    CardActivated($cardOwner, $topMZ);

    // Queue PostResolutionCheck to run after all ability interactions (block 200)
    DecisionQueueController::AddDecision($cardOwner, "CUSTOM", "PostResolutionCheck", 200);

    // Process PostResolutionCheck now if no interactive decisions are pending
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($cardOwner, "-");

    // Restore $playerID
    $playerID = $savedPlayerID;
}

// --- General Opportunity Window ------------------------------------------------
// Used for combat and ability Opportunity windows. Stores a pending handler
// in DQ variables so that after any EffectStack detour (fast card played during
// the window), PostResolutionCheck can re-grant the window.

/**
 * Grant a full 2-player priority Opportunity window.
 * $firstPlayer gets priority first. After both pass, $nextHandler runs for $nextPlayer.
 * If either player plays a fast card, the EffectStack handles it, and after it
 * empties, PostResolutionCheck re-grants this window via the stored variables.
 *
 * @param int    $firstPlayer Player who gets priority first.
 * @param string $nextHandler CUSTOM DQ handler name to queue after both pass.
 * @param int    $nextPlayer  Player for whom to queue $nextHandler (default = $firstPlayer).
 */
function GrantOpportunityWindow($firstPlayer, $nextHandler, $nextPlayer = null) {
    if($nextPlayer === null) $nextPlayer = $firstPlayer;
    $secondPlayer = ($firstPlayer == 1) ? 2 : 1;

    // Store pending state so PostResolutionCheck can re-grant after EffectStack detour
    DecisionQueueController::StoreVariable("PendingOpportunityHandler", $nextHandler);
    DecisionQueueController::StoreVariable("PendingOpportunityNextPlayer", strval($nextPlayer));
    DecisionQueueController::StoreVariable("PendingOpportunityFirstPlayer", strval($firstPlayer));

    // Check first player's fast cards
    $fastCards1 = GetPlayableFastCards($firstPlayer);
    if(!empty($fastCards1)) {
        $cardList = implode("&", $fastCards1);
        DecisionQueueController::AddDecision($firstPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
        DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "OpportunityWindowFirstResponse", 100, "", 1);
    } else {
        // First player can't act, try second
        $fastCards2 = GetPlayableFastCards($secondPlayer);
        if(!empty($fastCards2)) {
            $cardList = implode("&", $fastCards2);
            DecisionQueueController::AddDecision($secondPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "OpportunityWindowSecondResponse", 100, "", 1);
        } else {
            // Neither can act, resolve immediately
            ResolveOpportunityWindow();
        }
    }
}

/**
 * Both players passed the Opportunity window. Clear pending state and queue the next handler.
 */
function ResolveOpportunityWindow() {
    $nextHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
    $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? "1");
    ClearOpportunityVariables();

    if($nextHandler === null || $nextHandler === "" || $nextHandler === "NoOp") return;

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $nextPlayer;

    DecisionQueueController::AddDecision($nextPlayer, "CUSTOM", $nextHandler, 100);
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($nextPlayer, "-");

    $playerID = $savedPlayerID;
}

function ClearOpportunityVariables() {
    DecisionQueueController::ClearVariable("PendingOpportunityHandler");
    DecisionQueueController::ClearVariable("PendingOpportunityNextPlayer");
    DecisionQueueController::ClearVariable("PendingOpportunityFirstPlayer");
}

/**
 * First player in an Opportunity window responded.
 */
$customDQHandlers["OpportunityWindowFirstResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // First player passed, check second player
        $secondPlayer = ($player == 1) ? 2 : 1;
        $fastCards = GetPlayableFastCards($secondPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($secondPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "OpportunityWindowSecondResponse", 100, "", 1);
        } else {
            // Both passed (second has no cards), resolve
            ResolveOpportunityWindow();
        }
    } else {
        // Player played a fast card — they keep priority
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
        // ActivateCard → DoActivateCard → EffectStack → EffectStackOpportunity
        // After stack empties, PostResolutionCheck re-grants this window
    }
};

/**
 * Second player in an Opportunity window responded.
 */
$customDQHandlers["OpportunityWindowSecondResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Both players passed, resolve
        ResolveOpportunityWindow();
    } else {
        // Player played a fast card — they keep priority
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
        // After stack empties, PostResolutionCheck re-grants this window
    }
};

/**
 * No-op handler for Opportunity windows that don't have a next step
 * (e.g., ability Opportunity — after both pass, game simply continues).
 */
$customDQHandlers["NoOp"] = function($player, $parts, $lastDecision) {
    // Intentionally empty
};

/**
 * DQ handler: After an activated ability resolves, grant Opportunity.
 * Per rules: the player who activated the ability receives priority first.
 * After both pass, game simply continues (NoOp).
 */
$customDQHandlers["AbilityOpportunity"] = function($player, $parts, $lastDecision) {
    GrantOpportunityWindow($player, "NoOp", $player);
};

?>
