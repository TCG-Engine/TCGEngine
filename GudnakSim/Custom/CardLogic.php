<?php

/**
 * Card-specific logic for special abilities and effects
 * This file handles individual card mechanics that don't fit into standard game flow
 */

/**
 * Get additional attack targets based on card-specific abilities
 * Returns an array of zone names that can be attacked due to card effects
 */
function GetCardSpecificAttackTargets($player, $cardZone, $cardID, $includeAttack, $includeDiagonals) {
    $additionalTargets = [];
    
    switch($cardID) {
        case "DNBF3HNLKS": // Nihl'othrakis - can attack enemy fighters adjacent to ANY friendly fighter
            if(!$includeAttack) break;
            
            $allBattlefields = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
            foreach($allBattlefields as $bf) {
                $bfTop = GetTopCard($bf);
                if($bfTop !== null && $bfTop->Controller == $player && $bf !== $cardZone) {
                    $adjacentToBF = AdjacentZones($bf, $includeDiagonals);
                    foreach($adjacentToBF as $zone) {
                        $zoneArr = &GetZone($zone);
                        if(count($zoneArr) > 1) {
                            $defenderCard = $zoneArr[count($zoneArr) - 1];
                            if($defenderCard->Controller != $player) {
                                if(CanAttack($player, $cardZone, $zone)) {
                                    if(!in_array($zone, $additionalTargets)) {
                                        array_push($additionalTargets, $zone);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            break;
        
        default:
            break;
    }
    
    return $additionalTargets;
}

?>
