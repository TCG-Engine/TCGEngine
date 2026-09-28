<?php
// LOF_039
// Cost 8 - Darth Sidious - The Phantom Menace - [Vigilance,Villainy] - Power 6 - HP 8
// Text: Restore 2 (When this unit attacks, heal 2 damage from your base.) / When Played: You may use the Force. If you do, defeat each non-Sith unit with 3 or less remaining HP.

// LOF_039 Darth Sidious — When Played: may use the Force → defeat each non-Sith unit with 3 or less
// remaining HP. Mass automatic (no target): snapshot UIDs, then defeat by UID (index-shift safe).
$whenPlayedAbilities["LOF_039:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_defeat_each_non-Sith_unit_with_3_or_less_HP?", "LOF_039#0");
};

$customDQHandlers["LOF_039#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    // Drain loop: re-scan after each defeat (indices shift). No UID snapshot — avoids a UID collision
    // between a played unit and GIVEN test fixtures.
    // ⚠ The defeats ARE simultaneous and sequential is NOT equivalent, which an earlier comment here
    // claimed: a "when an enemy unit is defeated" observer that qualifies for this wipe itself (ASH_052
    // Chimaera at 3 or less remaining, SOR_002 Iden Versio, TS26_13 Darth Sidious) is drained out of play
    // before its co-victims' defeats are collected, and each defeat arrives as its own single-element
    // batch — so it stops counting partway through its own batch. The window freezes the pre-effect board
    // the whole wipe is judged against. See SWUSimulDefeatBegin (GameLogic.php).
    SWUSimulDefeatBegin();
    $changed = true;
    while ($changed) {
        $changed = false;
        foreach (SWUAllUnits() as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (HasTrait($o->CardID ?? '', 'Sith')) continue;
            if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 3) {
                // Pass the ACTING player (frame owner): SWUDefeatUnit sets $playerID = arg1 and resolves
                // $mz in that frame, so $mz ("their…"/"my…") must be read relative to $player, not the
                // unit's Controller (passing Controller flips the frame → defeats the wrong unit / loops).
                SWUDefeatUnit(intval($player), $mz);
                $changed = true;
                break;
            }
        }
    }
    SWUSimulDefeatEnd();
};
