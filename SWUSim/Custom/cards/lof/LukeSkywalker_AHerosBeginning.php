<?php
// LOF_249
// Cost 4 - Luke Skywalker - A Hero's Beginning - [Heroism] - Power 3 - HP 5
// Text: When you play another <uq> (unique) unit: You may use the Force (lose your Force token). If you do, give an Experience token and a Shield token to this unit.

$customDQHandlers["LOF_249#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    DoGiveExperienceToken(intval($player), $mz);
    GiveShieldToken(intval($player), $mz);
};

function LukeSkywalkerReaction(int $player, int $sourceUID): void
{
  SWUQueueMayUseTheForce($player, "Use_the_Force_to_give_Luke_an_Experience_and_a_Shield?", "LOF_249#0|{$sourceUID}");
}
