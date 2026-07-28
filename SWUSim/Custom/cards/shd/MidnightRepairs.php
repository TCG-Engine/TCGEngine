<?php
// SHD_054
// Cost 2 - Midnight Repairs - [Vigilance,Vigilance]
// Text: Heal up to 8 total damage from any number of units.

// ─── SHD_054 Midnight Repairs (Event) continuation ────────────────────────────
// Heal each assigned unit by its amount (clamped by OnHealUnit). No self-damage (unlike SOR_052).
$customDQHandlers["SHD_054#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode(',', (string)$lastDecision) as $pair) {
        $p = explode(':', $pair);
        if (count($p) < 2) continue;
        $mz = trim($p[0]); $amt = intval($p[1]);
        if ($amt <= 0) continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnHealUnit(intval($player), $mz, $amt);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_054:0"] = function($player, $mzID = '') {
// Midnight Repairs — "Heal up to 8 total damage from any number of units."
            $specs = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    $dmg = intval($o->Damage ?? 0);
                    if ($dmg > 0) $specs[] = "{$mz}:{$dmg}";
                }
            }
            if (empty($specs)) return;
            DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "8|" . implode("&", $specs) . "|UPTO", 1, tooltip:"Heal_up_to_8_damage_among_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SHD_054#0", 1);
            return;
};
