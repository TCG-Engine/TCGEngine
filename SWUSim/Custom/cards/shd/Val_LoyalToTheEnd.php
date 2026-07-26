<?php
// SHD_058
// Cost 2 - Val - Loyal to the End - [Vigilance] - Power 2 - HP 4
// Text: Bounty - Deal 3 damage to a unit. / When Defeated: Give 2 Experience tokens to a friendly unit. (The active player chooses the order of Val's abilities.)

// ─── SHD_058 Val ──────────────────────────────────────────────────────────────
// When Defeated: Give 2 Experience tokens to a friendly unit. (Her "Bounty — Deal 3 damage to a
// unit" is the SWUCollectBounty case in GameLogic.php.) Runs under Val's controller; fizzles when
// no friendly unit survives her.
$whenDefeatedAbilities["SHD_058:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_2_Experience_tokens_to_a_friendly_unit", "GIVE_EXPERIENCE|2");
};
