<?php
// LEVER 'attackthenshield' (theme 'order', ASH lever hunt G1-31). Off by default: '@try-attackthenshield' / '@try-order'.
// Deployed ASH_006 Sabine: "On Attack: The next unit you play this phase gains Shielded for this phase." She attacked 251 times;
// a unit was played after her attack in 44 of those rounds, and in 135 the units were played only before it. 'enablerfirst' part
// 3 (_SWUBotAttackEnablerBonus) only knows "enters play ready" / "costs N less" grants, so this one was worth 0.
// With such an attacker ready and a castable unit in hand: (1) the attack carries the Shield's value — priced as the split picker
// prices a Shield (_SWUBotShieldPopValue's rate: chip x the unit's power, x1.5 for a Sentinel) on the best castable unit; (2) a
// unit play waits just below that attack, so the swing goes first and the unit lands Shielded. Not while a grant is already armed.

const SWU_BOT_LV_ATTACKTHENSHIELD_RE = '/On Attack:[^\n]*next unit you play this phase gains Shielded/i';

function _SWUBotLvAttackthenshieldGrants(array $att): bool {
    $cid = strval($att['cardID'] ?? '');
    return (bool)preg_match(SWU_BOT_LV_ATTACKTHENSHIELD_RE, strval(!empty($att['isLeader']) ? CardDeployText($cid) . "\n" . CardText($cid) : CardText($cid)));
}
function _SWUBotLvAttackthenshieldArmed(int $seat): bool {
    return GlobalEffectCount($seat, 'SWU_ASH006_SHIELDED_NEXT') > 0;
}
// The Shield's worth on the best unit castable from the actions on offer, or 0.
function _SWUBotLvAttackthenshieldShield(array $ctx, int $seat, array $W): float {
    $best = 0.0;
    foreach ((array)($ctx['actions'] ?? []) as $a) {
        if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
        $cid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($cid)), 'Unit') || preg_match('/(^|\n)\s*Shielded\b/i', strval(CardText($cid)))) continue;
        $best = max($best, floatval($W['chip']) * max(1, intval(CardPower($cid))) * (_SWUBotHasPrintedSentinel($cid) ? 1.5 : 1.0));
    }
    return $best;
}

SWUBotLeverRegister('order', 'attackthenshield', [
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (!_SWUBotLvAttackthenshieldGrants($att) || _SWUBotLvAttackthenshieldArmed($seat)) return null;
        $sh = _SWUBotLvAttackthenshieldShield($ctx, $seat, $W);
        return $sh > 0.0 ? $s + $sh : null;
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvAttackthenshieldDepth']) || !str_contains(strval(CardType($cid)), 'Unit') || _SWUBotLvAttackthenshieldArmed($seat)) return null;
        $GLOBALS['SWUBotLvAttackthenshieldDepth'] = 1;
        try {
            $best = null;
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) !== 'attack') continue;
                $att = SWUBotViewForMz($seat, SWUBotActionMz($a));
                if ($att === null || empty($att['ready']) || !_SWUBotLvAttackthenshieldGrants($att)) continue;
                $s = SWUBotScoreAction($ctx, $a, intval($i));
                if ($s > 0.0) $best = max($best ?? $s, $s);
            }
        } finally { $GLOBALS['SWUBotLvAttackthenshieldDepth'] = 0; }
        if ($best === null || $v < $best - 0.01) return null;
        return $best - 0.01;
    },
]);
