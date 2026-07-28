<?php
// TWI_017
// Chancellor Palpatine - Playing Both Sides - [Cunning,Villainy,Heroism]
// Text: This leader starts the game with this side faceup. / Action [Exhaust]: If a friendly Heroism unit was defeated this phase, draw a card, heal 2 damage from your base, then flip this leader.
// DeployText: Action [Exhaust]: If you played a Villainy card this phase, create a Clone Trooper token, deal 2 damage to each enemy base, then flip this leader.

// TWI_017 Chancellor Palpatine "Flipatine" — a double-leader-face FLIP card with no unit side. Its
// Deployed flag is repurposed as the FACE bit: false = Heroism face (Cunning+Heroism), true = flipped
// Villainy face (Cunning+Villainy); flipping never creates an arena unit. Both faces are Action [Exhaust]
// abilities; SWULeaderAction already exhausted the leader before this closure runs, and the flip leaves it
// exhausted (ruling). Ruling: the Action is always usable when ready — if the face's condition isn't met,
// NONE of the listed effects resolve (not even the flip); the leader is simply spent.
$leaderAbilities["TWI_017"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $leaderArr = &GetLeader($player);
    $lead = null;
    for ($i = 0; $i < count($leaderArr); $i++) {
        if (empty($leaderArr[$i]->removed) && ($leaderArr[$i]->CardID ?? '') === 'TWI_017') { $lead = &$leaderArr[$i]; break; }
    }
    if ($lead === null) { SWUAfterAction($player); return; }
    if (empty($lead->Deployed)) {
        // HEROISM face: if a friendly Heroism unit was defeated this phase → draw 1, heal 2 from your base, flip.
        if (GlobalEffectCount($player, 'SWU_FRIENDLY_HEROISM_DEFEATED') > 0) {
            DoDrawCard($player, 1);
            OnHealBase($player, $player, 2);
            $lead->Deployed = true;  // flip to the Villainy face
        }
    } else {
        // VILLAINY face: if you played a Villainy card this phase → create a Clone Trooper, deal 2 to each enemy base, flip.
        if (GlobalEffectCount($player, 'SWU_PLAYED_VILLAINY') > 0) {
            SWUCreateUnitToken($player, 'TWI_T02');                 // Clone Trooper token
            SWUDealDamageToBase(2, OtherPlayer($player));           // 2-player: the single enemy base
            $lead->Deployed = false; // flip back to the Heroism face
        }
    }
    SWUAfterAction($player);
};
