<?php
// HMW_171
// Cost 2 - Trap Field - [Aggression][Heroism] - Upgrade - Trait: Fortification
// Text: Fortify (Attach to this base, not a unit.)
//       When a non-leader ground unit enters play (including token units): You may defeat this upgrade.
//       If you do, deal 3 damage to that unit.
//
// A base-hosted REACTIVE entry observer (the first of its kind). The collection + trigger owner wiring
// lives beside CollectEntryTriggers in GameLogic.php (SWUCollectTrapFieldReactions / Hmw171TrapFieldReaction),
// hooked at BOTH the played-unit funnel (CollectEntryTriggers) and the token funnel (_SWUCreateOneToken) so
// "including token units" holds. The reaction is unrestricted — either player's Trap Field reacts to friendly
// OR enemy ground units — and is owned by the base owner (possibly the non-active player: a cross-player
// reaction that drains like SHD_172). Only the continuation lives here.
//
// HMW_171 has NO generated ability stub — its trigger is worded "When a NON-LEADER GROUND UNIT enters play",
// which the generator's WhenPlayed detection (matches "When Played:" / "When Deployed:" only) correctly
// ignores. This reactive observer is wired by hand and does not depend on the stub system.
$customDQHandlers["HMW_171#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // "you may" — declined leaves the upgrade attached

    $enteredUID = intval($parts[0] ?? 0);
    $count      = max(1, intval($parts[1] ?? 1));

    $zone = GetBase(intval($player)); $base = $zone[0] ?? null;
    if ($base === null) return;
    $idx = SWUFindUpgradeIndex($base, 'HMW_171');
    if ($idx < 0) return;                             // no Trap Field left (shouldn't happen — guarded at offer)

    // "You may defeat this upgrade. If you do, deal 3 damage to that unit." HMW_060 Rampart MAY replace this
    // defeat (default $skipReplacement=false). Per the SWU CR replacement rules, when a replacement replaces
    // the text before "If you do" the player is STILL considered to have resolved it — so the 3 is dealt
    // regardless (below), and Rampart, if chosen at action end, saves the Trap Field.
    SWUDefeatUpgrade(intval($player), 'myBase-0', $idx);
    $enteredMz = SWUFindMzByUID($enteredUID);
    if ($enteredMz !== null && !SWUObjGone(GetZoneObject($enteredMz))) {
        SWUDealDamageToUnit($enteredMz, 3, intval($player));
    }

    // Multi-copy edge (a base with 2+ Trap Fields): offer the next one for the same entering unit.
    if ($count > 1 && SWUFindUpgradeIndex($base, 'HMW_171') >= 0) {
        Hmw171TrapFieldReaction(intval($player), $enteredUID, $count - 1);
    }
};
