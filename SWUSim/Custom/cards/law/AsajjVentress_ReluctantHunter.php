<?php
// LAW_061
// Cost 5 - Asajj Ventress - Reluctant Hunter - [Command,Aggression] - Power 3 - HP 3
// Text: When Played: You may ready another Bounty Hunter unit.

// LAW_061 Asajj Ventress — When Played: you may ready another Bounty Hunter unit.
$whenPlayedAbilities["LAW_061:0"] = function($player, $mzID) {
    // "Ready ANOTHER Bounty Hunter unit" — no "friendly" qualifier, so an ENEMY Bounty Hunter is a legal
    // target too (and deployed BH leaders, which live in the arena zones, qualify). Search all four arenas.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'READY_UNIT', 'traits' => 'Bounty Hunter', 'excludeSelf' => true, 'may' => true,
        'question' => "Ready_another_Bounty_Hunter_unit?", 'prompt' => "Choose_a_Bounty_Hunter",
    ]);
};
