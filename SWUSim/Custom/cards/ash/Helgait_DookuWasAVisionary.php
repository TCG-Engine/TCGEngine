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
    // The in-memory snapshot is gone after a request boundary (Thrawn's reuse YESNO is answered in a
    // later request), so fall back to the PERSISTED twin before the live re-resolve below. Without
    // this the reuse read the printed 6 instead of a buffed 10.
    if ($power <= 0) {
        $power = intval(GetSWUVar('SWU_ASH195_PWR_MZ_' . str_replace('-', '_', $mzID), '0'));
    }
    if ($power <= 0) {
        // No defeat snapshot exists — either JTL_039 Chimaera activated this ability directly on a
        // LIVING Helgait (no real defeat occurred, so CollectWhenDefeatedTriggers never ran), or a
        // Thrawn/Shadow-Caster reuse of that same live-activation. Both happen under the SAME player's
        // own frame as Helgait's controller (Chimaera/Thrawn/Helgait are all controlled by the same
        // player), so — unlike the cross-player defeat case above — $mzID is safe to re-resolve live
        // here: read Helgait's CURRENT power (re-evaluating any Advantage/buffs gained since the last
        // use), not the stale printed base.
        // ⚠ THE OBJECT AT $mzID MUST STILL BE A HELGAIT. This branch re-resolves a raw mzID, and an
        // mzID is a SLOT, not an identity: once Helgait leaves play the arena COMPACTS and the next
        // unit slides into that index. Without this check the fallback reads THAT unit's power and
        // distributes it — the reported symptom, "the second trigger gets the next MZ index after
        // Helgait dies". The snapshot above is the correct source; this is only for the live-Helgait
        // activation path (Chimaera/Thrawn on an UNDEFEATED Helgait), where the slot really does still
        // hold Helgait. Anything else falls to the printed power rather than a stranger's.
        $liveObj = GetZoneObject($mzID);
        $liveIsHelgait = ($liveObj !== null && empty($liveObj->removed)
                          && ($liveObj->CardID ?? '') === 'ASH_195');
        $power = $liveIsHelgait
            ? intval(ObjectCurrentPower($liveObj))
            : intval(CardPower('ASH_195'));   // slot no longer holds Helgait — never read a stranger
    }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if ($power <= 0 || empty($targets)) return;
    // ⚠ "You may … EQUAL TO this unit's power" — NOT "up to". Two different offers: "You may" makes the
    // ABILITY optional (declining outright is legal), while "equal to" fixes the AMOUNT once you engage.
    // ALLORNONE is exactly that pair; UPTO (what this used to pass) wrongly let a player place 2 of 40
    // Advantage and pocket the rest. The prompt must not say "up to" either — the wording IS the offer.
    SWUQueueDistributeAdvantage(intval($player), $power, $targets, 'ALLORNONE',
        "Distribute_all_{$power}_Advantage_among_friendly_units_or_none");
};
