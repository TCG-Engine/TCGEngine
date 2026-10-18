<?php
// LEVER 'aggroleaderkill' (theme 'aggroanswer', G4-31 kill-the-aggro-leader-unit). A deployed aggro leader is its deck's top damage source
// (Ahsoka Red 21% of its base damage, Splash 20%, Greef 15%) and was killed in 46% of Ahsoka Red's losses vs 17% of its wins; opponents
// attacked into her 0.10-0.35 times a game, and deployed Sloane (Splash) 4 times in 104 games.
// killextra — a kill of a deployed enemy leader unit, while the opponent is an aggro leader, earns for EVERY style the base damage it would
// deal over the next 2 rounds (only where 'leaderkill' does not already pay it: the aggro wing) PLUS the aura it grants each round:
// "Each other friendly unit gets +N" (granted power), an On Attack "+N/+0 for this phase" (Ahsoka's Support), an Advantage token per unit
// played (Greef), "Each other friendly unit gains … Sentinel" (Sloane). A tiny tie-break sends a tie to the leader.
const SWU_BOT_LV_AGGROLEADERKILL_ROUNDS = 2;
const SWU_BOT_LV_AGGROLEADERKILL_ADVANTAGE = 1.5;   // one Advantage a round, as 'enginekill' prices an ally buffer
const SWU_BOT_LV_AGGROLEADERKILL_TIE = 0.05;

// The per-round aura of a leader unit, in base-damage points.
function _SWUBotLvAggroleaderkillAura(array $def): float {
    $text = strval(CardDeployText(strval($def['cardID'])) ?? '');
    $aura = function_exists('_SWUBotAuraGrantedPower') ? floatval(_SWUBotAuraGrantedPower($def)) : 0.0;
    if (preg_match('/On Attack:[^.]*\+(\d+)\/\+0 for this phase/i', $text, $m)) $aura = max($aura, floatval($m[1]));
    if (preg_match('/give an Advantage token/i', $text)) $aura = max($aura, SWU_BOT_LV_AGGROLEADERKILL_ADVANTAGE);
    if (preg_match('/Each other friendly unit gains[^.]*Sentinel/i', $text)) {
        $others = count(array_filter(SWUBotUnits(intval($def['controller'])), fn($u) => $u['uid'] !== $def['uid']));
        $aura = max($aura, 0.5 * $others + 1.0);
    }
    return $aura;
}

SWUBotLeverRegister('aggroanswer', 'aggroleaderkill', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        if (empty($def['isLeader']) || $seat <= 0 || !SWUBotOpponentIsAggroLeader($seat)) return null;
        $add = SWU_BOT_LV_AGGROLEADERKILL_TIE + floatval($W['base']) * _SWUBotLvAggroleaderkillAura($def);
        if (!(SWUBotFeatureOn('leaderkill') && intval($W['archetypeRank'] ?? 0) >= 2))
            $add += SWU_BOT_LV_AGGROLEADERKILL_ROUNDS * floatval($W['base']) * max(0, intval($def['attackPower']));
        return $extra + $add;
    },
]);
