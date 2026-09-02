<?php
// HMW_046
// Cost 4 - Krrsantan, Santo - [Command][Aggression] - Unit (Ground) 4/4 - unique
// Traits: Underworld, Wookiee
// Text: When Played: You may deal damage equal to the number of resources you control minus 3 to a
//       ground unit.
//
// "THE NUMBER OF RESOURCES YOU CONTROL" IS EVERY RESOURCE IN THE ZONE, READY OR EXHAUSTED. Control is
// not readiness, and this matters on every single play of the card: paying Krrsantan's own cost of 4
// exhausts four of them, so a ready-only reading is off by (at least) four and usually computes a
// negative amount. SWUResourceCount($p) with no $readyOnly is the total, and it already skips Credit
// tokens (CR 3.13 — a Credit is a token sitting in the resource zone, not a resource), which is what
// makes an amount of zero reachable at all: pay part of the cost with a Credit and the resource count
// stays put while the cost is met.
//
// AMOUNT <= 0 RAISES NO PROMPT. Dealing zero damage is no effect — nothing is damaged, no damage
// observer fires — so an offer there could only ever waste the player's click. That is the LAW_257
// fizzle-only-optional family plus the house no-op-prompt rule, and the gate is what the
// Boundary_ThreeResourcesAndACredit_AmountZero_NoPrompt section pins.
//
// "A GROUND UNIT" NAMES NO CONTROLLER AND NO OTHER RESTRICTION: friendly and enemy alike, leader units
// included (no "non-leader"), and Krrsantan HIMSELF — he is in the ground arena by the time his own
// When Played resolves and the text does not say "another". SWUAllUnits(null, 'Ground') is exactly
// that pool, and it spans team + every opponent at any seat count. Space units are excluded.
//
// The pool therefore always holds at least one member (Krrsantan), so there is no no-legal-target
// branch to write — the only way this clause declines to offer is the amount gate above.
//
// The amount rides the CUSTOM decision's own Param (DEAL_UNIT_DAMAGE|N), which is serialised with the
// gamestate; production answers this offer in a fresh process, and an amount held in a global would
// read back as zero.
$whenPlayedAbilities["HMW_046:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $me       = intval($player);

    $amount = SWUResourceCount($me) - 3;
    if ($amount <= 0) return;

    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;

    SWUQueueMayChooseTarget($me, $targets,
        "Deal_{$amount}_damage_to_a_ground_unit?",
        "Choose_a_ground_unit_to_deal_{$amount}_damage_to",
        "DEAL_UNIT_DAMAGE|{$amount}");
};
