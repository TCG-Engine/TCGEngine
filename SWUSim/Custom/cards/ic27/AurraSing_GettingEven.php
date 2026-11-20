<?php
// IC27_107
// Cost 6 - Aurra Sing - Getting Even - [Aggression,Villainy] - Unit (Ground) 1/7 (unique)
//   Traits: Underworld, Bounty Hunter
// Text: Hidden. Action [Exhaust]: Deal 4 damage to a unit.
//
// Hidden is printed (auto-wired). The Action uses the default 'exhaust' cost kind, so SWUUnitAction pays the
// exhaust and an exhausted Aurra cannot use it. "a unit" has no "another"/"friendly"/"enemy": the pool is
// every unit in play — Aurra herself, leader units, both arenas, far seats. The pick is mandatory, so with no
// other unit in play it auto-resolves onto Aurra. She is the damage SOURCE (she deals it), threaded through
// DEAL_UNIT_DAMAGE's encoded-source slot so source-aware rules see her (ASH_196 Gorian Shard's Corsair makes
// an Underworld card's damage unpreventable).
// Tests: SWUSim/Tests/Cases/ic27/AurraSing_GettingEven.md
$unitAbilities["IC27_107"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWUCollectUnitTargets(intval($player));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_4_damage_to_a_unit", "DEAL_UNIT_DAMAGE|4|" . _SWUEncodeDamageSource($mzID));
    SWUQueueAfterAction($player);
};
