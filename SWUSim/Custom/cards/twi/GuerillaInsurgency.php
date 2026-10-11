<?php
// TWI_177
// Guerilla Insurgency
// Text: Each player defeats a resource they control and discards 2 cards from their hand. Deal 4 damage to each ground unit.

// When Played (event) — migrated from OnPlayEvent. ($cardID hardcoded for the caster self-exclude.)
$whenPlayedAbilities["TWI_177:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    // 1. EACH PLAYER defeats a resource THEY control — so each player picks their OWN. Every LIVE seat,
    //    caster included — was the literal [caster, OtherPlayer(caster)], i.e. two seats.
    //    ⚠ The old comment here read "fungible → auto-pick the first". Resources are NOT fungible (USER
    //    RULING 2026-08-26) — which one dies is information the player can act on — and "a resource THEY
    //    control" names the owner as the decider, not the caster. Both halves were wrong.
    foreach (GetLiveSeatsArray() as $p) {
        $playerID = $p;
        SWUQueueResourceDefeatPick($p, ZoneSearch("myResources", null), 1, "Choose_a_resource_to_defeat_(Guerilla_Insurgency)");
    }
    // 2. Each OPPONENT discards 2 via the helper, one call per seat; the caster's own discard is handled
    //    inline below because it must exclude the just-played event.
    // "…and discards 2 cards from their hand" — hidden information, CR v9.0 7.1.a: every player chooses
    // independently and all the discards happen together (SWUEachSeatDiscardsSimultaneously). The event itself
    // still sits in the caster's hand, so one copy is excluded from the caster's pool.
    SWUEachSeatDiscardsSimultaneously(intval($player), array_merge([intval($player)], OpponentsOf(intval($player))),
        'discard', 2, "Choose_2_cards_to_discard", 'TWI_177');
    // 3. Deal 4 to each ground unit (both players; UID-snapshot).
    $playerID = intval($player);
    $uids = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 4, intval($player)); }
};
