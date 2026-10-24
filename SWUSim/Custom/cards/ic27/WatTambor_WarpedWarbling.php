<?php
// IC27_143
// Cost 3 - Wat Tambor - Warped Warbling - [Cunning,Villainy] - Unit (Ground) 4/2 (unique)
//   Traits: Separatist, Official
// Text: Sentinel / When Played: You may attack with a unit.
//
// SEC_172 Cinta Kaz's sentence, resolved through SEC_172's own continuation. The pool is the units that
// could declare an attack RIGHT NOW (`_SWUUnitCanAttackNow`: ready, not "can't attack", at least one
// legal target), rather than SEC_172's bare ready check, so a Loth-Wolf is never offered for an attack
// that would silently fizzle. Wat Tambor himself entered play exhausted and is excluded by that check.
// "attack with a unit" can only ever mean a unit you CONTROL, so a Team Suns teammate's unit is out.
$whenPlayedAbilities["IC27_143:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $attackers = [];
    foreach (['myGroundArena' => 'GroundArena', 'mySpaceArena' => 'SpaceArena'] as $zone => $arena) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            if (_SWUUnitCanAttackNow(intval($player), $arr[$i], $arena)) $attackers[] = "{$zone}-{$i}";
        }
    }
    if (empty($attackers)) return;
    SWUQueueMayChooseTarget(intval($player), $attackers, "Attack_with_a_unit?", "Choose_a_unit_to_attack_with", "SEC_172#0");
};
