<?php
// SEC_103
// Cost 7 - Mon Mothma - Clinging to Hope - [Command,Heroism] - Power 5 - HP 8
// Text: Restore 3 / When Played: You may attack with any number of other units (one at a time), even if those units are exhausted. They can't attack bases for these attacks.

// SEC_103 Mon Mothma — Restore 3 (auto) + When Played: you may attack with any number of OTHER units
// (one at a time), even if exhausted; they can't attack bases. The loop var SWU_MONMOTHMA_LOOP carries
// the exclude-UID set (Mon Mothma + units that already attacked); _SWUMonMothmaOffer re-offers after each
// attack via the SWU_TRIGGER_RESUME stack-empty branch. No SWUAfterAction here — the play's
// FINISH_PLAY_CARD finalizes the action once the loop ends (mirrors SEC_172's single attack).
$whenPlayedAbilities["SEC_103:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    SetSWUVar('SWU_MONMOTHMA_LOOP', strval($selfUID));   // exclude Mon Mothma herself ("other units")
    _SWUMonMothmaOffer(intval($player));
};
