<?php
// TWI_145
// Cost 3 - Jesse - Hard-Fighting Patriot - [Aggression,Heroism] - Power 4 - HP 4
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: An opponent creates 2 Battle Droid tokens.

// TWI_145 Jesse — "When Played: An opponent creates 2 Battle Droid tokens." (Raid 1 is a keyword.)
$whenPlayedAbilities["TWI_145:0"] = function($player, $mzID) {
    // "AN opponent creates 2 Battle Droid tokens" — the caster chooses WHO. Auto-resolves to an invisible
    // PASSPARAMETER at one eligible opponent, so Premier is byte-identical (I1).
    // ⚠ NO $eligible filter: creating tokens always succeeds — no board, hand, deck or arena-capacity
    // state can make a live opponent unable to receive them. Nobody can be filtered out as unaffected.
    // ⚠ This clause is a DRAWBACK being aimed (it hands an opponent two bodies), which is exactly why the
    // choice matters and why intuition about "who benefits" must not drive the eligibility decision.
    SWUQueueChooseOpponent(intval($player), 'TWI_145#0',
        "Choose_an_opponent_to_create_2_Battle_Droids");
};

$customDQHandlers["TWI_145#0"] = function($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    SWUCreateUnitTokens($opp, 'TWI_T01', 2);
};
