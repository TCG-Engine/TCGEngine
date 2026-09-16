<?php
// HMW_216 Insurgent Camp — Upgrade, cost 1, Fortification.
// "Fortify. When you play a unit with 3 or less power: You may defeat this upgrade. If you do, ready that unit."
// Collected in SWUCollectOwnPlayReactions (GameLogic), dispatched here. HMW_171 Trap Field's shape: one
// trigger with the copy count, one may-defeat per copy until declined or none left.

if (!function_exists('Hmw216InsurgentCampReaction')) {
    function Hmw216InsurgentCampReaction(int $player, int $playedUID, int $count): void {
        global $playerID; $playerID = $player;
        if (SWUFindMzByUID($playedUID) === null) return;
        if (_SWUCountBaseUpgrades($player, 'HMW_216') <= 0) return;
        DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Defeat_Insurgent_Camp_to_ready_that_unit?");
        DecisionQueueController::AddDecision($player, 'CUSTOM', "HMW_216#0|{$playedUID}|{$count}", 1);
    }
}

$customDQHandlers["HMW_216#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $zone = GetBase(intval($player)); $base = $zone[0] ?? null;
    if ($base === null) return;
    $idx = SWUFindUpgradeIndex($base, 'HMW_216');
    if ($idx < 0 || !SWUDefeatUpgrade(intval($player), 'myBase-0', $idx)) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};
