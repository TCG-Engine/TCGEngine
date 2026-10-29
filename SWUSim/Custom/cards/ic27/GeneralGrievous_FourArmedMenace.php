<?php
// IC27_021
// Cost 4 - General Grievous - Four-Armed Menace - [Vigilance,Villainy] - Unit (Ground) 4/5 (unique)
//   Traits: Separatist, Official
// Text: When you play an upgrade on this unit: Choose one that hasn't been chosen this phase:
//       • Give an Experience token to him.  • Heal 3 damage from your base.  • Draw a card.
//
// Collected in CollectWhenPlayedAsUpgradeTriggers (GameLogic) beside ASH_047 Gar Saxon / SHD_067 Fenn Rau: only
// when the player who played the upgrade CONTROLS him ("you play … on this unit") and he keeps his abilities. The
// trigger carries his UniqueID and DispatchTrigger routes it here.
// "hasn't been chosen this phase" is per copy: each chosen mode is a GlobalEffects flag SWU_IC27021_<uid>_<mode> on
// his controller, cleared at RegroupPhaseStart. The flag is set when the mode is CHOSEN, whatever it then manages to
// do. One mode left resolves without a prompt (a one-answer question); none left does nothing.
// Labels are single tokens: Experience / HealBase / Draw.
// Tests: SWUSim/Tests/Cases/ic27/GeneralGrievous_FourArmedMenace.md
function _SWUIc27021Modes(int $player, int $uid): array {
    $out = [];
    foreach (['Experience', 'HealBase', 'Draw'] as $m) {
        if (GlobalEffectCount($player, "SWU_IC27021_{$uid}_{$m}") === 0) $out[] = $m;
    }
    return $out;
}

function _SWUIc27021Resolve(int $player, int $uid, string $mode): void {
    AddGlobalEffects($player, "SWU_IC27021_{$uid}_{$mode}");
    global $playerID; $playerID = $player;
    switch ($mode) {
        case 'Experience':
            $mz = SWUFindMzByUID($uid);
            if ($mz !== null) DoGiveExperienceToken($player, $mz);
            break;
        case 'HealBase':
            OnHealBase($player, $player, 3);
            break;
        case 'Draw':
            DoDrawCard($player, 1);
            break;
    }
}

function _SWUIc27021Reaction(int $player, int $uid): void {
    $modes = _SWUIc27021Modes($player, $uid);
    if (empty($modes)) return;
    if (count($modes) === 1) { _SWUIc27021Resolve($player, $uid, $modes[0]); return; }
    DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', implode('&', $modes), 1,
        tooltip: 'Choose_one_that_hasn\'t_been_chosen_this_phase_(Experience_to_Grievous_/_heal_3_from_your_base_/_draw)');
    DecisionQueueController::AddDecision($player, 'CUSTOM', "IC27_021#0|{$uid}", 1);
}

$customDQHandlers["IC27_021#0"] = function($player, $parts, $lastDecision) {
    $uid = intval($parts[0] ?? 0);
    if (!in_array((string)$lastDecision, _SWUIc27021Modes(intval($player), $uid), true)) return;
    _SWUIc27021Resolve(intval($player), $uid, (string)$lastDecision);
};
