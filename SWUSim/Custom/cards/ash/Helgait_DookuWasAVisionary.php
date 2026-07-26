<?php
// ASH_195
// Cost 5 - Helgait - Dooku Was a Visionary - [Cunning,Villainy] - Power 6 - HP 4
// Text: When Defeated: You may distribute a number of Advantage tokens equal to this unit's power among friendly units (divided as you choose).

// ASH_195 Helgait — When Defeated: you may distribute a number of Advantage tokens equal to this unit's
// power among friendly units (divided as you choose).
$whenDefeatedAbilities["ASH_195:0"] = function($player, $mzID) {
    global $playerID, $gAsh195DefeatSnapshot; $playerID = intval($player);
    // Read Helgait's power from the defeat-time snapshot taken in CollectWhenDefeatedTriggers. The $mzID we
    // receive is frame-relative to the DEFEATING player (e.g. "theirGroundArena-0" when an opponent kills
    // Helgait), but this closure runs under Helgait's controller's frame — re-resolving it here would point
    // at a DIFFERENT unit (the opponent's unit in that slot). The snapshot is keyed by the same mzID string.
    // Left in place (not unset) after reading: a dead Helgait's power is frozen, so a Thrawn/Shadow-Caster
    // reuse of the SAME defeat instance must keep using this correctly-captured value, not silently fall
    // back to the printed base below.
    $power = intval($gAsh195DefeatSnapshot[$mzID] ?? 0);
    if ($power <= 0) {
        // No defeat snapshot exists — either JTL_039 Chimaera activated this ability directly on a
        // LIVING Helgait (no real defeat occurred, so CollectWhenDefeatedTriggers never ran), or a
        // Thrawn/Shadow-Caster reuse of that same live-activation. Both happen under the SAME player's
        // own frame as Helgait's controller (Chimaera/Thrawn/Helgait are all controlled by the same
        // player), so — unlike the cross-player defeat case above — $mzID is safe to re-resolve live
        // here: read Helgait's CURRENT power (re-evaluating any Advantage/buffs gained since the last
        // use), not the stale printed base.
        $liveObj = GetZoneObject($mzID);
        $power = ($liveObj !== null && empty($liveObj->removed))
            ? intval(ObjectCurrentPower($liveObj))
            : intval(CardPower('ASH_195'));   // truly unreachable — last-resort fallback
    }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if ($power <= 0 || empty($targets)) return;
    SWUQueueDistributeAdvantage(intval($player), $power, $targets, true, "Distribute_up_to_{$power}_Advantage_among_friendly_units");
};
