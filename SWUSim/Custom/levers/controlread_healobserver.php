<?php
// LEVER 'healobserver' (theme 'controlread', report OG2). Chimaera ASH_052 "When an enemy unit is defeated: Heal 2 damage from your base":
// every unit of mine that dies heals its controller. Ninin's Chimaera healed 8-24 a game off fed deaths (cdfd 12, 5536 12, 99cd ~24).
// 'observertax' (p41) priced only the HK-47 "Deal N damage to its controller's base" observer. A heal is base damage undone — priced
// the same, capped by the damage their base actually has (healing an undamaged base is nothing).

function _SWUBotLvHealObserverHeal(int $victim): int {
    $n = 0;
    foreach (OpponentsOf($victim) as $o) {
        $o = intval($o);
        $heal = 0;
        foreach (SWUBotUnits($o) as $u) {
            if (function_exists('LostAbilities') && LostAbilities($u['obj'])) continue;
            if (preg_match('/When an enemy unit is defeated: Heal (\d+) damage from your base/i', strval(CardText($u['cardID'])), $m)) $heal += intval($m[1]);
        }
        if ($heal <= 0) continue;
        $base = strval((GetBase($o)[0] ?? null)->CardID ?? '');
        $damage = max(0, intval(CardHp($base)) - SWUBaseRemainingHp($o));
        $n += min($heal, $damage);
    }
    return $n;
}

SWUBotLeverRegister('controlread', 'healobserver', [
    'observertax' => function (float $tax, array $att, array $W): ?float {
        $heal = _SWUBotLvHealObserverHeal(intval($att['controller']));
        return $heal > 0 ? $tax + floatval($W['base']) * $heal : null;
    },
]);
