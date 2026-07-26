<?php
// LAW_058
// Cost 2 - Honor-Bound Partisan - [Command,Aggression] - Power 2 - HP 2
// Text: When Played: Deal 1 damage to a base. / When Defeated: The next unit you play this phase costs 1 resource less.

// LAW_058 Honor-Bound Partisan — When Played: deal 1 to a base. When Defeated: next unit you play this
// phase costs 1 less.
$whenPlayedAbilities["LAW_058:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "Deal 1 damage to a base" — a base (no "enemy" qualifier) → the player chooses EITHER base.
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_1_damage_to_a_base", "LAW_058#0");
};

$whenDefeatedAbilities["LAW_058:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_LAW058_DISCOUNT_NEXT');
};

// LAW_150 Fulcrum grants the Rebel trait + a +2/+2 Rebel aura (both passive, in GameLogic).
// LAW_201 Thermal Detonator grants a When-Defeated (subcard scan + Law201Trigger, both in GameLogic).
// ── LAW Phase 7 — non-common Epic Action base continuations ───────────────────────────────────────// ── LAW Unit When-Defeated / On-Defense abilities (Phase 4) ───────────────────────────────────────
$customDQHandlers["LAW_058#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision))
    return;
  SWUDealDamageToBase(1, SWUMzOwner((string) $lastDecision, intval($player)));
};
