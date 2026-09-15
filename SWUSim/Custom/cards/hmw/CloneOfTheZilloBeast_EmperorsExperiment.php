<?php
// HMW_065
// Cost 4 - Clone of the Zillo Beast, Emperor's Experiment - [Vigilance][Villainy] - Unit (Ground) 6/6 - Unique
// Traits: Clone, Creature
// Text: Other friendly units get -2/-2.
//       On Attack: You may give a Weakness token to a unit.

// ── "Other friendly units get -2/-2." ────────────────────────────────────────────────────────────────
// A continuous aura, the mirror of SHD_037 Snoke's "each enemy non-leader unit gets -2/-2": recomputed on
// every read by ObjectCurrentPower / ObjectCurrentHP (GameLogic.php, beside the Snoke line), so it follows
// control changes and ends the moment the source leaves play or loses its abilities. It is HP REDUCTION,
// not damage — a unit reduced to 0 remaining HP is defeated by the state check (SWUCheckShrinkDefeats),
// which every entry route (play, token creation, take-control) already runs.
//   • "friendly" = the source's TEAM (controller + teammates) — Team Suns; the controller alone elsewhere.
//   • "Other" is by IDENTITY (UniqueID): a teammate's copy shrinks this copy, and two copies stack.
//   • No non-leader qualifier: deployed leader units shrink too.
// Returns how many active copies apply to $obj (each is -2/-2).
function _SWUHmw065AuraCount($obj): int {
    if (SWUObjGone($obj)) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $selfUID = intval($obj->UniqueID ?? 0);
    $count = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
        foreach (GetUnitsInPlay($seat) as $u) {
            if (($u->CardID ?? '') !== 'HMW_065' || !empty($u->removed)) continue;
            if (intval($u->UniqueID ?? 0) === $selfUID) continue;
            if (LostAbilities($u)) continue;
            $count++;
        }
    }
    return $count;
}

// ── "On Attack: You may give a Weakness token to a unit." ────────────────────────────────────────────
// Unqualified "a unit" → any unit on either side, itself included (HMW_003 Doctor Hemlock's deployed
// On Attack, word for word). MAY-choose, so a lone legal target is still declinable; GIVE_WEAKNESS attaches
// HMW_T02 and runs the shrink sweep.
$onAttackAbilities["HMW_065:0"] = function ($player, $mzID = '') {
    GiveTokenUpgrade(intval($player), $mzID, [
        'token'        => 'WEAKNESS',
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit',
    ]);
};
