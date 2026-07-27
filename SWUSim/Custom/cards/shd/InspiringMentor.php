<?php
// SHD_104
// Cost 2 - Inspiring Mentor - [Command,Heroism] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / Attached unit gains, "On Attack/When Defeated: Give an Experience token to another friendly unit."

// ─── SHD_104 Inspiring Mentor (granted On Attack / When Defeated) ──────────────
// Attached unit gains: "On Attack/When Defeated: Give an Experience token to another friendly unit."
// The On Attack half fires via the upgrade-granted OnAttack scan ($mzID = host attacker); the When
// Defeated half is collected in CollectWhenDefeatedTriggers' Subcards scan → DispatchTrigger 'SHD_104'.
$onAttackAbilities["SHD_104:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'excludeSelf' => true,
        'prompt' => "Give_an_Experience_token_to_another_friendly_unit",
    ]);
};
