<?php
// HMW_150 Migrate — Event, cost 3, [Command], Innate.
// "For every 3 resources you control, create a Beast token."
// One create instruction for floor(resources / 3) tokens (Credits are not resources). The resources spent
// on Migrate are still controlled, so they count.

$whenPlayedAbilities["HMW_150:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $n = intdiv(SWUResourceCount(intval($player)), 3);
    if ($n > 0) SWUCreateUnitTokens(intval($player), 'HMW_T03', $n);
};
