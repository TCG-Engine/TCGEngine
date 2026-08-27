<?php
// SOR_187
// Cost 7 - I Had No Choice - [Cunning,Villainy]
// Text: Choose up to 2 non-leader units. An opponent chooses 1 of those units. Return that unit to its owner's hand and put the other on the bottom of its owner's deck.

// SOR_187 I Had No Choice — the caster chose up to 2 non-leader units ($lastDecision, &-delimited).
// 0 → no-op; 1 → return it to its owner's hand; 2 → the opponent chooses which is saved (the other is
// buried on the bottom of its owner's deck, resolved in SOR_187#1). Targets carried by UniqueID.
$customDQHandlers["SOR_187#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $playerID = $caster;
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if (count($uids) >= 2) break;
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
        $uids[] = intval($o->UniqueID);
    }
    $uids = array_values(array_unique($uids));
    if (count($uids) === 0) return;
    if (count($uids) === 1) {                       // forced single choice → return to owner's hand
        $mz = SWUFindMzByUID($uids[0]);
        if ($mz !== null) SWUBounceUnit($caster, $mz);
        return;
    }
    // 2 units: the opponent chooses which returns to hand. Resolve the two under the opponent's
    // perspective and queue an MZCHOOSE answered by them; leave $playerID = opponent for MZCountChoices.
    // "AN OPPONENT chooses 1 of those units" — the caster picks WHICH opponent decides.
    // OFFICIAL RULING (03/01/2024): "If there are multiple opponents, the controlling player chooses which
    // one will be 'an opponent.'"
    // ⚠ NO $eligible filter: the chosen opponent is only being asked to pick between two units that are
    // already on the table — nothing about their own board can make them unable to choose (taxonomy
    // shape 3, the same as LOF_065 Watto).
    // ⚠ Handler named #2 — #0 and #1 are already taken in this file.
    SWUQueueChooseOpponent($caster, "SOR_187#2|{$caster}|{$uids[0]}|{$uids[1]}",
        "Choose_an_opponent_to_decide_which_unit_is_saved");
};

$customDQHandlers["SOR_187#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $uidA   = intval($parts[1] ?? 0);
    $uidB   = intval($parts[2] ?? 0);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $playerID = $opp;
    $mzA = SWUFindMzByUID($uidA);
    $mzB = SWUFindMzByUID($uidB);
    $uids = [$uidA, $uidB];
    if ($mzA === null || $mzB === null) {           // one vanished → bounce whatever remains, no choice
        $playerID = $caster;
        foreach ($uids as $u) { $m = SWUFindMzByUID($u); if ($m !== null) SWUBounceUnit($caster, $m); }
        return;
    }
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", $mzA . "&" . $mzB, 1,
        tooltip:"Choose_which_unit_returns_to_hand");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SOR_187#1|{$caster}|{$uids[0]}|{$uids[1]}", 1);
};

$customDQHandlers["SOR_187#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp    = intval($player);
    $caster = intval($parts[0] ?? 0);
    $uidA   = intval($parts[1] ?? 0);
    $uidB   = intval($parts[2] ?? 0);
    $playerID = $opp;
    $chosenUID = $uidA;
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $chosen = GetZoneObject($lastDecision);
        if ($chosen !== null) $chosenUID = intval($chosen->UniqueID);
    }
    $otherUID = ($chosenUID === $uidA) ? $uidB : $uidA;
    // Return chosen to owner's hand (resolve from the caster's frame), then bury the other.
    $playerID = $caster;
    $mzChosen = SWUFindMzByUID($chosenUID);
    if ($mzChosen !== null) SWUBounceUnit($caster, $mzChosen);
    $mzOther = SWUFindMzByUID($otherUID);
    if ($mzOther !== null) SWUUnitToBottomOfDeck($caster, $mzOther);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_187:0"] = function($player, $mzID = '') {
// I Had No Choice — "Choose up to 2 non-leader units. An opponent chooses 1
                          // of those units. Return that unit to its owner's hand and put the other on
                          // the bottom of its owner's deck."
            global $playerID;
            $playerID = intval($player);
            $units = SWUAllUnits(null, null, NonLeaderUnitFilter);
            if (empty($units)) return;   // no non-leader unit → fizzle
            SWUQueueMultiChoose($player, 0, 2, $units, "Choose_up_to_2_non-leader_units", "SOR_187#0");
            return;
};
