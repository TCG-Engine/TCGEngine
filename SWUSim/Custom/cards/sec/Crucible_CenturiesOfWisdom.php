<?php
// SEC_119
// Cost 6 - Crucible - Centuries of Wisdom - [Command] - Power 5 - HP 5
// Text: When Played/When Defeated: Give an Experience token to each other friendly unit.

// SEC_119 Crucible — When Played / When Defeated: give an Experience token to each OTHER friendly unit.
$sec119 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $self = GetZoneObject($mzID);
  $selfUID = SWUObjUID($self, 0);
  // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
  // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
  // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
  // Premier is byte-identical. See memory: unqualified pools miss teammates.
  foreach (SWUFriendlyUnits() as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID)
      DoGiveExperienceToken(intval($player), $mz);
  }
};

$whenPlayedAbilities["SEC_119:0"] = $sec119;

$whenDefeatedAbilities["SEC_119:0"] = $sec119;
