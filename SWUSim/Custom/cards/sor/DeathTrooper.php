<?php
// SOR_033  |  Reprints: SEC_030, SHD_030
// Cost 3 - Death Trooper - [Vigilance,Villainy] - Power 3 - HP 3
// Text: When Played: Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground unit.

// ONE implementation, aliased onto every printing's registration key. Ability dispatch is
// printing-EXACT (`$whenPlayedAbilities["{$cardID}:{$w}"]`, and GeneratedAbilityStubs gates the trigger
// per printing), so each reprint needs its own key — but it does NOT need its own code. Aliasing the
// key is the pattern; three parallel copies is how they drift.
//
// CONVENTION: order the alias tail NEWEST printing at the top down to the EARLIEST at the bottom, and
// let the earliest carry the `= function`. The earliest printing is the canonical one (it is what
// CardIDOverride folds to, what the stats corpus aggregates under, and which file this lives in), so
// putting it on the line that owns the body makes it unmistakable at a glance.
//
// They had already drifted: the SEC and SHD copies hand-rolled the friendly half with
// SWUQueueChooseTarget plus a manual SWUDealDamageToUnit in their continuations, and SEC carried an
// extra "no friendly ground" early-exit branch. SWUOfferUnitTarget — the canonical collect→choose→apply
// helper — already no-ops on an empty pool and applies the damage through DEAL_UNIT_DAMAGE, so all of
// that was reimplementing the helper. Both halves now go through it.
//
// Both damage halves are MANDATORY (no "you may"), and Death Trooper itself is a legal friendly target
// because he is in play by the time When Played resolves.
$whenPlayedAbilities["SEC_030:0"] =
$whenPlayedAbilities["SHD_030:0"] =
$whenPlayedAbilities["SOR_033:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'friendly', 'arena' => 'Ground',
        'prompt' => "Deal_2_to_a_friendly_ground_unit",
    ]);
    // The enemy half is queued from a CUSTOM continuation rather than inline so it resolves AFTER the
    // friendly pick, and so it still fires when the friendly half has no candidates at all.
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_033#0", 1);
};

// Shared continuation: every printing queues this one key, so there is deliberately no SEC_030#0 /
// SHD_030#0 twin to keep in sync.
$customDQHandlers["SOR_033#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_2_to_an_enemy_ground_unit", 'block' => 0,
    ]);
};
