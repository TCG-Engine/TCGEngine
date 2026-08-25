<?php
// ASH_115
// Cost 1 - The Student Guides the Master - [Command,Heroism]
// Text: Give a friendly unit +1/+0 for this phase for each other friendly unit with less power than it.

// ASH_115 The Student Guides the Master — buff the chosen friendly unit +N/+0 (this phase), N = number
// of OTHER friendly units with less power than it.
$customDQHandlers["ASH_115#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $caster = intval($parts[0] ?? $player);
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) return;
    $chosenPow = intval(ObjectCurrentPower($chosen));
    $chosenUid = intval($chosen->UniqueID ?? 0);
    $n = 0;
    foreach (GetUnitsInPlay($caster) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $chosenUid && intval(ObjectCurrentPower($u)) < $chosenPow) $n++;
    }
    if ($n <= 0) return;
    SWUApplyPhaseBuff($lastDecision, $n, 0, 'ASH_115');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_115:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = SWUFriendlyUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Choose_a_friendly_unit_to_buff", "ASH_115#0|" . intval($player));
};
