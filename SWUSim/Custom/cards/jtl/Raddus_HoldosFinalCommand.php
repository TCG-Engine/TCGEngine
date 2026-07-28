<?php
// JTL_104
// Cost 7 - Raddus - Holdo's Final Command - [Command,Heroism] - Power 8 - HP 6
// Text: While you control another Resistance card (unit, upgrade, or leader), this unit gains Sentinel. / When Defeated: Deal damage equal to this unit's power to an enemy unit.

// ── JTL_104 Raddus — conditional Sentinel (in KeywordEffects) + When Defeated: deal damage equal to
// this unit's power to an enemy unit. ────────────────────────────────────────────────────────────────
$whenDefeatedAbilities["JTL_104:0"] = function($player, $mzID) {
    global $playerID, $gWDPowerSnapshot;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    // Prefer the defeat-time snapshot (taken while Raddus's upgrades were still attached) over the now-
    // stripped live object, so the damage reflects the buffed power. Fall back to live/printed.
    if (isset($gWDPowerSnapshot[$mzID])) { $power = intval($gWDPowerSnapshot[$mzID]); unset($gWDPowerSnapshot[$mzID]); }
    else $power = ($self !== null) ? ObjectCurrentPower($self) : 0;
    if ($power <= 0) $power = intval(CardPower('JTL_104'));
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_Raddus's_power_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|" . $power);
};
