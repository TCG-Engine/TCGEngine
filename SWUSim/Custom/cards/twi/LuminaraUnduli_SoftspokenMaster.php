<?php
// TWI_050
// Cost 7 - Luminara Unduli - Soft-Spoken Master - [Vigilance,Heroism] - Power 4 - HP 9 - Ground
// Text: Coordinate - Grit. When Played: Choose a base. Heal 1 damage from it for each unit you control.
// (Coordinate-Grit lives in KeywordEffects.php HasConditionalKeyword_Grit.)

// When Played — "a base" is unqualified, so every live seat's base is offered (SWUAllBaseMzIDs 'any'), and the
// choice is mandatory. The count is taken when the answer resolves, so Luminara herself is included.
$whenPlayedAbilities["TWI_050:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'TWI_050#0','prompt'=>"Choose_a_base_(heal_1_per_unit_you_control)"]);
};

$customDQHandlers["TWI_050#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, 'Base')) return;
    $owner = SWUMzOwner($lastDecision, intval($player));
    if ($owner <= 0) return;
    $count = count(GetUnitsInPlay(intval($player)));   // "units you control": tokens, deployed leaders, both arenas
    if ($count > 0) OnHealBase(intval($player), $owner, $count);
};
