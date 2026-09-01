<?php
// HMW_078
// Cost 5 - Qui-Gon Jinn - We'll Handle This - [Vigilance,Heroism] - Unit - Power 2 - HP 5
// Traits: Force, Jedi, Republic - UNIQUE
// Text: Grit
//       When Played: You may defeat a unit that attacked your base this phase. If it's a leader unit,
//       defeat this unit.
//
// Grit is keyword-only and auto-wired from the generated registry — nothing to do for it here.
//
// THE POOL IS "A UNIT", AND THE MISSING WORDS MATTER. Both released cards carrying this exact clause —
// SHD_088 Ephant Mon and SHD_106 Rule with Respect — read "enemy NON-LEADER unit". This one says
// neither, so the pool spans BOTH sides (SWUAllUnits(null)) and INCLUDES deployed leader units. The
// leader case is not an accident of the wording; it is what the second sentence is for.
// No self-exclusion is added: the text does not say "another", and Qui-Gon has just entered play so he
// can never carry the marker anyway — an exclusion here would be inventing a restriction.
//
// "ATTACKED YOUR BASE THIS PHASE" reads SWU_MYBASE_ATTACKEDBY_{uid}, which is stamped in CombatLogic on
// the ATTACKED BASE'S OWNER (see the note there). That is why this reads GlobalEffectCount($player, …)
// rather than the attacking unit's controller: "your base" is the namespace, so the answer is correct
// above two seats and survives a change of control of the attacker.
//
// "You may" -> MZMAYCHOOSE, which does not auto-resolve even on a single legal target, so the player
// can always decline. With no marked unit at all, nothing is offered.
$whenPlayedAbilities["HMW_078:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (SWUAllUnits(null) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $uid = intval($o->UniqueID ?? 0);
        if ($uid > 0 && GlobalEffectCount(intval($player), 'SWU_MYBASE_ATTACKEDBY_' . $uid) > 0) {
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;   // nothing attacked your base: no prompt at all
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_a_unit_that_attacked_your_base_this_phase",
        "Defeat_a_unit_that_attacked_your_base_this_phase",
        "HMW_078#0|" . $selfUID);
};

$customDQHandlers["HMW_078#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // "you may" — declining defeats nothing, self included
    global $playerID; $playerID = intval($player);
    $target = GetZoneObject($lastDecision);
    if (SWUObjGone($target)) return;
    // Read the LIVE object, not the printed CardType: a unit made a leader unit by The Darksaber or by
    // a deployed Pilot leader is a leader unit for this rider, and its printed type is still "Unit".
    // Captured BEFORE the defeat, because afterwards there is nothing left to ask.
    $wasLeaderUnit = IsLeaderUnit($target);
    SWUDefeatUnit(intval($player), $lastDecision);
    if (!$wasLeaderUnit) return;
    // The defeat compacted the arena, so every positional mzID taken before it — including Qui-Gon's
    // own — is stale. Re-resolve him by UniqueID, which is carried across the decision in the Param.
    // $parts holds the args AFTER the handler name, so the UID is index 0, not 1 (same shape as
    // SHD_045 Bossk). Reading $parts[1] returns null, SWUFindMzByUID(0) returns null, and the rider
    // then silently does nothing — which is exactly how this failed the first time.
    $selfMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($selfMz === null) return;
    SWUDefeatUnit(intval($player), $selfMz);
};
