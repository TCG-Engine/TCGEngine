<?php
// HMW_008
// Cost 5 - General Grievous - Separatist Warlord - [Command,Villainy] - Power 3 - HP 6 - Separatist, Official
// Text: Action [Exhaust]: Play 2 units from your hand (one at a time, paying their costs).
// Epic Action: If you control 5 or more resources, deploy this leader.
// DeployText: While you control more units than an opponent, this unit gets +3/+0.

// ── FRONT: Action [Exhaust]: play 2 units from hand, one at a time, each paying its own cost ─────────
//
// ⚠ EACH PLAY IS DECLINABLE even though the card prints no "may" — USER RULING: the hand is a HIDDEN
// zone, so a player can never be forced to reveal they were holding a playable card. Declining ends the
// ability; the [Exhaust] is still spent, because the cost buys the ability, not the effect resolving.
//
// ⚠ THE SECOND OFFER IS BUILT AT DRAIN TIME, not alongside the first. "…paying their costs" means the
// first play SPENDS resources, so what is affordable for the second is not knowable until it resolves —
// the HMW_035 recompute-before-every-pick rule. _SWUHmw008OfferPlay is therefore re-entered from a
// CUSTOM queued at a HIGHER block, which also lets the played unit's own entry triggers finish first.
//
// ⚠ NESTED PLAYS, NOT bare ActivateCard. Each play runs inside SWUWithNestedActionFrame so its own
// after-action cannot close the leader Action, which this ability closes exactly once itself. With TWO
// plays a double close would CANCEL OUT and be invisible — Front_PlayOneThenDecline_NoExtraAction is
// the odd-count section that can actually see it.
// SWUBeginPlayCard (not ActivateCard) is the entry point so ADDITIONAL COSTS are determined and paid:
// ActivateCard is only the second half of the play ceremony.
if (!function_exists('_SWUHmw008OfferPlay')) {
    function _SWUHmw008OfferPlay(int $player, int $remaining, int $block): void {
        global $playerID; $playerID = $player;
        if ($remaining <= 0) { SWUAfterAction($player); return; }
        // "play a UNIT" — an event or upgrade in hand is not a legal pick (CR 17.c also keeps a Piloting
        // card from being played as an upgrade here; SWUBeginPlayCard's unitOnly flag enforces that).
        $targets = SWUPlayablesAtDiscount($player, 'myHand', ['Unit'], 0);
        if (empty($targets)) { SWUAfterAction($player); return; }   // soft pass: nothing playable
        SWUQueueMayChooseTarget($player, $targets, "Play_a_unit_from_your_hand?",
            "Choose_a_unit_to_play", "HMW_008#0|{$remaining}|{$block}", $block);
    }
}

$leaderAbilities["HMW_008"] = function($player) {
    // No affordability case: an exhaust-only leader Action is ALWAYS usable and resolves to nothing when
    // it can do nothing (the TS26_02 Anakin rule — a condition in SWULeaderActionAffordable makes the
    // whole action VANISH instead of soft-passing). SWULeaderAction has already exhausted the leader.
    _SWUHmw008OfferPlay(intval($player), 2, 1);
};

$customDQHandlers["HMW_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = intval($parts[0] ?? 0);
    $block     = intval($parts[1] ?? 1);
    if (SWUDecisionDeclined($lastDecision) || !str_contains((string)$lastDecision, '-')) {
        SWUAfterAction(intval($player));                      // declined → the ability ends here
        return;
    }
    SWUWithNestedActionFrame(fn() => SWUBeginPlayCard(intval($player), (string)$lastDecision, 0, unitOnly: true));
    // Re-offer from a CUSTOM at the NEXT block: the pool must be rebuilt after this play has paid, and
    // the played unit's own entry decisions must resolve before the second offer appears.
    // dontSkipOnPass=1: a decline anywhere inside the played unit's entry (an Ambush "no", say) leaves a
    // sticky PASS, and an unflagged CUSTOM behind it is SKIPPED — which would silently eat the 2nd play.
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "HMW_008#1|" . ($remaining - 1) . "|" . ($block + 1), $block + 1, '', 1);
};

$customDQHandlers["HMW_008#1"] = function($player, $parts, $lastDecision) {
    _SWUHmw008OfferPlay(intval($player), intval($parts[0] ?? 0), intval($parts[1] ?? 1));
};

// ── DEPLOYED: "While you control more units than an opponent, this unit gets +3/+0." ─────────────────
//
// ⚠ "YOU CONTROL" is SELF-ONLY in every format — GetUnitsInPlay, never SWUFriendlyUnitObjects. A
// teammate's unit is friendly but you do not control it (contrast HMW_105 Nute Gunray one card over,
// whose "friendly" DOES span the team). Pinned by Deployed_TeamSuns_YouControlIsSelfOnly.
//
// ⚠ "AN OPPONENT" is EXISTENTIAL, not universal: controlling more units than ONE opponent is enough,
// which only shows above two seats. There is nothing to prompt for — a continuous passive cannot ask a
// question — so this is a comparison over OpponentsOf, not a SWUQueueChooseOpponent.
//
// Recomputed on every power read, so it appears and disappears as the board changes; a value stamped
// once onto the object would pass every section here except Deployed_BuffRecomputesWhenTheOpponentCatchesUp.
if (!function_exists('_SWUHmw008HasUnitAdvantage')) {
    function _SWUHmw008HasUnitAdvantage(int $controller): bool {
        if ($controller <= 0) return false;
        $mine = count(GetUnitsInPlay($controller));      // includes the deployed leader unit itself
        foreach (OpponentsOf($controller) as $opp) {
            if ($mine > count(GetUnitsInPlay($opp))) return true;
        }
        return false;
    }
}
