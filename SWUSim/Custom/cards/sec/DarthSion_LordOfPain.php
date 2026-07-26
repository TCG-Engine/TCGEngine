<?php
// SEC_035
// Cost 5 - Darth Sion - Lord of Pain - [Vigilance,Villainy] - Power 5 - HP 5
// Text: When Played: Give an Experience token to this unit for each enemy unit that was defeated this phase. / When Defeated: If this unit had 7 or more power, return him to his owner's hand.

// SEC_035 Darth Sion — When Played: give an Experience token to him for each enemy unit defeated this
// phase. When Defeated: if he had 7 or more power, return him to his owner's hand.
$whenPlayedAbilities["SEC_035:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = GlobalEffectCount(intval($player), 'SWU_ENEMY_DEFEATED');
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $mzID);
};

// SEC_035 Darth Sion — When Defeated: if this unit had 7 or more power, return him to his owner's hand.
// His power-at-defeat (Experience subcards included) is snapshotted in CollectWhenDefeatedTriggers while
// the subcards are still attached; here they're already stripped, so we read the snapshot, not the live
// (base-5) power. Mandatory (not "you may").
$whenDefeatedAbilities["SEC_035:0"] = function($player, $mzID) {
    global $playerID, $gSec035DefeatSnapshot;
    $snap = $gSec035DefeatSnapshot[$mzID] ?? null;
    unset($gSec035DefeatSnapshot[$mzID]);
    if ($snap === null || intval($snap['power']) < 7) return;
    $owner = intval($snap['owner']);
    $playerID = $owner;
    $dmz = _SWUFindDiscardMzID($owner, 'SEC_035');
    if ($dmz === null) return;                    // already moved / not in discard → nothing to return
    SWUReturnFromDiscardToHand($owner, $dmz);
};
