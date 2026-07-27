<?php
// JTL_086
// Cost 3 - Wingman Victor Three - Backstabber - [Command,Villainy] - Power 4 - HP 3 - Upgrade Power 1 - Upgrade HP 1
// Text: / Piloting [1 resource Command Villainy] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: You may give an Experience token to another unit.

// JTL_086 Wingman Victor Three (pilot) — When played as an upgrade: You may give an Experience token to
// another unit.
$whenPlayedAsUpgradeAbilities["JTL_086:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'friendlyOnly' => false, 'excludeSelf' => true, 'may' => true,
        'question' => "Give_an_Experience_token_to_another_unit",
        'prompt'   => "Choose_a_unit",
    ]);
};
