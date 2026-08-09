<?php
// TS26_34
// Cost 6 - Fives - I Have Proof! - [Cunning,Vigilance,Heroism] - Power 6 - HP 6
// Text: Sentinel / You may have this unit enter play with the "When Played" abilities of another unit in play.

// TS26_34 Fives — Sentinel. When Played: you may have this unit enter play with the "When Played"
// abilities of another unit in play (re-resolve the chosen unit's When Played, with Fives as the source).
$whenPlayedAbilities["TS26_34:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $tg = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;         // another unit
        // "When played using Smuggle" IS a When Played ability — it just carries an extra condition on
        // HOW the card was played. It lives in a separate registry/stub, so a plain HasWhenPlayedAbility
        // check silently excluded SHD_148 Cassian Andor and friends from the copy pool entirely.
        if (!HasWhenPlayedAbility($o->CardID ?? '')
            && !HasWhenPlayedUsingSmuggleAbility($o->CardID ?? '')) continue;
        // ⚠ HasWhenPlayedAbility is a PRINTED-CARD check; it cannot see that this particular object has
        // been blanked. A unit that "loses all abilities" (JTL_018 Kazuda's action, SOR_138, Brain
        // Invaders, …) has no When Played to copy, so it must drop out of the pool — otherwise Fives
        // offers it and copying resolves nothing. Live object state, not card identity.
        if (LostAbilities($o)) continue;
        $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Copy_another_unit's_When_Played?", "Choose_a_unit_to_copy", "TS26_34#0|" . $selfUID);
};

$customDQHandlers["TS26_34#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) return;
    $chosenCID = $chosen->CardID ?? '';
    $selfUID = intval($parts[0] ?? 0);
    $fivesMz = SWUFindMzByUID($selfUID);
    if ($fivesMz === null) return;
    OnWhenPlayed(intval($player), $chosenCID, $fivesMz);   // resolve the copied When Played as Fives
    // A copied "When played using Smuggle" ability keeps its own condition: it only fires if FIVES was
    // himself played via Smuggle. _SWUSmuggleFireEntry stamps SWU_SMUGGLED_{uid} on the entering unit,
    // which survives the decision round-trip (a transient var would not — the copy offer is answered
    // after the entry triggers were merely bagged).
    global $whenPlayedUsingSmuggleAbilities;
    if (isset($whenPlayedUsingSmuggleAbilities["{$chosenCID}:0"])
            && $selfUID > 0 && GlobalEffectCount(intval($player), 'SWU_SMUGGLED_' . $selfUID) > 0) {
        $whenPlayedUsingSmuggleAbilities["{$chosenCID}:0"](intval($player), $fivesMz);
    }
};
