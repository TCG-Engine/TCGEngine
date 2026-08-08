<?php
// SEC_095
// Cost 2 - Theed Security - [Command,Heroism] - Power 2 - HP 3
// Text: When Played: If an opponent controls an upgrade, give an Experience token to a unit.

// SEC_095 Theed Security — When Played: if an opponent controls an upgrade, give an Experience token to a unit.
$whenPlayedAbilities["SEC_095:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "If an OPPONENT CONTROLS an upgrade" — a question about the UPGRADE'S controller, not about which
    // unit it sits on. Per CR 2.e a player who plays an upgrade onto an ENEMY unit REMAINS its controller,
    // so an opponent-controlled upgrade can be attached to one of OUR units. Scanning only the opponent's
    // units (the old `SWUAllUnits('their')`) missed exactly that case — and would also have wrongly counted
    // OUR OWN upgrade sitting on an enemy unit. Scan every unit and test each upgrade's controller.
    $oppHasUpgrade = false;
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $hostCtrl = intval($o->Controller ?? $o->Owner ?? 0);
        foreach (GetUpgradesOnUnit($o) as $up) {
            $cid   = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
            $isCap = is_array($up) ? !empty($up['IsCaptive']) : !empty($up->IsCaptive);
            if ($isCap || $cid === '' || strpos(strtolower(CardType($cid) ?? ''), 'token') !== false) continue;
            $upCtrl = is_array($up) ? intval($up['Controller'] ?? $up['Owner'] ?? $hostCtrl)
                                    : intval($up->Controller ?? $up->Owner ?? $hostCtrl);
            if ($upCtrl > 0 && $upCtrl !== intval($player)) { $oppHasUpgrade = true; break 2; }
        }
    }
    if (!$oppHasUpgrade) return;
    GiveTokenUpgrade($player, $mzID, [
        'friendlyOnly' => false,
        'prompt'       => "Give_an_Experience_token_to_a_unit",
    ]);
};
