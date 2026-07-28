<?php
// SHD_090
// Cost 7 - Maul - Shadow Collective Visionary - [Command,Villainy] - Power 7 - HP 6
// Text: Ambush, Overwhelm / On Attack: You may choose another friendly Underworld unit. If you do, all combat damage that would be dealt to this unit during this attack is dealt to the chosen unit instead.

// ─── SHD_090 Maul ────────────────────────────────────────────────────────────────
// Ambush, Overwhelm. On Attack: You may choose ANOTHER friendly Underworld unit. If you do, all combat
// damage that would be dealt to Maul this attack is dealt to the chosen unit instead. The pick sets an
// attack-duration marker "SWU_REDIRECT_DMG-{chosenUID}" on Maul; SWUCombatDamage reads it and routes the
// counter-damage to the chosen unit (see _SWUCombatRedirectTarget / _SWUMaybeRedirectAttackerDamage).
$onAttackAbilities["SHD_090:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->UniqueID ?? 0) === $selfUID) continue;             // "another"
            if (!TraitContains($o, 'Underworld')) continue;
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Redirect_combat_damage_to_a_friendly_Underworld_unit?",
        "Choose_a_friendly_Underworld_unit", "SHD_090#0|{$selfUID}");
};

$customDQHandlers["SHD_090#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // declined
    global $playerID; $playerID = intval($player);
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) return;
    $chosenUID = intval($chosen->UniqueID ?? 0);
    $maulMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($maulMz === null || $chosenUID <= 0) return;
    AddTurnEffect($maulMz, "SWU_REDIRECT_DMG-{$chosenUID}");
};
