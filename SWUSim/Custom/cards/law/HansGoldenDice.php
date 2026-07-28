<?php
// LAW_225
// Cost 1 - Han's Golden Dice - [Cunning,Heroism] - Upgrade Power 0 - Upgrade HP 0
// Text: Attached unit gains: "On Attack: Discard a card from your deck. If its cost is odd, create a Credit token."

// LAW_225 Han's Golden Dice — granted "On Attack: Discard a card from your deck. If its cost is odd,
// create a Credit token." (OnAttackFromUpgrade seam.)
$onAttackAbilities["LAW_225:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $cid = SWUMillTopCard(intval($player));
    if ($cid !== null && (intval(CardCost($cid)) % 2) === 1) SWUCreateCreditToken(intval($player), 1);
};
