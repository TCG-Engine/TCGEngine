<?php
// HMW_054
// Cost 6 - Seismic Detonation - [Aggression,Cunning] - Event - Traits: Tactic - NON-unique
// Text: Choose an arena. At the start of the next regroup phase, deal 3 damage to each enemy unit in
//       that arena.

// ─── HMW_054 Seismic Detonation ──────────────────────────────────────────────
// ⚠ THIS EVENT DOES NOTHING WHEN IT IS PLAYED. The only thing that happens now is the arena choice;
// the damage lands a whole PHASE later. So the answer cannot be held anywhere but the GAMESTATE — the
// nearest built shapes are LAW_245 Salvaged Materials ("at the start of the next regroup phase, defeat
// it") and HMW_200 Rish Loo (control returns then), both of which park a global on the player and
// consume it in RegroupPhaseStart. This does the same, and the resolver below is called from there.
//
// ONE MARKER PER COPY, carrying its own arena (`SWU_HMW054|Ground` / `SWU_HMW054|Space`).
// AddGlobalEffects appends without de-duplicating, so two detonations set in the same phase are two
// markers — which is what makes two copies on the same arena deal 3 twice, and two copies on different
// arenas each hit their own. A single per-player "the arena P1 chose" value collapses both cases and
// passes every other section in the test file.
//
// "Choose an arena" is a mandatory PARAMETER, not a modal of effects and not a "you may": both options
// are always offered, and picking the arena with nothing in it is a legal (if bad) play. ⚠ Contrast
// LAW_178 Persecutor — "you MAY deal 3 damage to each unit in that arena" — whose printed permission
// earns a third `Pass` option. Copying its menu would silently make this card optional.

$whenPlayedAbilities["HMW_054:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1,
        tooltip: "Choose_an_arena_to_detonate_at_the_next_regroup");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HMW_054#0", 1);
};

$customDQHandlers["HMW_054#0"] = function ($player, $parts, $lastDecision) {
    // OPTIONCHOOSE answers are validated against the offered labels before a handler sees them, so
    // only 'Ground' or 'Space' can arrive here; the ternary mirrors HMW_240 Sandstorm's.
    $arena = ($lastDecision === 'Space') ? 'Space' : 'Ground';
    AddGlobalEffects(intval($player), 'SWU_HMW054|' . $arena);
};

// ── The delayed half. Called ONCE from RegroupPhaseStart. ────────────────────────────────────────
// Placed there AFTER the "for this phase" expiry and the shrink sweep, so the 3 lands against each
// unit's real HP rather than a buff that belongs to the phase which just ended. (Placing it earlier is
// not actually observable — the shrink sweep runs after expiry either way and would finish off anything
// a lapsed +HP buff had been propping up — but the later slot is the one that matches the CR, and it
// is where LAW_245's identical "at the start of the next regroup phase" one-shot already lives.)
function _SWUHmw054RegroupDetonations(): void {
    global $playerID;
    $saved = $playerID;
    foreach (GetLiveSeatsArray() as $seat) {
        $pending = [];
        foreach (GetGlobalEffects($seat) as $ge) {
            $flag = (string)($ge->CardID ?? '');
            if (strpos($flag, 'SWU_HMW054|') !== 0) continue;
            $pending[] = substr($flag, strlen('SWU_HMW054|'));
        }
        if (empty($pending)) continue;
        // Consume FIRST, and consume all of this seat's markers together. "At the start of the NEXT
        // regroup phase" is a one-shot; a permanent marker left behind is an infinite detonation that
        // every positive section in the file would still pass (FiresOnce_TheFollowingRegroupIsQuiet is
        // the only guard). Clearing up front also means a marker cannot be re-read by a later pass if
        // one of these damage events somehow re-enters this function.
        SWUClearGlobalEffectsByPrefix($seat, 'SWU_HMW054|');

        foreach ($pending as $arena) {
            $playerID = $seat;
            // "each ENEMY unit in that arena", resolved NOW — not a target list snapshotted when the
            // event was played. A unit that arrived after the detonation was set is in the arena and is
            // hit; a unit that has since come under the caster's control is friendly and is spared.
            // ⚠ their<Arena> is the team- AND seat-aware pool: it fans out across every live OPPONENT at
            // 3-4 seats and OpponentsOf already drops teammates in Team Suns, so there is no seat loop
            // to hand-roll here and no teammate to filter out by hand.
            $zone = ($arena === 'Space') ? 'theirSpaceArena' : 'theirGroundArena';
            $uids = [];
            foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $uids[] = intval($o->UniqueID ?? 0);
            }
            if (empty($uids)) continue;   // the chosen arena is empty — a legal, bad play; clean fizzle

            // One ability damaging several units is SIMULTANEOUS (official Rancor Keeper ruling,
            // 07/21/2026), so the co-victims share one defeat batch and a defeat observer that is itself
            // a victim still sees the others. Units are re-resolved by UID because a unit killed by its
            // own 3 compacts the arena underneath every later mzID.
            SWUSimulDefeatBegin();
            foreach ($uids as $uid) {
                $playerID = $seat;
                $mzNow = SWUFindMzByUID($uid);
                if ($mzNow !== null) SWUDealDamageToUnit($mzNow, 3, $seat);
            }
            SWUSimulDefeatEnd();
        }
    }
    $playerID = $saved;
}
