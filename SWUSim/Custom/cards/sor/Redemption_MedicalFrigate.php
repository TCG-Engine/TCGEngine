<?php
// SOR_052
// Cost 8 - Redemption - Medical Frigate - [Vigilance,Heroism] - Power 6 - HP 9
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Played: Heal up to 8 total damage from any number of units and/or bases. Deal that much damage to this unit.

// SOR_052 Redemption (Unit, Space, 6/9) — Sentinel (auto) + When Played: heal up to 8 total damage
// from any number of units and/or bases, then deal that much (the ACTUAL healed) to itself. Uses the
// MZSPLITASSIGN "up to" mode (per-target cap = current damage; partial submit allowed).
$whenPlayedAbilities["SOR_052:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $specs = [];
    // Damaged units in any arena (cap each at its current damage — can't heal more than is there).
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $dmg = intval($o->Damage ?? 0);
        if ($dmg > 0) $specs[] = "{$mz}:{$dmg}";
    }
    // Damaged bases.
    foreach (['myBase-0' => intval($player), 'theirBase-0' => GetOpponent(intval($player))] as $baseMz => $bp) {
        $base = GetBase($bp);
        $bdmg = (count($base) > 0 && empty($base[0]->removed)) ? intval($base[0]->Damage ?? 0) : 0;
        if ($bdmg > 0) $specs[] = "{$baseMz}:{$bdmg}";
    }
    if (empty($specs)) return; // nothing damaged → no heal, no self-damage
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "8|" . implode("&", $specs) . "|UPTO", 1, tooltip:"Heal_up_to_8_damage_(units_and-or_bases)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_052#0|{$selfUID}", 1);
};

// Heal each assigned target (clamped by OnHealUnit/OnHealBase, which also fire the heal animation),
// sum the ACTUAL healed, then deal that to Redemption ($parts[0] = its UniqueID).
$customDQHandlers["SOR_052#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $selfUID  = intval($parts[0] ?? 0);
    $totalHealed = 0;
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode(',', (string)$lastDecision) as $pair) {
            $p = explode(':', $pair);
            if (count($p) < 2) continue;
            $mz = trim($p[0]); $amt = intval($p[1]);
            if ($amt <= 0) continue;
            if (strpos($mz, 'Base') !== false) {
                $tp = (strpos($mz, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
                $base = GetBase($tp);
                $before = (count($base) > 0) ? intval($base[0]->Damage ?? 0) : 0;
                OnHealBase(intval($player), $tp, $amt);
                $base = GetBase($tp);
                $after = (count($base) > 0) ? intval($base[0]->Damage ?? 0) : 0;
                $totalHealed += max(0, $before - $after);
            } else {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $before = intval($o->Damage ?? 0);
                OnHealUnit(intval($player), $mz, $amt);
                $totalHealed += max(0, $before - intval($o->Damage ?? 0)); // $o is a live handle
            }
        }
    }
    if ($totalHealed > 0) {
        $selfMz = SWUFindMzByUID($selfUID);
        if ($selfMz !== null) SWUDealDamageToUnit($selfMz, $totalHealed, intval($player));
    }
};
