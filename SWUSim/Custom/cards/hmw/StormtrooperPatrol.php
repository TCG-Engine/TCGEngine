<?php
// HMW_107
// Cost 3 - Stormtrooper Patrol - [Command][Villainy] - Unit (Ground) 2/4 - Traits: Imperial, Trooper
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
//       While you control another unit that costs 3 or more, this unit gets +2/+0.
//
// The Sentinel half needs NO code — HMW_107 is auto-registered in $Sentinel_Cards by the keyword
// generator (derived from the card text), and Sentinel already has generic behavioural coverage.
//
// The conditional buff is a CONTINUOUS self-passive, so it lives in ObjectCurrentPower (GameLogic.php)
// next to the TWI_163 / TWI_130 "while you control another <X> unit" family rather than as a
// TurnEffect — it must switch off the instant the qualifying ally leaves play, with no cleanup hook.
// Only the +2 POWER half exists; ObjectCurrentHP is deliberately untouched.
//
// Three details the printed text pins down, each with its own guard section in the test file:
//   • "costs 3 or more" reads the PRINTED cost (CardCost), never a current/effective cost — so a
//     0-cost token unit never qualifies no matter what buffs it carries.
//   • "another" is a per-OBJECT exclusion by UniqueID, not a per-CardID one: two copies of Stormtrooper
//     Patrol each satisfy the OTHER's condition and both get +2/+0.
//   • "you control" scopes to the controller's own arenas, so an enemy 3+ cost unit grants nothing.
//     A DEPLOYED leader unit sits in its controller's arena and has a printed cost well above 3, so it
//     does qualify — GetUnitsInPlay reads the arenas directly and therefore includes it (a printed
//     CardType 'Unit' filter would have wrongly excluded it, since a leader unit's CardType is 'Leader').
if (!function_exists('_SWUHmw107HasCostlyAlly')) {
    function _SWUHmw107HasCostlyAlly(int $controller, $excludeUID): bool {
        foreach (GetUnitsInPlay($controller) as $u) {
            if ($excludeUID !== null && intval($u->UniqueID ?? -1) === intval($excludeUID)) continue;
            if (intval(CardCost($u->CardID ?? '')) >= 3) return true;
        }
        return false;
    }
}
