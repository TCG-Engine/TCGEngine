<?php
// SHD_220
// Cost 7 - Fennec Shand - Loyal Sharpshooter - [Cunning] - Power 4 - HP 6
// Text: Ambush (When you play this unit, it may ready and attack an enemy unit.) / On Attack: Deal 1 damage to the defender (if it's a unit) for each different cost among cards in your discard pile.

// ─── SHD_220 Fennec Shand ─────────────────────────────────────────────────────
// Ambush (auto) + On Attack: Deal 1 damage to the defender (if it's a unit) for each DIFFERENT cost among
// cards in your discard pile.
$onAttackAbilities["SHD_220:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $def = GetSWUVar('SWU_CURRENT_DEFENDER');
    if (!$def || strpos((string)$def, 'Arena') === false) return;   // base attack → no unit defender
    $costs = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $costs[intval(CardCost($o->CardID ?? ''))] = true;
    }
    $n = count($costs);
    if ($n <= 0) return;
    SWUDealDamageToUnit($def, $n, intval($player));
};
