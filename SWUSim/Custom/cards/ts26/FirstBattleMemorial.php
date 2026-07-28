<?php
// TS26_09
// First Battle Memorial - [Vigilance] - HP 27
// Text: 
// Epic Action: For each friendly leader unit, give an Experience token to a unit.

$baseAbilities["TS26_09"] = function($player) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $n++; }
    FirstBattleMemorialGive(intval($player), $n);
};

$customDQHandlers["TS26_09#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = intval($parts[0] ?? 0);
    if ($lastDecision && str_contains($lastDecision, '-')) DoGiveExperienceToken(intval($player), $lastDecision);
    FirstBattleMemorialGive(intval($player), $remaining - 1);
};

// TS26_09 First Battle Memorial — Epic Action: for each friendly leader unit, give an Experience token
// to a unit. (Loops one "give Experience to a unit" pick per leader unit.)
function FirstBattleMemorialGive(int $player, int $remaining): void {
    global $playerID; $playerID = intval($player);
    if ($remaining <= 0) { SWUAfterAction($player); return; }
    $tg = SWUAllUnits();
    if (empty($tg)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $tg, "Give_an_Experience_token_to_a_unit", "TS26_09#0|{$remaining}");
}
