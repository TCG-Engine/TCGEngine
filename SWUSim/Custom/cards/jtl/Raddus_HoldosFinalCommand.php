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
    // ⚠ DO NOT unset the snapshot after reading. A JTL_002 Thrawn / JTL_169 Shadow Caster replay uses
    // the SAME defeat instance and must see the SAME frozen power — ASH_195 Helgait's handler says so
    // explicitly and this one did the opposite. Combined with the global dying at the request boundary,
    // the replay fell through to a live re-resolve of a raw mzID and read whichever unit had COMPACTED
    // into Raddus's vacated slot (measured: a buffed 10 replayed as 1).
    if (isset($gWDPowerSnapshot[$mzID])) { $power = intval($gWDPowerSnapshot[$mzID]); }
    else $power = intval(GetSWUVar('SWU_WDPOWER_MZ_' . str_replace('-', '_', $mzID), '0'));
    if ($power <= 0) {
        // An mzID is a SLOT, not an identity — only trust a live re-resolve if it is still Raddus.
        $power = ($self !== null && empty($self->removed) && ($self->CardID ?? '') === 'JTL_104')
            ? intval(ObjectCurrentPower($self)) : 0;
    }
    if ($power <= 0) $power = intval(CardPower('JTL_104'));
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_{$power}_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|" . $power);
};
