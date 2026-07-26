<?php
// JTL_200
// Cost 3 - Shuttle Tydirium - Fly Casual - [Cunning,Heroism] - Power 2 - HP 4
// Text: On Attack: Discard a card from your deck. If it has an odd cost, you may give an Experience token to another unit.

// ── JTL_200 Shuttle Tydirium — On Attack: Discard a card from your deck. If it has an odd cost, you may
// give an Experience token to another unit. ──────────────────────────────────────────────────────────
$onAttackAbilities["JTL_200:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;
    if (intval(CardCost($milled)) % 2 === 0) return; // even cost → no Experience
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUid) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_an_Experience_to_another_unit", "Give_an_Experience_token", "GIVE_EXPERIENCE|1");
};
