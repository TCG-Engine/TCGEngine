<?php
// LAW_152
// Cost 2 - C-3PO - Translation Protocol - [Command] - Power 1 - HP 4
// Text: On Attack: You may give an Experience token to another non-leader unit that shares a Trait with a friendly leader.

// LAW_152 C-3PO — On Attack: you may give an Experience token to another non-leader unit that shares a
// Trait with a friendly leader.
$onAttackAbilities["LAW_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $leader = SWUGetLeader(intval($player));
    if ($leader === null) return;
    $leaderTraits = array_filter(array_map('trim', explode(',', (string)(CardTrait($leader->CardID ?? '') ?? ''))));
    if (empty($leaderTraits)) return;
    // Another non-leader unit (either player) that shares a printed Trait with the friendly leader.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'side' => 'any', 'nonLeader' => true, 'excludeSelf' => true, 'may' => true,
        'extraFilter' => function($o) use ($leaderTraits) {
            $ut = array_filter(array_map('trim', explode(',', (string)(CardTrait($o->CardID ?? '') ?? ''))));
            return !empty(array_intersect($leaderTraits, $ut));
        },
        'question' => "Give_an_Experience_token_to_a_unit_sharing_a_Trait_with_your_leader?",
        'prompt'   => "Choose_a_unit",
    ]);
};
