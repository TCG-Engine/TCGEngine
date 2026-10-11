<?php
// TS26_11
// Executioner's Arena - [Aggression] - HP 27
// Text: 
// Epic Action: For each friendly leader unit, you may deal 2 damage to a unit.

$baseAbilities["TS26_11"] = function($player) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $n++; }
    ExecutionersArenaDeal(intval($player), $n);
};

// $parts: [instances left, picks so far "uid,uid"]. CR 8.34.1: choose every instance first, then resolve them
// together — and 8.34.1.a: the damage to a target is ONE instance (2 per pick, summed). Dealing each pick as it was
// chosen let a Shield stop only the first of two picks on the same unit.
$customDQHandlers["TS26_11#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = intval($parts[0] ?? 0);
    $picks = (isset($parts[1]) && $parts[1] !== '') ? explode(',', $parts[1]) : [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && str_contains($lastDecision, '-')) {
        $o = GetZoneObject($lastDecision);
        if (!SWUObjGone($o)) $picks[] = intval($o->UniqueID ?? 0);
    }
    ExecutionersArenaDeal(intval($player), $remaining - 1, $picks);
};

// TS26_11 Executioner's Arena — Epic Action: for each friendly leader unit, you may deal 2 damage to a
// unit. One "may deal 2 to a unit" pick per leader unit; the damage lands only after the last pick.
function ExecutionersArenaDeal(int $player, int $remaining, array $picks = []): void {
    global $playerID; $playerID = intval($player);
    $tg = ($remaining > 0) ? SWUAllUnits() : [];
    if ($remaining > 0 && !empty($tg)) {
        SWUQueueMayChooseTarget($player, $tg, "Deal_2_damage_to_a_unit?", "Choose_a_unit", "TS26_11#0|{$remaining}|" . implode(',', $picks));
        return;
    }
    $byTarget = [];
    foreach ($picks as $uid) { if (intval($uid) > 0) $byTarget[intval($uid)] = ($byTarget[intval($uid)] ?? 0) + 2; }
    foreach ($byTarget as $uid => $total) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID(intval($uid));
        if ($mz !== null) SWUDealDamageToUnit($mz, $total, intval($player));
    }
    SWUAfterAction($player);
}
