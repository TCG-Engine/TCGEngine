<?php
// SOR_155
// Cost 4 - Aggression - [Aggression,Aggression]
// Text: Choose two, in any order: / Draw a card. / Defeat up to 2 upgrades. / Ready a unit with 3 or less power. / Deal 4 damage to a unit.

// SOR_155 "defeat up to 2 upgrades" — the second link (fired by the first's thenHandler once it fully
// resolves). It re-reads the board, so this second upgrade can be on a DIFFERENT unit than the first.
$customDQHandlers["SOR_155#0"] = function ($player, $parts, $lastDecision) {
  SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade_(2_of_2)", may: true, max: 1, min: 0);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_155:0"] = function($player, $mzID = '') {
// Aggression — draw / defeat up to 2 upgrades / ready a ≤3-power unit / deal 4 to a unit
            SWUQueueModalChoose(intval($player), 'SOR_155', ['Draw', 'DefeatUpgrades', 'Ready', 'Deal4'], 2);
            return;
};
