<?php
// TWI_115
// Cost 6 - Osi Sobeck - Warden of the Citadel - [Command] - Power 3 - HP 6
// Text: Exploit 3 / When Played: This unit captures an enemy non-leader ground unit with cost equal to or less than the number of resources paid to play this unit.

// TWI_115 Baktoid Spider Droid (Osi Sobeck / Warden of the Citadel) — Exploit 3 (auto-wired)
// When Played: This unit captures an enemy non-leader ground unit with cost equal to or less
// than the number of resources paid to play this unit.
//
// Resources-paid is read from the SWU_PAID_n TurnEffect stamped by ActivateCard.
// With Exploit: defeating 1 unit → paid = 4; defeating 2 → paid = 2; defeating 3 → paid = 0.
// Paid = 0 → nothing eligible (cost ≤ 0 means printed cost 0, which no ground units have in
//             the current card pool), so the ability fizzles.
//
// Target selection: mandatory SWUQueueChooseTarget (PASSPARAMETER if 1 eligible, MZCHOOSE if
// 2+), then TWI_115|{selfUID} re-resolves the captor by UID (arena indices can shift
// during the async queue), calls the CaptureUnit macro.
$whenPlayedAbilities["TWI_115:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);

    $obj = GetZoneObject($mzID);
    if (SWUObjGone($obj)) return;

    $paid    = SWUUnitResourcesPaid($obj);
    $selfUID = intval($obj->UniqueID ?? 0);

    // Collect eligible enemy non-leader ground units: printed cost ≤ resources paid.
    $candidates = ZoneSearch("theirGroundArena", AnyUnitFilter);
    $eligible   = [];
    foreach ($candidates as $emz) {
        $eo = GetZoneObject($emz);
        if (SWUObjGone($eo)) continue;
        if (IsLeaderUnit($eo)) continue;                   // non-leader only
        if (intval(CardCost($eo->CardID)) <= $paid) $eligible[] = $emz;
    }

    if (empty($eligible)) return;   // fizzle — no eligible target (paid = 0 or no cheap units)

    SWUQueueChooseTarget(
        intval($player),
        $eligible,
        "Capture_an_enemy_non-leader_ground_unit_(cost_≤_{$paid})",
        "TWI_115#0|{$selfUID}"
    );
};

// TWI_115 step 2: perform the capture. $parts[0] = TWI_115's UniqueID (captured before the
// async queue so the mzID is re-resolved by UID after possible arena reindexing).
$customDQHandlers["TWI_115#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);

    $selfUID    = intval($parts[0] ?? 0);
    $captorMzID = SWUFindMzByUID($selfUID);

    if ($captorMzID === null) return;   // TWI_115 left play before capture resolved

    CaptureUnit(intval($player), $captorMzID, $lastDecision);
};
