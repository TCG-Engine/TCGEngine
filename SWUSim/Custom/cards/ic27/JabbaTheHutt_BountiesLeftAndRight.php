<?php
// IC27_060
// Cost 3 - Jabba the Hutt - Bounties Left and Right - [Command,Villainy] - Unit (Ground) 0/8
//   Traits: Underworld, Hutt - unique
// Text: Action [Exhaust]: Choose an enemy unit. For this phase, while that unit is defending, the attacker gets +4/+0.
//
// A phase MARKER on the chosen unit (registry row 'IC27_060' in GameLogic.php), read by SWUCombatDamage beside TWI_072
// I Have the High Ground: whoever attacks a marked unit gets +4/+0 per marker.
//   • A lasting effect on THAT unit: it does not depend on Jabba staying in play or keeping his abilities, it follows the
//     unit through a control change (SWUTakeControlOfUnit keeps TurnEffects), and it applies to ANY attacker of it.
//   • Each use is its own effect, so each gets its own token ("IC27_060-<n>") — AddTurnEffect de-dupes identical
//     strings, and two Jabbas on one Team Suns team must give +8/+0.
//   • "an enemy unit" — team-relative, both arenas (SWUAllUnits('their')); mandatory, one target auto-resolves.
//   • No availability gate (CR 6.4.587.c): with no enemy unit the Action still exhausts Jabba and resolves to nothing.
// Tests: SWUSim/Tests/Cases/ic27/JabbaTheHutt_BountiesLeftAndRight.md

$unitAbilities["IC27_060"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits('their');
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Choose_an_enemy_unit_(attackers_get_+4/+0_against_it_this_phase)", "IC27_060#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["IC27_060#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $target = GetZoneObject($lastDecision);
    if (SWUObjGone($target)) return;
    AddTurnEffect($lastDecision, 'IC27_060-' . (_SWUIc27060MarkerCount($target) + 1));
};

// Number of Jabba markers on a unit (each is a separate +4/+0 for its attackers this phase).
function _SWUIc27060MarkerCount($obj): int {
    if ($obj === null) return 0;
    $n = 0;
    foreach (($obj->TurnEffects ?? []) as $te) {
        if (preg_match('/^IC27_060(-\d+)?$/', (string)$te)) $n++;
    }
    return $n;
}
