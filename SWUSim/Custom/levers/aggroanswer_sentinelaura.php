<?php
// LEVER 'sentinelaura' (theme 'aggroanswer', G4-41 symmetric-sentinel-opponent-awareness). Deployed Sloane (ASH_007: "Each other friendly unit
// gains Overwhelm and Sentinel") was attacked 4 times in 104 Splash games — she hides behind her own Sentinels — and Sloane Splash wins vs
// every style (+46 / +31 / +31). With Gallius Rax (ASH_100: "Other friendly units with 2 or more different keywords get +2/+2") every unit
// has 2 keywords. 'enginekill' lists Sentinel STRIPS and ally +N/+N buffs, never Sentinel GRANTS, and skips leader units.
// killextra — a kill of the aura source, once 3+ other enemy units carry its grant, earns its per-round value in base-damage points:
//   a Sentinel grant → 2 (as 'enginekill' prices a Sentinel strip) + 0.5 per unit it walls with;
//   a keyword-count buff → N per other enemy unit with 2+ keywords.
const SWU_BOT_LV_SENTINELAURA_MIN = 3;

// The number of keywords a unit view carries (the flags the view reads live, plus Raid / Restore / Ambush / Shielded in its text).
function _SWUBotLvSentinelauraKeywords(array $u): int {
    $n = (!empty($u['sentinel']) ? 1 : 0) + (!empty($u['saboteur']) ? 1 : 0) + (!empty($u['overwhelm']) ? 1 : 0) + (!empty($u['grit']) ? 1 : 0);
    $t = strval(CardText(strval($u['cardID'])));
    foreach (['Raid', 'Restore', 'Ambush', 'Shielded', 'Hidden'] as $k) if (preg_match('/(^|\n)\s*' . $k . '\b/', $t)) $n++;
    return $n;
}
function _SWUBotLvSentinelauraValue(array $def): float {
    $cid = strval($def['cardID']);
    $text = !empty($def['isLeader']) && strval(CardDeployText($cid) ?? '') !== '' ? strval(CardDeployText($cid)) : strval(CardText($cid));
    $others = array_values(array_filter(SWUBotUnits(intval($def['controller'])), fn($u) => $u['uid'] !== $def['uid']));
    $v = 0.0;
    if (preg_match('/Each other friendly unit gains[^.]*\bSentinel\b/i', $text)) {
        $walls = count(array_filter($others, fn($u) => !empty($u['sentinel'])));
        if ($walls >= SWU_BOT_LV_SENTINELAURA_MIN) $v = max($v, 2.0 + 0.5 * $walls);
    }
    if (preg_match('/Other friendly units with (\d+) or more different keywords get \+(\d+)\/\+\d+/i', $text, $m)) {
        $hit = count(array_filter($others, fn($u) => _SWUBotLvSentinelauraKeywords($u) >= intval($m[1])));
        if ($hit >= SWU_BOT_LV_SENTINELAURA_MIN) $v = max($v, floatval($m[2]) * $hit);
    }
    return $v;
}

SWUBotLeverRegister('aggroanswer', 'sentinelaura', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        $v = _SWUBotLvSentinelauraValue($def);
        // 'enginekill' already reads Gallius's "+2/+2" as an ally buffer (1.5): only the rest is added.
        $had = (SWUBotFeatureOn('enginekill') && empty($def['isLeader'])) ? SWUBotEngineValue(strval($def['cardID'] ?? '')) : 0.0;
        return $v > $had ? $extra + floatval($W['base']) * ($v - $had) : null;
    },
]);
