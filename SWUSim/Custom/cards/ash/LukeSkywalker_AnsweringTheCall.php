<?php
// ASH_112
// Cost 6 - Luke Skywalker - Answering the Call - [Command,Heroism] - Power 5 - HP 5
// Text: Restore 1 / When Played: If you control at least 4 units, deal 3 damage to each enemy unit.

// ASH_112 Luke Skywalker — Restore 1 (keyword) + When Played: if you control at least 4 units, deal 3
// damage to each enemy unit.
$whenPlayedAbilities["ASH_112:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $friendly++; }
    if ($friendly < 4) return;
    // "Deal 3 damage to EACH enemy unit" is ONE simultaneous damage event, so it goes through
    // SWUDealSplitDamage — the shared simultaneous funnel — not a per-unit SWUDealDamageToUnit loop.
    //
    // ⚠ The loop this replaces defeated units INLINE as it walked the arena, which made the effect
    // order-dependent and froze game 1157594. P1 held SEC_101 Queen Amidala (Naboo, Official) behind two
    // SEC_T01 Spy tokens (Official) — the only units sharing a trait with her, and so the only legal cost
    // for her "you may defeat another friendly unit that shares a trait with this unit; if you do, prevent
    // that damage" replacement. The Spies sat EARLIER in the arena, so they were dealt 3 and defeated
    // before Amidala's own damage was processed; her replacement then had nothing to spend and the
    // resulting prompt was left unanswerable. With Amidala FIRST in the arena the very same board worked,
    // which is what made this look intermittent.
    //
    // Owner ruling (2026-09-23): all of this damage is dealt simultaneously, and defeats are not resolved
    // until after the "when this unit is damaged" reactions — so a Spy that has itself taken 3 is still on
    // the board and still a legal cost. SWUDealSplitDamage implements exactly that: it offers each
    // interactive replacement FIRST, then applies every surviving share together, then sweeps defeats in
    // one pass, then fires the damaged-observers. Targets are snapshotted by UniqueID inside it, so no
    // mzID can go stale as the board shifts.
    //
    // srcTok is left EMPTY deliberately: SWUDealDamageToUnit was called here with no source, so passing
    // one would additionally change which prevention gates see a dealer (SEC_050 Vigil's "by another
    // card", LOF_108 Malakili, ASH_196). That is a real but SEPARATE gap — this change is the ordering fix
    // only, and threading the source belongs with the sweep of the other "damage to each" cards.
    $assign = [];
    foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $assign[] = $mz . ':3';
        }
    }
    if (!empty($assign)) SWUDealSplitDamage(intval($player), implode(',', $assign));
};
