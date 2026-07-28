<?php
// ASH_232
// Cost 2 - Full of Surprises - [Cunning]
// Text: Return an upgrade that costs 2 or less to its owner's hand. / Give a Shield token to a unit.

$customDQHandlers["ASH_232#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && str_contains($lastDecision, '-')) {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            foreach (GetUpgradesOnUnit($o) as $up) {
                $ucid  = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
                $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
                if ($ucid !== '' && !$isTok && intval(CardCost($ucid)) <= 2) { SWUReturnUpgradeToHand($lastDecision, $ucid, intval($player)); break; }
            }
        }
    }
    _SWUAsh232GiveShield(intval($player));   // the Shield half always happens
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_232:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $hosts = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        foreach (GetUpgradesOnUnit($o) as $up) {
            $ucid  = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
            $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
            if ($ucid !== '' && !$isTok && intval(CardCost($ucid)) <= 2) { $hosts[] = $mz; break; }
        }
    }
    if (!empty($hosts)) {
        SWUQueueChooseTarget(intval($player), $hosts, "Return_an_upgrade_(cost_2_or_less)_to_owner's_hand", "ASH_232#0");
    } else {
        _SWUAsh232GiveShield(intval($player));   // no upgrade to return → straight to the Shield
    }
};
