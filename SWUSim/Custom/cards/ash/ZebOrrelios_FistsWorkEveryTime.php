<?php
// ASH_161
// Cost 7 - Zeb Orrelios - Fists Work Every Time - [Aggression,Heroism] - Power 5 - HP 7
// Text: When Played: Give 3 Advantage tokens to another unit. / When a friendly upgrade is defeated: Deal 1 damage to a base.

// ASH_161 Zeb Orrelios — When Played: give 3 Advantage tokens to ANOTHER unit. (The reactive "when a
// friendly upgrade is defeated: deal 1 to a base" half is handled by _SWUOnUpgradeDefeated.)
$whenPlayedAbilities["ASH_161:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'token' => 'ADVANTAGE', 'amount' => 3, 'excludeSelf' => true, 'friendlyOnly' => false,
        'prompt' => "Give_3_Advantage_tokens_to_another_unit",
    ]);
};
