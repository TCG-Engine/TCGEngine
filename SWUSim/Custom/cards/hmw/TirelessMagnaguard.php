<?php
// HMW_109
// Cost 4 - Tireless Magnaguard - [Command,Villainy] - Power 5 - HP 3 - Separatist, Droid
// Text: When Defeated: If this unit had 5 or more power, for this phase you may play this unit from your discard pile for free and give 2 Weakness tokens to it.

// HMW_109 Tireless Magnaguard — the fourth member of the POWER-AT-DEFEAT family (SEC_035 Darth Sion,
// ASH_195 Helgait, JTL_104 Raddus). "HAD 5 or more power" is measured at the instant of defeat, which is
// NOT what the live object reads by the time this closure runs:
//   • its subcards have been stripped, so Weakness (-1/-1) and Advantage (+1/+0) are gone;
//   • RAID never was a stat modifier — CR 8.8.c gives the bonus for the duration of the attack only.
// Both are handled by the snapshot taken in CollectWhenDefeatedTriggers (GameLogic), which reads the
// object while its subcards are intact and takes the max with the attacker's stashed ATTACK power.
// See that block for the two user rulings this encodes (Raid counts; Advantage is still attached because
// the When Defeated resolves before the attack-ends window that sheds it).
//
// The permission itself needs no new machinery: 'TPF' is the discard modifier for "the owner may play
// this from their discard, free, this phase" (the LOF_117 Sifo-Dyas seam), and SWUClearDiscardModifiers
// wipes it at RegroupPhaseStart, which IS the "for this phase" expiry. The player then takes the
// ordinary PlayFromDiscard action — that action being optional is what makes the printed "you may" true,
// so there is no prompt to raise here.
//
// ⚠ "YOUR discard pile" is the ABILITY CONTROLLER's. A defeated unit goes to its OWNER's discard, so a
// stolen Magnaguard grants its controller nothing — the card simply is not in their pile. That is the
// documented ownership-vs-control split, and it is the opposite of SEC_035, whose text says "his OWNER's
// hand". Pinned by StolenMagnaguard_LandsInTheOWNERSDiscard_NoPermission.
$whenDefeatedAbilities["HMW_109:0"] = function($player, $mzID) {
    global $playerID, $gHmw109DefeatSnapshot;
    $snap = $gHmw109DefeatSnapshot[$mzID] ?? null;
    if ($snap === null) {
        // The persisted twin. The global dies with the request; a Thrawn / Shadow-Caster reuse answers
        // its YESNO in a later one, and without this the permission would silently not be granted.
        $k  = 'SWU_HMW109_' . str_replace('-', '_', $mzID);
        $pw = intval(GetSWUVar($k . '_PWR', '0'));
        $ct = intval(GetSWUVar($k . '_CTL', '0'));
        if ($pw > 0 && $ct > 0) $snap = ['power' => $pw, 'controller' => $ct];
    }
    if ($snap === null || intval($snap['power']) < 5) return;

    $ctrl = intval($snap['controller']);
    if ($ctrl <= 0) return;
    $playerID = $ctrl;
    // Stamp the copy that just died, in the CONTROLLER's own discard. A second Magnaguard already sitting
    // in that pile is a different card and must not become playable, so the scan takes the LAST live
    // entry — SWUAddToDiscard appends, so that is the one this defeat just put there.
    $discard = &GetDiscard($ctrl);
    $target  = -1;
    for ($i = 0; $i < count($discard); $i++) {
        if (!empty($discard[$i]->removed)) continue;
        if (($discard[$i]->CardID ?? '') === 'HMW_109') $target = $i;
    }
    if ($target === -1) return;                       // not in THIS player's discard (stolen unit)
    $discard[$target]->Modifier = 'TPF';              // "play from your discard, for free, this phase"
    AddGlobalEffects($ctrl, 'SWU_HMW109_WEAKEN');     // the "and give 2 Weakness tokens to it" rider
};
