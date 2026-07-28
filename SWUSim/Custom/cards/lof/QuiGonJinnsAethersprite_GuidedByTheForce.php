<?php
// LOF_197
// Cost 5 - Qui-Gon Jinn's Aethersprite - Guided by the Force - [Cunning,Heroism] - Power 3 - HP 6
// Text: On Attack: The next time you use a "When Played" ability this phase, you may use that ability again.

// LOF_197 Qui-Gon Jinn's Aethersprite — On Attack: arm "the next When-Played ability you use this phase
// may be used again" (resolved in OnWhenPlayed; cleared at RegroupPhaseStart).
$onAttackAbilities["LOF_197:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_LOF197_REPEAT');
};
