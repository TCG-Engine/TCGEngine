<?php
// LAW_078
// Cost 3 - Sabine Wren - Spectre Five - [Aggression,Cunning,Heroism] - Power 3 - HP 3
// Text: Ambush (When you play this unit, she may attack an enemy unit.) / When Played: You may defeat a non-<uq> upgrade. If you control a Vigilance or Command unit, you may defeat an upgrade instead.

// LAW_078 Sabine Wren — Ambush + When Played: you may defeat a non-unique upgrade (any upgrade instead
// if you control a Vigilance or Command unit).
$whenPlayedAbilities["LAW_078:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $any = PlayerHasUnitWithAspectInPlay(intval($player), 'Vigilance') || PlayerHasUnitWithAspectInPlay(intval($player), 'Command');
    $filter = $any ? '' : 'unique=0';
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade?", may: true, max: 1, filter: $filter, min: 0);
};
