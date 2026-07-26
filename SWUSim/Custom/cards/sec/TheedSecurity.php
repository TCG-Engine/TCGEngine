<?php
// SEC_095
// Cost 2 - Theed Security - [Command,Heroism] - Power 2 - HP 3
// Text: When Played: If an opponent controls an upgrade, give an Experience token to a unit.

// SEC_095 Theed Security — When Played: if an opponent controls an upgrade, give an Experience token to a unit.
$whenPlayedAbilities["SEC_095:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $oppHasUpgrade = false;
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        foreach (GetUpgradesOnUnit($o) as $up) {
            $cid = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
            $isCap = is_array($up) ? !empty($up['IsCaptive']) : !empty($up->IsCaptive);
            if (!$isCap && $cid !== '' && strpos(strtolower(CardType($cid) ?? ''), 'token') === false) { $oppHasUpgrade = true; break 2; }
        }
    }
    if (!$oppHasUpgrade) return;
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Give_an_Experience_token_to_a_unit", "GIVE_EXPERIENCE|1");
};
