<?php
// IC27_031
// Cost 1 - BB-8 - Frazzled Friend - [Vigilance,Heroism] - Unit (Ground) 0/4 (unique)
//   Traits: Resistance, Droid
// Text: On Attack: Heal 1 damage to a friendly unit. If you do, deal 1 damage to an enemy unit.
//
// Both picks are mandatory, so they are queued from CUSTOM continuations: a mandatory relative-mzID MZCHOOSE
// queued straight from an On Attack closure is skipped (OnAttackTrigger restores $playerID before
// MZCountChoices runs). The pools: "a friendly unit" = every unit on the team (BB-8 included, undamaged ones
// included); "an enemy unit" = 'their' (team-aware). BB-8 is the source of the 1 damage.
// "If you do" is measured: the heal must actually remove damage (PREVIEW JUDGEMENT CALL — choosing an undamaged
// unit heals nothing, so there is no ping). With no damaged friendly unit nothing can be healed whatever is
// chosen, so no prompt is raised at all.
// Tests: SWUSim/Tests/Cases/ic27/BB8_FrazzledFriend.md
$onAttackAbilities["IC27_031:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_031#0|' . _SWUEncodeDamageSource($mzID), 1);
};

$customDQHandlers["IC27_031#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $friendly = _SWUCollectUnitTargets(intval($player), ['side' => 'friendly']);
    $anyDamaged = false;
    foreach ($friendly as $mz) { if (intval(GetZoneObject($mz)->Damage ?? 0) > 0) { $anyDamaged = true; break; } }
    if (!$anyDamaged) return;
    SWUQueueChooseTarget(intval($player), $friendly, 'Heal_1_damage_from_a_friendly_unit', 'IC27_031#1|' . ($parts[0] ?? ''));
};

$customDQHandlers["IC27_031#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $before = intval($o->Damage ?? 0);
    OnHealUnit(intval($player), $lastDecision, 1);
    $after = GetZoneObject($lastDecision);
    if (SWUObjGone($after) || intval($after->Damage ?? 0) >= $before) return;   // "If you do"
    $enemy = _SWUCollectUnitTargets(intval($player), ['side' => 'their']);
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, 'Deal_1_damage_to_an_enemy_unit', 'DEAL_UNIT_DAMAGE|1|' . ($parts[0] ?? ''));
};
