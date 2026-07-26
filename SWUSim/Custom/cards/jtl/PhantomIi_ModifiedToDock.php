<?php
// JTL_050
// Cost 3 - Phantom II - Modified to Dock - [Vigilance,Heroism] - Power 3 - HP 3
// Text: Grit / Action [1 resource]: If this card is a unit, attach it as an upgrade to The Ghost. (It's no longer a unit. Defeat all upgrades on it and remove all damage from it.) / Attached unit gets +3/+3 and gains Grit.

// JTL_050 Phantom II — "Action [1 resource]: If this card is a unit, attach it as an upgrade to The Ghost.
// (It's no longer a unit. Defeat all upgrades on it and remove all damage from it.)" costKind 'none'
// (resource-only, no exhaust). Reuses SWUMoveUnitToUpgrade; the +3/+3 + Grit grant lives in
// ObjectCurrentPower/HP + the Grit conditional. "The Ghost" is TITLE-based — any unit titled "The Ghost"
// on EITHER player's side is a legal host (JTL_053, SOR_050, …), so when >1 is in play the player picks.
// SWUMoveUnitToUpgrade handles the "defeat all upgrades on it and remove all damage" rider. Affordability
// (SWUUnitActionAffordable) gates on any "The Ghost" being in play.
$unitActionCostKind["JTL_050"] = 'none';

$unitActionResourceCosts["JTL_050"] = 1;

$unitAbilities["JTL_050"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $hosts   = _SWUCollectUnits($selfUID, fn($o) => CardTitle($o->CardID ?? '') === 'The Ghost');
    if (empty($hosts)) { SWUAfterAction($player); return; }
    // 1 host → auto-attach (PASSPARAMETER, no prompt); 2+ → MZCHOOSE. JTL_050#1 finalizes the attach
    // (and the terminal SWUAfterAction) on the chosen host — the Phantom's own mzID rides in the token.
    SWUQueueChooseTarget(intval($player), $hosts,
        'Choose_a_The_Ghost_to_attach_Phantom_II_to', 'JTL_050#1|' . (string)$mzID);
};

// JTL_050#1 — receives the chosen host mzID as $lastDecision; Phantom II's own mzID rides in $parts[0].
// Attaches Phantom (as a special upgrade, not a Pilot) to the chosen "The Ghost", then ends the action.
$customDQHandlers["JTL_050#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID  = intval($player);
    $phantomMz = $parts[0] ?? '';
    $hostMz    = $lastDecision ?? '';
    if ($phantomMz !== '' && $hostMz !== '' && $hostMz !== '-') {
        SWUMoveUnitToUpgrade($phantomMz, $hostMz, false); // Phantom II is a special upgrade, not a Pilot
    }
    SWUAfterAction(intval($player));
};
