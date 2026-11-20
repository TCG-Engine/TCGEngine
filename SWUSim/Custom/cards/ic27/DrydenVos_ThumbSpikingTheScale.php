<?php
// IC27_014
// Cost 2 - Dryden Vos - Thumb-Spiking the Scale - [Vigilance,Villainy] - Unit (Ground) 1/4 (unique)
//   Traits: Underworld
// Text: On Attack: You may deal 1 damage to a friendly unit. If it survives, give an Experience token to it.
//
// "a friendly unit" (no "another") — Dryden himself is legal; "friendly" spans the team. Optional pick
// (MZMAYCHOOSE — safe from an On Attack closure). The continuation deals the 1 with Dryden as the damage source,
// then re-finds the chosen unit by UniqueID: a defeat compacts the arena, and the unit that slides into the
// dead one's index must not get the token. "If it survives" names an outcome, so a Shield that prevents the
// damage still leaves a survivor that gets the token (preview judgement call).
// Tests: SWUSim/Tests/Cases/ic27/DrydenVos_ThumbSpikingTheScale.md
$onAttackAbilities["IC27_014:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'side' => 'friendly', 'may' => true,
        'continuation' => 'IC27_014#0|' . _SWUEncodeDamageSource($mzID),
        'prompt' => 'Deal_1_damage_to_a_friendly_unit_(Experience_if_it_survives)',
    ]);
};

$customDQHandlers["IC27_014#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = SWUObjUID($o);
    SWUDealDamageToUnit($lastDecision, 1, intval($player), _SWUDecodeDamageSource((string)($parts[0] ?? '')));
    $mz = SWUFindMzByUID($uid);
    if ($mz === null || SWUObjGone(GetZoneObject($mz))) return;   // "If it survives"
    DoGiveExperienceToken(intval($player), $mz);
};
