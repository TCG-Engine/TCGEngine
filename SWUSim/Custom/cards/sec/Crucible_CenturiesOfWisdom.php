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
  foreach (SWUAllUnits('my') as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID)
      DoGiveExperienceToken(intval($player), $mz);
  }
};

$whenPlayedAbilities["SEC_119:0"] = $sec119;

$whenDefeatedAbilities["SEC_119:0"] = $sec119;
