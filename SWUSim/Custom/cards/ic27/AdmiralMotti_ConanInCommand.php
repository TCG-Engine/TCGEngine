<?php
// IC27_062
// Cost 4 - Admiral Motti - Conan In Command - [Command,Villainy] - Unit (Ground) 2/6 (unique)
//   Traits: Imperial, Official
// Text: Hidden. On Attack: Give an Experience token to each other friendly Imperial unit.
//
// Hidden is printed (auto-wired). SEC_119 Crucible's loop: "friendly" spans the TEAM (SWUFriendlyUnits), "other"
// is by UniqueID, "Imperial" by TraitContains (object-aware). No choice, so nothing is queued.
// Tests: SWUSim/Tests/Cases/ic27/AdmiralMotti_ConanInCommand.md
$onAttackAbilities["IC27_062:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $selfUID = SWUObjUID(GetZoneObject($mzID), 0);
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $selfUID) continue;
        if (!TraitContains($o, 'Imperial')) continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};
