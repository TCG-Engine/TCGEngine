<?php
// HMW_127
// Cost 3 - Chewbacca's Bowcaster - [Command][Heroism] - Upgrade (+3/+1) - Traits: Item, Weapon - Unique
// Text: Attach to a non-Vehicle unit.
//       When Played: If attached unit is Chewbacca, resource the top card of your deck. (It enters play
//       exhausted.)
//
// Attach restriction ("non-Vehicle unit") is handled by HMW_127's case in SWUGetUpgradeValidTargets
// (GameLogic.php). A non-pilot upgrade's WhenPlayed fires through CollectWhenPlayedAsUpgradeTriggers'
// fallback, which passes the HOST unit's mzID as $mzID (the SOR_136 Vader's Lightsaber shape).
$whenPlayedAbilities["HMW_127:0"] = function($player, $mzID = '') {
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (CardTitle($host->CardID ?? '') !== 'Chewbacca') return;   // matches ANY Chewbacca (title, not CardID)
    if (count(GetDeck($player)) === 0) return;                    // empty deck → nothing to resource
    // "resource the top card of your deck (It enters play exhausted.)" — the default ramp entry status.
    SWURampResourceExhausted($player, 'myDeck-0');
};
