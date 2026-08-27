<?php
// SEC_193
// Cost 7 - Grand Admiral Thrawn - Grand Schemer - [Cunning,Villainy] - Power 8 - HP 7
// Text: When Played: An opponent may choose a non-leader unit they control. If they do, this unit captures that unit. If they don't, ready this unit. / When Defeated: A friendly unit captures an enemy non-leader unit in the same arena.

// SEC_193 Grand Admiral Thrawn — When Played: an opponent MAY choose a non-leader unit they control; if
// they do, Thrawn captures it, else ready Thrawn. When Defeated: a friendly unit captures an enemy
// non-leader unit in the same arena.
$whenPlayedAbilities["SEC_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $thrawnUID = SWUObjUID($self, 0);
    // "AN opponent may choose a non-leader unit they control" — the caster picks WHICH opponent.
    //
    // ⚠⚠ ELIGIBILITY: EVERY live opponent, NOT just those holding a unit — and this card is the reason
    // the sweep's canonical I2 sentence is not a safe shortcut. SEC_193 matches Cad Bane's wording word
    // for word, but Cad Bane's other leg deals 1 damage (so a unit-less opponent achieves NOTHING and is
    // correctly filtered), whereas SEC_193's other leg is "IF THEY DON'T, READY THIS UNIT" — a real,
    // positive outcome for the caster. Naming a unit-less opponent is therefore a GUARANTEED ready of an
    // 8/7, versus a stocked opponent which is only a MAYBE-capture they can decline into the same ready.
    // Those are materially different plays, so the menu entry is not a choice among nothing.
    // Same rule as TWI_222 vs TS26_33: read what happens when the chosen player CAN'T act.
    //
    // The GATE is board-level instead: if NO opponent anywhere has a non-leader unit, every answer
    // collapses to "ready Thrawn", which IS degenerate — so skip the picker and just ready him.
    $anyUnit = false;
    foreach (OpponentsOf(intval($player)) as $o) {
        $sp0 = $playerID; $playerID = $o;
        $u = array_merge(ZoneSearch('myGroundArena', NonLeaderUnitFilter), ZoneSearch('mySpaceArena', NonLeaderUnitFilter));
        $playerID = $sp0;
        if (!empty($u)) { $anyUnit = true; break; }
    }
    if (!$anyUnit) {
        $playerID = intval($player);
        $tmz = SWUFindMzByUID($thrawnUID);
        if ($tmz !== null) OnReadyCard(intval($player), $tmz);
        return;
    }
    SWUQueueChooseOpponent(intval($player), "SEC_193#3|{$thrawnUID}|" . intval($player),
        "Choose_an_opponent_to_offer_the_capture");
};

// ⚠ NAMED #3, NOT #1: this card already registers SEC_193#1 and SEC_193#2 further down (the
// When-Defeated capture chain). A duplicate key SILENTLY OVERWRITES the earlier registration with no
// error and no warning — the picker queued fine and its continuation simply never ran, which reads
// exactly like a decision-queue ordering bug. Grep the card file for existing "#N" keys before adding one.
$customDQHandlers["SEC_193#3"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $thrawnUID = intval($parts[0] ?? 0);
    $caster    = intval($parts[1] ?? $player);
    $opp       = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $playerID = $opp;
    $units = array_merge(ZoneSearch('myGroundArena', NonLeaderUnitFilter), ZoneSearch('mySpaceArena', NonLeaderUnitFilter));
    if (empty($units)) {   // a unit-less opponent WAS a legal pick → they cannot choose → ready Thrawn
        $playerID = $caster;
        $tmz = SWUFindMzByUID($thrawnUID);
        if ($tmz !== null) OnReadyCard($caster, $tmz);
        return;
    }
    DecisionQueueController::AddDecision($opp, "MZMAYCHOOSE", implode('&', $units), 1, tooltip: "Choose_a_unit_to_be_captured_by_Thrawn_(or_pass)");
    // ⚠ dontSkipOnPass: the opponent DECLINING is a real answer here — "no unit is captured, so Thrawn
    // readies" — and a sticky "PASS" would otherwise skip this continuation entirely, leaving Thrawn
    // exhausted. The handler's own `$lastDecision !== 'PASS'` branch was unreachable without it.
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SEC_193#0|{$thrawnUID}|" . $caster, 1, dontSkipOnPass: 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};

$customDQHandlers["SEC_193#0"] = function($player, $parts, $lastDecision) {   // $player = the opponent
    global $playerID; $playerID = intval($player);
    $thrawnUID = intval($parts[0] ?? 0);
    $caster    = intval($parts[1] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $chosen = GetZoneObject($lastDecision);
        $chosenUID = ($chosen !== null) ? intval($chosen->UniqueID ?? 0) : 0;
        $playerID = $caster;
        $tmz = SWUFindMzByUID($thrawnUID);
        $cmz = SWUFindMzByUID($chosenUID);
        if ($tmz !== null && $cmz !== null) DoCaptureUnit($caster, $tmz, $cmz);
        return;
    }
    // declined → ready Thrawn
    $playerID = $caster;
    $tmz = SWUFindMzByUID($thrawnUID);
    if ($tmz !== null) OnReadyCard($caster, $tmz);
};

$whenDefeatedAbilities["SEC_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_capturing_unit", "SEC_193#1");
};

$customDQHandlers["SEC_193#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = GetZoneObject($lastDecision);
    if (SWUObjGone($captor)) return;
    $captorUID = intval($captor->UniqueID ?? 0);
    $isSpace = strpos((string)($captor->Location ?? ''), 'Space') !== false;
    $enemies = array_values(array_filter(ZoneSearch($isSpace ? 'theirSpaceArena' : 'theirGroundArena', NonLeaderUnitFilter),
        fn($mz) => ($e = GetZoneObject($mz)) !== null && empty($e->removed)));
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_enemy_unit_in_the_same_arena", "SEC_193#2|{$captorUID}");
};

$customDQHandlers["SEC_193#2"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};
