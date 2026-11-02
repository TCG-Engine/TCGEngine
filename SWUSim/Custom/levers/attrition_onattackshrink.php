<?php
// LEVER 'onattackshrink' (theme 'attrition'). Ninin vs Boba Fett (JTL_009) R5: Hemlock's Action put a Weakness on Cinta Kaz (5/5 → 4/4), he
// deployed, and his deployed "On Attack: You may give a Weakness token to a unit" made her a 3/3 before damage — his 3 power killed her and he
// took 3. The replay bot did the Action and the deploy, then priced the attack against the 4/4 (no kill, 1.35) and hit the base (1.80) —
// still landing the On Attack token on Cinta. An attacker's own unconditional On Attack shrink resolves BEFORE combat damage, so the
// defender is now priced as it will be when damage is dealt:
//   "give a/an [enemy] unit a Weakness token" (-1/-1, HMW_003 deployed) · "give a/an [enemy] unit -P/-H" (LOF_002 Talzin deployed -1/-1,
//   TWI_063 -1/-1; the -N/-0 family: Corona Four, Jango Fett, Padmé — the defender hits back softer). Conditional ones ("If …") are left out.
// The token can kill outright (remaining <= H): priced as a kill the attacker survives. Leaders read their deployed text.
function _SWUBotLvOnAttackShrink(array $att): ?array {
    $cid = strval($att['cardID'] ?? '');
    $t = !empty($att['isLeader']) ? strval($GLOBALS['deployTextData'][$cid] ?? '') : strval(CardText($cid));
    if ($t === '' || (function_exists('LostAbilities') && isset($att['obj']) && LostAbilities($att['obj']))) return null;
    if (preg_match('/On Attack: (?:You may )?give (?:a|an|an enemy) unit a Weakness token|On Attack: (?:You may )?give a Weakness token to (?:a|an|an enemy) unit/i', $t))
        return ['p' => 1, 'h' => 1];
    if (preg_match('/On Attack: (?:You may )?give (?:a|an|an enemy) unit -(\d+)\/-(\d+)/i', $t, $m)) return ['p' => intval($m[1]), 'h' => intval($m[2])];
    return null;
}

SWUBotLeverRegister('attrition', 'onattackshrink', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null || ($sh = _SWUBotLvOnAttackShrink($att)) === null) return null;
        $seat = intval($att['controller'] ?? 0);
        if ($seat <= 0 || !in_array(intval($def['controller'] ?? 0), array_map('intval', SWUBotOpponents($seat)), true)) return null;
        $s = $def;
        $s['power'] = max(0, intval($def['power']) - $sh['p']); $s['attackPower'] = max(0, intval($def['attackPower']) - $sh['p']);
        if ($sh['h'] > 0 && empty($def['hpImmune'])) {
            $s['hp'] = max(1, intval($def['hp']) - $sh['h']);
            $s['remaining'] = intval($def['remaining']) - $sh['h'];
            if ($s['remaining'] <= 0) { $s['remaining'] = 1; $s['attackPower'] = 0; $s['shields'] = 0; }   // the token kills it: a free kill
        }
        $v = _SWUBotUnitTargetValue($att, $s, $W);
        return $v > $tv ? $v : null;
    },
]);
