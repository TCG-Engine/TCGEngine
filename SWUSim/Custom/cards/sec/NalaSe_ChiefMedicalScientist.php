<?php
// SEC_065
// Cost 5 - Nala Se - Chief Medical Scientist - [Vigilance] - Power 4 - HP 7
// Text: On Attack: You may disclose VigilanceVigilance (reveal cards from your hand with these aspect icons among them). If you do, heal up to 4 damage from among other units.

// SEC_065 Nala Se — On Attack: you may disclose VigilanceVigilance → heal up to 4 damage from among
// OTHER units (the attacker herself excluded by UID). Disclose runs in the OnAttack window via
// MZMULTICHOOSE (safe); the heal split rides a continuation (safe vs the OnAttack mzID-restore gotcha).
$onAttackAbilities["SEC_065:0"] = function($player, $mzID) {
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    SWUQueueDisclose(intval($player), ['Vigilance', 'Vigilance'], "SEC_065#0|{$selfUID}",
        "Disclose_VigilanceVigilance_to_heal_up_to_4");
};

$customDQHandlers["SEC_065#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $specs = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $selfUID) continue;   // "other units"
        $dmg = intval($o->Damage ?? 0);
        if ($dmg > 0) $specs[] = "{$mz}:{$dmg}";
    }
    if (empty($specs)) return;
    DecisionQueueController::AddDecision(intval($player), "MZSPLITASSIGN",
        "4|" . implode("&", $specs) . "|UPTO", 1, tooltip: "Heal_up_to_4_damage_among_other_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_065#1", 1);
};

$customDQHandlers["SEC_065#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode(',', (string)$lastDecision) as $pair) {
        $pp = explode(':', $pair);
        if (count($pp) < 2) continue;
        $mz = trim($pp[0]); $amt = intval($pp[1]);
        if ($amt <= 0) continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnHealUnit(intval($player), $mz, $amt);
    }
};
