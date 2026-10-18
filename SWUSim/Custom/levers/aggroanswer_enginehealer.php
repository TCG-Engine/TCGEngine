<?php
// LEVER 'enginehealer' (theme 'aggroanswer', G2-28 enginekill-counts-healers). 'enginekill' (p44) prices a unit that pays its controller every
// round, but its text reader (SWUBotEngineValueFromText) matches only "Restore N" and On Attack base damage — and skips leader units. Luke ASH
// heals 6.3 base a game and wins 66-68% vs control in 9.7-round games (real cells 31-56%).
// killextra — the base heal the enemy unit makes each round, in base-damage points, beyond what 'enginekill' already counted:
//   "When a friendly unit's attack ends: Heal N … your base" (Luke ASH deployed) → N per friendly attacker, capped at 4;
//   "When Attack Ends: If this unit dealt combat damage to a base, heal that much" (Hera ASH_031) → its attack power;
//   "When an enemy unit is defeated: Heal N damage from your base" (Chimaera ASH_052) → N;
//   "Each other friendly Jedi unit gains Restore N" (Yaddle) → N per other Jedi unit.
// A leader unit is read on its deployed text.
const SWU_BOT_LV_ENGINEHEALER_CAP = 4.0;

function _SWUBotLvEnginehealerHeal(array $def): float {
    $cid = strval($def['cardID']);
    $text = !empty($def['isLeader']) && strval(CardDeployText($cid) ?? '') !== '' ? strval(CardDeployText($cid)) : strval(CardText($cid));
    $ctl = intval($def['controller']);
    $h = 0.0;
    if (preg_match("/When a friendly unit's attack ends:[^.]*?\\bHeal (\\d+) damage[^.]*\\bbase/i", $text, $m)) {
        $h = max($h, min(SWU_BOT_LV_ENGINEHEALER_CAP, floatval($m[1]) * count(SWUBotUnits($ctl))));
    }
    if (preg_match('/When Attack Ends: If this unit dealt combat damage to a base, heal that much damage from your base/i', $text)) $h = max($h, floatval(max(0, intval($def['attackPower']))));
    if (preg_match('/When an enemy unit is defeated: Heal (\d+) damage from your base/i', $text, $m)) $h = max($h, floatval($m[1]));
    if (preg_match('/Each other friendly (\w+) unit gains Restore (\d+)/i', $text, $m)) {
        $n = 0;
        foreach (SWUBotUnits($ctl) as $u) if ($u['uid'] !== $def['uid'] && str_contains(strval(CardTrait(strval($u['cardID'])) ?? ''), $m[1])) $n++;
        $h = max($h, floatval($m[2]) * $n);
    }
    return $h;
}

SWUBotLeverRegister('aggroanswer', 'enginehealer', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        $h = _SWUBotLvEnginehealerHeal($def);
        if ($h <= 0.0) return null;
        $had = (SWUBotFeatureOn('enginekill') && empty($def['isLeader'])) ? SWUBotEngineValue(strval($def['cardID'] ?? '')) : 0.0;
        if ($h <= $had) return null;
        return $extra + floatval($W['base']) * ($h - $had);
    },
]);
