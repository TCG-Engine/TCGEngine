<?php
// SHD_183
// Cost 1 - Kintan Intimidator - [Cunning,Villainy] - Power 1 - HP 4
// Text: On Attack: Exhaust the defender.

// ─── SHD_183 Kintan Intimidator ───────────────────────────────────────────────
// On Attack: Exhaust the defender. (Mandatory; reads the attack target via SWU_CURRENT_DEFENDER. Base
// attacks have no unit defender → no-op.)
$onAttackAbilities["SHD_183:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $def = GetSWUVar('SWU_CURRENT_DEFENDER');
    if (!$def || strpos((string)$def, 'Arena') === false) return;
    $o = GetZoneObject($def);
    if ($o !== null && empty($o->removed)) OnExhaustCard(intval($player), $def);
};
