<?php
// LOF_087
// Cost 6 - Eighth Brother - Hunt Together - [Command,Villainy] - Power 5 - HP 7
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When you play another unit: You may use the Force (lose your Force token). If you do, give a unit +2/+2 for this phase.

$customDQHandlers["LOF_087#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'APPLY_PHASE_BUFF|2|2|LOF_087',
        'side' => 'any', 'prompt' => "Give_a_unit_+2/+2_for_this_phase",
    ]);
};

function EighthBrotherReaction(int $player): void
{
  SWUQueueMayUseTheForce($player, "Use_the_Force_to_give_a_unit_+2/+2?", "LOF_087#0");
}
