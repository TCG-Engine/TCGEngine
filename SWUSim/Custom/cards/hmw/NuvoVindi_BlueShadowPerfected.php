<?php
// HMW_062
// Cost 3 - Nuvo Vindi, Blue Shadow Perfected - [Vigilance][Villainy] - Unit (Ground) 1/4
// Trait: Separatist - Unique
// Text: When Played: You may give a Weakness token to a unit.
//       When an enemy unit with a Weakness token on it is defeated: You may give a Weakness token to a
//       unit. Use this ability only once each round.
//
// CLAUSE A — When Played. "a unit" is unqualified, so friendly AND enemy units are legal (and Vindi
// himself is already in play when his own When Played resolves, so he is a legal target too).
//
// CLAUSE B — the reactive half. Its two non-obvious parts both live outside this file:
//   • The "with a Weakness token on it" test is captured as $d['weakened'] at the DEFEAT sites, while
//     the dying unit's subcards are still intact. SWUCollectLeavePlayReactions runs after they are
//     stripped, so the token cannot be read off the unit at reaction time — this mirrors the
//     $d['upgraded'] capture SHD_137 relies on.
//   • The observer + the once-each-round gate live in SWUCollectLeavePlayReactions (GameLogic.php),
//     which is where every defeat path funnels. The flag is consumed at COLLECT time, so declining the
//     offer still spends the round — the ability triggered.
// The round flag SWU_HMW062_USED is cleared in RegroupPhaseStart with the other once/round flags.

$whenPlayedAbilities["HMW_062:0"] = function ($player, $mzID = '') {
    GiveTokenUpgrade(intval($player), $mzID, [
        'token'        => 'WEAKNESS',
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit',
    ]);
};

// Dispatched by the 'HMW_062' case in DispatchTrigger, armed from SWUCollectLeavePlayReactions.
// $player is Vindi's controller. No source mzID is threaded: Vindi is only the OBSERVER here, and the
// offer spans every unit in play, so nothing needs resolving relative to him.
function Hmw062WeakenedDefeatTrigger($player): void {
    GiveTokenUpgrade(intval($player), '', [
        'token'        => 'WEAKNESS',
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit',
    ]);
}
