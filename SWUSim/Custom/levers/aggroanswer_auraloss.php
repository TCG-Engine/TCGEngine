<?php
// LEVER 'auraloss' (theme 'aggroanswer', G1-34 aura-kill-value). Traced krennic-vader-1: Clone Combat Squadron ("+1/+1 for each other
// friendly space unit") hit for 8 in R5, while control's space attacks vs Vader went to the base 47-92% of the time. 'aurathreat' (p28)
// raises the aura SOURCE's value only; killing any other ship also shrinks every CCS by 1, which no kill value counted.
// killextra — a kill of an enemy unit adds W['base'] x the power it removes from the enemy auras that COUNT units: N for each other enemy
// unit reading "gets +N/+N for each other friendly [space|ground] unit" whose scope includes the dying unit's arena.

function _SWUBotLvAuralossCounted(array $def): int {
    $n = 0;
    foreach (SWUBotUnits(intval($def['controller'])) as $o) {
        if ($o['uid'] === $def['uid']) continue;
        if (preg_match('/gets \+(\d+)\/\+\d+ for each other friendly (space |ground )?unit/i', strval(CardText(strval($o['cardID']))), $m)) {
            $sc = strtolower(trim($m[2] ?? ''));
            if ($sc === '' || $sc === strtolower(strval($def['arena']))) $n += intval($m[1]);
        }
    }
    return $n;
}

SWUBotLeverRegister('aggroanswer', 'auraloss', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        if (intval($W['archetypeRank'] ?? 0) < 2) return null;   // scope: control and midrange targeting
        $n = _SWUBotLvAuralossCounted($def);
        return $n > 0 ? $extra + floatval($W['base']) * $n : null;
    },
]);
