<?php
// SOR_016
// Cost 6 - Grand Admiral Thrawn - Patient and Insightful - [Cunning,Villainy] - Power 3 - HP 9
// Text: When the action phase starts: Look at the top card of each player's deck. / Action [1 resource, exhaust]: Reveal the top card of any player's deck. Exhaust a unit that costs the same as or less than the revealed card.
// DeployText: When the action phase starts: Look at the top card of each player's deck. / On Attack: You may reveal the top card of any player's deck. Exhaust a unit that costs the same as or less than the revealed card.
// Epic Action: If you control 6 or more resources, deploy this leader.

$onAttackAbilities["SOR_016:0"] = function($player) {
    global $playerID;
    $playerID = $player;
    DecisionQueueController::AddDecision($player, 'YESNO', '', 1, 'Use_Thrawn_ability?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016#2', 1);
};

// "Reveal the top card of ANY player's deck" — YOU are a legal pick, so this is an $includeSelf player
// choice, not an opponent choice.
// ⚠ 2-PLAYER KEEPS ITS YES/NO PROMPT, deliberately. With two seats "any player" is "mine or theirs",
// which the existing YESNO already says more clearly than a two-name menu would — and switching it
// would rewrite the prompt (and every existing 2-player section's answer) for no gain. Invariant I1:
// a conversion must not change Premier. The player picker appears only where there is a real choice.
if (!function_exists('_SWUThrawnAskDeckOwner')) {
    function _SWUThrawnAskDeckOwner(int $player, string $context): void {
        if (SeatCountForGame() > 2) {
            SWUQueueChooseOpponent($player, "SOR_016#OWNER|{$context}",
                "Reveal_the_top_card_of_which_player's_deck?", null, true);
            return;
        }
        DecisionQueueController::AddDecision($player, 'YESNO', '', 1, 'Own_deck_or_opponent?');
        DecisionQueueController::AddDecision($player, 'CUSTOM', "SOR_016#0|{$context}", 1);
    }
}

// Twin Suns continuation: the picked seat arrives as "P{n}".
$customDQHandlers["SOR_016#OWNER"] = function($player, $parts, $lastDecision) {
    $context = $parts[0] ?? 'action';
    $owner   = SWUPickedOpponent($lastDecision);   // reads ANY seat token, the caster's own included
    if ($owner <= 0) {
        if ($context === 'action') SWUAfterAction(intval($player));
        return;
    }
    _SWUThrawnReveal(intval($player), $owner, $context);
};

// YES: proceed to deck choice. NO: return (combat continuation already queued).
$customDQHandlers["SOR_016#2"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    _SWUThrawnAskDeckOwner(intval($player), 'attack');
};

// Shared handler: YES = own deck, NO = opponent's deck.
// Peeks top card, finds units with cost <= that card's cost, queues PASSPARAMETER or MZCHOOSE.
// Context param 'action' calls SWUAfterAction when done; 'attack' does not (combat handles it).
$customDQHandlers["SOR_016#0"] = function($player, $parts, $lastDecision) {
    $context = $parts[0] ?? 'action';
    // 2-player only (see _SWUThrawnAskDeckOwner): YES = own deck, NO = the single opponent's.
    _SWUThrawnReveal(intval($player), ($lastDecision === 'YES') ? intval($player) : OtherPlayer(intval($player)), $context);
};

// The whole reveal-and-exhaust, once the deck's owner is known. Both entry paths (the 2-player YES/NO
// and the Twin Suns picker) funnel through here so they cannot drift.
function _SWUThrawnReveal(int $player, int $deckOwner, string $context): void {
    global $playerID;
    $playerID = intval($player);

    // Top card = first non-removed entry (matches the APS handler's filtering).
    $deck   = GetDeck($deckOwner);
    $topIdx = null;
    foreach ($deck as $i => $c) {
        if (empty($c->removed ?? false)) { $topIdx = $i; break; }
    }
    if ($topIdx === null) {
        if ($context === 'action') SWUAfterAction($player);
        return;
    }

    $topCard = $deck[$topIdx];
    $topCost = intval(CardCost($topCard->CardID));

    // Reveal top card (cosmetic flash message).
    $savedPID = $playerID;
    $playerID = $deckOwner;
    DoRevealCard($deckOwner, "myDeck-" . $topIdx);
    $playerID = $savedPID;
    AddGameLogEntry(
        'REVEAL',
        'Grand Admiral Thrawn reveals: ' . GameLogCardRef($topCard->CardID)
    );

    // Collect all units with cost <= top card cost.
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $zone) {
        foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
            $obj = GetZoneObject($mz);
            if ($obj === null || ($obj->removed ?? false)) continue;
            if (intval(CardCost($obj->CardID)) <= $topCost) $targets[] = $mz;
        }
    }

    if (empty($targets)) {
        if ($context === 'action') SWUAfterAction($player);
        return;
    }

    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Exhaust_a_unit_costing_at_most_' . $topCost);
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016#1|' . $context, 1);
}

// Exhausts the chosen unit. 'action' context calls SWUAfterAction; 'attack' does not.
$customDQHandlers["SOR_016#1"] = function($player, $parts, $lastDecision) {
    $context = $parts[0] ?? 'action';
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        if ($context === 'action') SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    OnExhaustCard($player, $lastDecision);
    if ($context === 'action') SWUAfterAction($player);
};

// SOR_016 Grand Admiral Thrawn — Leader Action [1 resource, Exhaust]:
// Reveal the top card of any player's deck. Exhaust a unit that costs <= that card's cost.
$leaderAbilities["SOR_016"] = function(int $player): void {
    global $playerID;
    $playerID = $player;


    _SWUThrawnAskDeckOwner(intval($player), 'action');
};
