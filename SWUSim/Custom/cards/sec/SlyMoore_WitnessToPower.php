<?php
// SEC_033
// Cost 4 - Sly Moore - Witness to Power - [Vigilance,Villainy] - Power 2 - HP 6
// Text: When Played: For this phase, each enemy unit gets -2/-0 while it's attacking a base. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_033 Sly Moore — When Played: for this phase, each enemy unit gets -2/-0 while attacking a base.
// Mark each enemy in play now; the -2 is applied in SWUCombatDamage when that unit attacks a base. (Plot auto.)
// The effect is CONTINUOUS for the phase, not a stamp on the units that happened to be enemies when it
// resolved: it must catch units played later and units that only BECOME enemies later (a control change).
// So arm one phase flag on Sly Moore's controller and evaluate "is this attacker an enemy of that player"
// at combat time. Cleared at RegroupPhaseStart with the other phase flags.
$whenPlayedAbilities["SEC_033:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    AddGlobalEffects(intval($player), 'SWU_SEC033');
};

// True if $attacker is an enemy of some player who has an active Sly Moore phase flag.
function _SWUSlyMooreDebuffs($attacker): bool {
    if ($attacker === null) return false;
    $ctrl = intval($attacker->Controller ?? 0);
    if ($ctrl <= 0) return false;
    for ($p = 1; $p <= SeatCountForGame(); $p++) {
        if ($p === $ctrl) continue;                       // "each ENEMY unit" — never its own controller's
        if (GlobalEffectCount($p, 'SWU_SEC033') > 0) return true;
    }
    return false;
}
