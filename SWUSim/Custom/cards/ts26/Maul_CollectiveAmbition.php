<?php
// TS26_03
// Cost 7 - Maul - Collective Ambition - [Command,Villainy] - Power 5 - HP 9
// Text: Action [Exhaust]: Choose a unit. If it has more different keywords than it has Experience tokens on it, give an Experience token to it and deal 1 damage to it.
// DeployText: When Deployed/On Attack: Choose a unit. If it has more different keywords than it has Experience tokens on it, give an Experience token to it and deal 1 damage to it.
// Epic Action: If you control 7 or more resources, deploy this leader.

// TS26_03 Maul (deployed) — When Deployed/On Attack: same "keywords > Experience → +1 Exp & 1 damage"
// effect as the front Action. Shared TS26_03#0 handler (parts[0]=0 → trigger/combat owns the close).
$whenPlayedAbilities["TS26_03:0"] = $onAttackAbilities["TS26_03:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Choose_a_unit_(+1_Exp_and_1_damage_if_more_keywords_than_Experience)?", "Choose_a_unit", "TS26_03#0|0");
};

$customDQHandlers["TS26_03#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $close = intval($parts[0] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && str_contains($lastDecision, '-')) {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            // EVERY keyword counts, once each (ruling: Raid 1 + Raid 2 is one keyword) — the shared counter
            // derives the set from the generated keyword functions. A private list of ten here missed Smuggle,
            // Coordinate, Exploit, Fortify, Piloting, Plot and Support (SHD_032 Lom Pyke got nothing).
            $K = _SWUCountDistinctKeywords($o);
            $E = 0;
            foreach (($o->Subcards ?? []) as $sc) {
                $scid = is_array($sc) ? ($sc['CardID'] ?? '') : ($sc->CardID ?? '');
                $srem = is_array($sc) ? !empty($sc['removed']) : !empty($sc->removed);
                if (!$srem && $scid === 'SOR_T01') $E++;
            }
            if ($K > $E) {
                DoGiveExperienceToken(intval($player), $lastDecision);
                SWUDealDamageToUnit($lastDecision, 1, intval($player));
            }
        }
    }
    if ($close === 1) SWUAfterAction(intval($player));
};

// TS26_03 Maul (front) — Action [Exhaust]: choose a unit; if it has more different keywords than
// Experience tokens on it, give an Experience token to it and deal 1 damage to it. (Deployed side: same
// effect on When Deployed / On Attack — shared TS26_03#0 handler.)
$leaderAbilities["TS26_03"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) { SWUAfterAction(intval($player)); return; }
    SWUQueueMayChooseTarget(intval($player), $tg, "Choose_a_unit_(+1_Exp_and_1_damage_if_more_keywords_than_Experience)?", "Choose_a_unit", "TS26_03#0|1");
};
