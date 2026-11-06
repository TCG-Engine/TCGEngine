<?php
// IC27_005
// Cost 5 - Darth Maul - Mechanism of Menace - [Aggression,Villainy] - Leader / Unit (Ground) 4/5
//   Traits: Force, Sith
// Text: Action [1 resource, Exhaust]: Deal 1 damage to a unit.
// DeployText: On Attack: You may deal 1 damage to a unit.
// Epic Action: If you control 5 or more resources, deploy this leader.
//
// PREVIEW card (no official rulings). Same two-sided shape as IC27_002 Rey (In Balance); the deployed sentence is SHD_012
// Bo-Katan's first clause word for word. "A unit" names no side and no "another": any unit in play, either side, either arena,
// any seat — Maul himself included when he is the attacker (TWI_135 Darth Maul's ruling: "You can use Maul's unit ability to
// damage himself"). Epic deploy needs no wiring: the generic threshold IS the printed cost (5).
// FRONT with no unit in play: still usable — CR 637.3 (the cost changes the game state), resolving to nothing.
// The damage is left source-less, like SHD_012 / IC27_002: no source reader in the card pool can tell Maul apart here
// (ASH_196 reads Underworld, LOF_108 Creature, SEC_050 only its own abilities).

// Front-side [1 resource]: a cost REQUIREMENT, checked by SWULeaderActionAffordable before Maul exhausts and paid centrally
// (Credits / SEC_122 Droids included). ($leaderActionResourceCosts is initialized in LeaderAbilities.php.)
$leaderActionResourceCosts["IC27_005"] = 1;

// ── FRONT: Action [1 resource, Exhaust] — deal 1 damage to a unit ────────────────────────────────
// The cost is already paid and Maul exhausted when this runs. The effect is mandatory (no "may"), so a lone unit auto-resolves.
// SWUAllUnits() is the WHOLE table (team + every opponent seat): a far Twin Suns seat and a Team Suns teammate are both in it.
$leaderAbilities["IC27_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    // No unit in play: SWUQueueChooseTarget no-ops on an empty pool and the After Action below still closes the action —
    // CR 637.3, the cost was paid and the effect changes nothing.
    SWUQueueChooseTarget($player, SWUAllUnits(), "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);   // the leader Action owns its After Action
};

// ── DEPLOYED: On Attack — you MAY deal 1 damage to a unit ────────────────────────────────────────
// One MZMAYCHOOSE (pick a unit, or decline with '-'); an optional pick never auto-resolves. No 'excludeSelf': Maul may ping
// himself. It resolves before combat damage, so pinging a 1-HP defender defeats it before it can deal its counter-damage.
$onAttackAbilities["IC27_005:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'may' => true,
        'question'     => "Deal_1_damage_to_a_unit?",
        'prompt'       => "Choose_a_unit_to_deal_1_damage",
    ]);
};
