<?php
// SOR_007
// Cost 5 - Grand Moff Tarkin - Oversector Governor - [Command,Villainy] - Power 2 - HP 7
// Text: Action [1 resource, exhaust]: Give an Experience token to an Imperial unit.
// DeployText: On Attack: You may give an Experience token to another Imperial unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// SOR_007 Grand Moff Tarkin — deployed leader unit On Attack: You may give an Experience token
// to ANOTHER Imperial unit. $mzID = the attacking Tarkin leader-unit's mzID (excluded by UID).
$onAttackAbilities["SOR_007:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits'       => 'Imperial',
        'excludeSelf'  => true,
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => 'Choose_an_Imperial_unit_for_an_Experience_token',
        'question'     => 'Give_an_Experience_token_to_another_Imperial_unit?',
    ]);
};

// SOR_007 Grand Moff Tarkin — Leader Action [1 resource, exhaust]: Give an Experience token
// to an Imperial unit. (Framework exhausts the leader + gates affordability; closure pays the
// resource, like SOR_006.)
$leaderAbilities["SOR_007"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    // ⚠ TWO DEFECTS FIXED ON THIS LINE.
    // 1. UNQUALIFIED TARGET WORDS SPAN BOTH SIDES. The printed front text is "Give an Experience token
    //    to an Imperial unit" — no "friendly". SWU templating says "friendly" when it means it
    //    (SOR_094 "another FRIENDLY unit"), so an unqualified "an Imperial unit" reaches the enemy's
    //    Imperials too. `SWUAllUnits('my')` is friendly-only; the unqualified pool passes null.
    //    The proof is on THIS CARD: its deployed side, one function above, already passes
    //    'friendlyOnly' => false for the identically-unqualified "another Imperial unit". Two halves of
    //    one card read the same words oppositely, and the deployed half is the correct one.
    //    ⚠ The old pool also AUTO-RESOLVED whenever you controlled exactly one Imperial — so the
    //    violation was invisible: no offer existed to inspect.
    // 2. TRAIT LOOKUP MUST BE OBJECT-AWARE. `HasTrait($obj->CardID, …)` reads the PRINTED trait list of
    //    a bare CardID, so a unit that was GRANTED the Imperial trait (or a deployed leader carrying a
    //    trait override) was invisible to it. `TraitContains($obj, …)` is the object-aware check and
    //    falls back to the printed list when there is no override.
    $targets = array_values(array_filter(SWUAllUnits(null),
        fn($mz) => TraitContains(GetZoneObject($mz), 'Imperial')));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, 'Give_an_Experience_token_to_an_Imperial_unit', 'GIVE_EXPERIENCE|1');
    SWUQueueAfterAction($player);
};
