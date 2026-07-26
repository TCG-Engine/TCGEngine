<?php
// SOR_007
// Cost 5 - Grand Moff Tarkin - Oversector Governor - [Command,Villainy] - Power 2 - HP 7
// Text: Action [1 resource, exhaust]: Give an Experience token to an Imperial unit.
// DeployText: On Attack: You may give an Experience token to another Imperial unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// SOR_007 Grand Moff Tarkin — deployed leader unit On Attack: You may give an Experience token
// to ANOTHER Imperial unit. $mzID = the attacking Tarkin leader-unit's mzID (excluded by UID).
$onAttackAbilities["SOR_007:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits($selfUID, fn($o) => HasTrait($o->CardID, 'Imperial')),
        'Give_an_Experience_token_to_another_Imperial_unit?', 'Choose_an_Imperial_unit_for_an_Experience_token', 'GIVE_EXPERIENCE|1');
};

// SOR_007 Grand Moff Tarkin — Leader Action [1 resource, exhaust]: Give an Experience token
// to an Imperial unit. (Framework exhausts the leader + gates affordability; closure pays the
// resource, like SOR_006.)
$leaderAbilities["SOR_007"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = array_values(array_filter(SWUAllUnits('my'), fn($mz) => HasTrait(GetZoneObject($mz)->CardID ?? '', 'Imperial')));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, 'Give_an_Experience_token_to_an_Imperial_unit', 'GIVE_EXPERIENCE|1');
    SWUQueueAfterAction($player);
};
