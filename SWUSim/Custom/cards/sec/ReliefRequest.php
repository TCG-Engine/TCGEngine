<?php
// SEC_074
// Cost 2 - Relief Request - [Vigilance]
// Text: Heal 3 damage from a unit. / You may disclose Vigilance (reveal a card from your hand with this aspect icon). If you do, heal 3 damage from another unit.

// SEC_074 Relief Request continuations — #0: heal the chosen unit 3, then offer the Vigilance
// disclose; #1: heal 3 from another (different) damaged unit.
$customDQHandlers["SEC_074#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? 0);
    OnHealUnit(intval($player), $lastDecision, 3);
    SWUQueueDisclose(intval($player), ['Vigilance'], "SEC_074#1|{$firstUID}",
        "Disclose_Vigilance_to_heal_3_from_another_unit");
};

$customDQHandlers["SEC_074#1"] = function($player, $parts, $lastDecision) {
    $firstUID = intval($parts[0] ?? 0);
    SWUOfferUnitTarget($player, '', ['continuation'=>'HEAL_TARGET','amount'=>3,'excludeUID'=>$firstUID,
        'extraFilter'=>fn($o)=>intval($o->Damage ?? 0) > 0,'prompt'=>"Heal_3_damage_from_another_unit"]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_074:0"] = function($player, $mzID = '') {
// Relief Request — "Heal 3 damage from a unit. You may disclose Vigilance →
                          // heal 3 damage from another unit." First heal (mandatory) over damaged units,
                          // then the optional disclose → a second heal on a DIFFERENT damaged unit.
            SWUOfferUnitTarget($player, $mzID, ['continuation'=>'SEC_074#0',
                'extraFilter'=>fn($o)=>intval($o->Damage ?? 0) > 0,'prompt'=>"Heal_3_damage_from_a_unit"]);
};
