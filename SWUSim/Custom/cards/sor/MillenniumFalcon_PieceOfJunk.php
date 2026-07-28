<?php
// SOR_193
// Cost 3 - Millennium Falcon - Piece of Junk - [Cunning,Heroism] - Power 3 - HP 4
// Text: This unit enters play ready. / When you ready cards during the regroup phase: Either pay [1 resource] or return this unit to her owner's hand.

// Regroup "ready cards" trigger: YES = pay 1 resource to keep her on the board;
// NO (or unable to pay) = return her to her owner's hand. $parts[0] = Falcon mzID.
// If SEC_122 Vuutun Palaa is in play and the player has ready Droids, SWUOfferDroidPayment
// queues the central MZMULTICHOOSE + DROID_PAY chain and returns true (Droid branch taken).
// In that case we must NOT restore $playerID — MZCountChoices resolves the MZMULTICHOOSE
// relative mzIDs under $playerID immediately after this handler returns.
$customDQHandlers["SOR_193#0"] = function($player, $parts, $lastDecision) {
    $falconMz = $parts[0] ?? '';
    if ($falconMz === '') return;
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    if (strtoupper((string)$lastDecision) !== 'YES') {
        // Player declined — bounce regardless of SEC_122.
        _SWUFalconKeepOrBounce(intval($player), $falconMz, false);
        $playerID = $savedPID;
        return;
    }

    // Player said YES. Check if the Droid branch will fire before calling, so we
    // know whether to restore $playerID. SWUOfferDroidPayment sets $playerID=$player
    // in the Droid branch and leaves it set; restoring here would break MZCountChoices.
    $droidBranch = (SWUPlayerControlsSEC122(intval($player))
                    && !empty(SWUReadyFriendlyDroids(intval($player))));
    SWUOfferAltPayment(intval($player), 1, 'FALCON_KEEP', $falconMz, 1);
    if (!$droidBranch) {
        $playerID = $savedPID;
    }
    // Droid branch: $playerID intentionally left = $player (MZCountChoices requirement).
};
