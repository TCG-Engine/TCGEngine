<?php
// HMW_052
// Cost 2 - A'Koba, Restless Raider - [Aggression][Cunning] - Power 1 - HP 4 - Ground
// Text: Raid 1 (This unit gets +1/+0 while attacking.)
//       When Played: Give a unit +2/+0 for this phase.
//       (Official text, 2026-09-16 flip — the preview mock read +2/+2.)

// ─── HMW_052 A'Koba, Restless Raider ──────────────────────────────────────────
// Raid 1 needs NO code — the generator already derived 'HMW_052' => 1 into $Raid_Cards from the
// printed text, and the keyword has generic coverage under Tests/Cases/keywords/.
//
// The When Played half is one line of pool plus the universal buff handler, but the POOL is the
// whole card and it is the one thing easy to get wrong:
//
//   "Give a unit +2/+0 for this phase."
//
// No "another" and no "friendly". So the target set is EVERY unit on the table — both sides, both
// arenas, deployed leader units included, and A'Koba herself (her own When Played fires after she
// is placed, so she is already in play when the pool is built). That is exactly what
// _SWUAllUnitsOnly() returns, and it is also Twin-Suns-safe: its theirGroundArena/theirSpaceArena
// searches fan out across every live opponent at 3-4 seats rather than hardcoding one.
//
// ⚠ The obvious model for this card is IC27_079 Qui-Gon Jinn — same trigger, a +2 power buff, same
// phase duration — but Qui-Gon reads "another FRIENDLY unit" and so scans only my* arenas with a
// self-exclusion. Copying his pool would silently make A'Koba a narrower card than she is printed;
// the guards are WhenPlayed_CanBuffAnEnemyUnit and WhenPlayed_CanBuffItself_AutoResolvesWhenAlone.
//
// Mandatory, not optional: the clause prints no "may" and no "up to", so SWUQueueChooseTarget is
// right and the player is committed once a target exists. (Its single-target short-circuit through
// PASSPARAMETER is what makes the alone-on-the-board case resolve onto A'Koba with no prompt.)
// A mandatory choose is safe here — the documented auto-skip applies to a choose queued directly
// in an ON ATTACK closure, not a When Played one; IC27_079 has run this exact shape for a set.
//
// The buff rides the registered STAT_BUFF token 'HMW_052' (registry row in GameLogic), so it
// expires centrally with the phase and shows its source card in the Active Effects popup — no
// bespoke cleanup here.
$whenPlayedAbilities["HMW_052:0"] = function($player, $mzID = '') {
    $targets = _SWUAllUnitsOnly(intval($player));
    if (empty($targets)) return;   // unreachable in practice: A'Koba is herself a legal target
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_a_unit_+2/+0_this_phase", "APPLY_PHASE_BUFF|2|0|HMW_052");
};
