<?php
// JTL_148
// Cost 2 - Frisk - Vanguard Loudmouth - [Aggression,Heroism] - Power 3 - HP 2 - Upgrade Power 2 - Upgrade HP 2
// Text: / Piloting [2 resources Aggression Heroism] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: You may defeat an upgrade that costs 2 or less.

// JTL_148 Frisk (pilot) — Piloting (keyword) + When played as an upgrade: You may defeat an upgrade that
// costs 2 or less. The cost<=2 filter scopes the host enumeration to units bearing a matching upgrade.
$whenPlayedAsUpgradeAbilities["JTL_148:0"] = function($player, $mzID) {
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade_costing_2_or_less",
        may: true, max: 1, filter: 'cost<=2', min: 0);
};
