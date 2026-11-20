<?php
// IC27_002
// Cost 5 - Rey - In Balance - [Vigilance,Heroism] - Leader / Unit (Ground) 3/6
//   Traits: Force, Resistance
// Text: Action [1 resource, Exhaust]: Give an Experience token to a damaged unit.
// DeployText: On Attack: You may give an Experience token to a damaged unit.
// Epic Action: If you control 5 or more resources, deploy this leader.
//
// PREVIEW card (no official rulings). Same two-sided shape as SHD_004 Rey (More Than a Scavenger) with a different target
// condition. "A damaged unit" names no side and no "another": any unit in play with damage on it, either side, either arena —
// Rey herself included when she is the attacker. Epic deploy needs no wiring: the generic threshold IS the printed cost (5).
// FRONT with no damaged unit: still usable — CR 637.3 ("as long as an action ability has a cost that changes the game state, a
// player can pay that cost and resolve the ability even if the ability's effect would not change the game state").

// Front-side [1 resource]: a cost REQUIREMENT, checked by SWULeaderActionAffordable before Rey exhausts and paid centrally
// (Credits / SEC_122 Droids included). ($leaderActionResourceCosts is initialized in LeaderAbilities.php.)
$leaderActionResourceCosts["IC27_002"] = 1;

// A damaged unit: damage on it, anywhere on the table. SWUAllUnits() is the WHOLE table (team + every opponent seat), so a far
// Twin Suns seat and a Team Suns teammate are both in the pool.
function Ic27002IsDamaged($o): bool { return !SWUObjGone($o) && intval($o->Damage ?? 0) > 0; }

// ── FRONT: Action [1 resource, Exhaust] — give an Experience token to a damaged unit ─────────────
// The cost is already paid and Rey exhausted when this runs. The effect is mandatory (no "may").
$leaderAbilities["IC27_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = array_values(array_filter(SWUAllUnits(), fn($mz) => Ic27002IsDamaged(GetZoneObject($mz))));
    if (empty($targets)) { SWUAfterAction($player); return; }   // CR 637.3: cost paid, effect changes nothing
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_damaged_unit", "GIVE_EXPERIENCE|1");
    SWUQueueAfterAction($player);   // the leader Action owns its After Action
};

// ── DEPLOYED: On Attack — you MAY give an Experience token to a damaged unit ─────────────────────
// No 'excludeSelf': a damaged Rey may take it herself. The defender has not taken combat damage yet when this resolves.
$onAttackAbilities["IC27_002:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'may' => true,
        'extraFilter'  => fn($o) => Ic27002IsDamaged($o),
        'question'     => "Give_an_Experience_token_to_a_damaged_unit?",
        'prompt'       => "Choose_a_damaged_unit",
    ]);
};
