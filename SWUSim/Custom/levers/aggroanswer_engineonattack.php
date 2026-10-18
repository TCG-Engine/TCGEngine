<?php
// LEVER 'engineonattack' (theme 'aggroanswer', G2-29 enginekill-counts-on-attack-damage-ships). Ships whose On Attack deals damage are left
// alive: TIE Bomber was attacked by enemy units 0.06 times per copy played while it attacked 1.72 times; Red Squadron Y-Wing 0.07 / 1.36;
// Red Five 0.03 / 1.42; Black One 0.10 / 1.70. The 'enginekill' regex needs "deal N damage to … base".
// killextra — beyond what 'enginekill' counted: "On Attack: Deal N indirect damage to the defending player / a player" → N a round;
// "On Attack: … deal N damage to a (damaged) unit" → 0.5 N (it removes my units, not my base). A leader unit is read on its deployed text.

function _SWUBotLvEngineonattackValue(array $def): float {
    $cid = strval($def['cardID']);
    $text = !empty($def['isLeader']) && strval(CardDeployText($cid) ?? '') !== '' ? strval(CardDeployText($cid)) : strval(CardText($cid));
    $v = 0.0;
    if (preg_match('/On Attack:[^.]*?\bDeal (\d+) indirect damage to (?:the defending player|a player|each opponent)/i', $text, $m)) $v = max($v, floatval($m[1]));
    if (preg_match('/On Attack:[^.]*?\bdeal (\d+) damage to an? (?:damaged |enemy )?(?:ground |space )?unit/i', $text, $m)) $v = max($v, 0.5 * floatval($m[1]));
    return $v;
}

SWUBotLeverRegister('aggroanswer', 'engineonattack', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        $v = _SWUBotLvEngineonattackValue($def);
        if ($v <= 0.0) return null;
        $had = (SWUBotFeatureOn('enginekill') && empty($def['isLeader'])) ? SWUBotEngineValue(strval($def['cardID'] ?? '')) : 0.0;
        if ($v <= $had) return null;
        return $extra + floatval($W['base']) * ($v - $had);
    },
]);
