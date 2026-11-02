<?php
// LEVER 'condemn0' (theme 'teamread', team games 2026-10-29, report D5). Condemn SEC_038 makes its host, while attacking, lose every other
// ability and take -6/-0 when the defender discloses Vigilance Villainy. 'condemnfore' (p36) already prices that -6 into the attack power, but
// rule 'no-unused-attacks' still sends the blanked unit in as a "free" attack: Grievous attacked 5 times for 0 damage and died on the last.
// filter — a free-play attack by a Condemned unit whose expected attack power is 0 or less is dropped: it deals nothing and has no On Attack
//          of its own (Condemn strips it), so all it can do is take damage. Never empties the list (the hook keeps the original then).
// target — any attack at 0 or less power into a unit that strikes back and does not kill it is charged that damage (chip x power).

function _SWUBotTrCondemned(array $att): bool {
    if (!isset($att['obj'])) return false;
    foreach (GetUpgradesOnUnit($att['obj']) as $s) if (strval($s->CardID ?? '') === 'SEC_038') return true;
    return false;
}

SWUBotLeverRegister('teamread', 'condemn0', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play') return null;
        $seat = intval($ctx['seat'] ?? 0);
        $out = [];
        $dropped = false;
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) === 'attack' && ($att = SWUBotViewForMz($seat, SWUBotActionMz($a))) !== null
                && intval($att['attackPower']) <= 0 && _SWUBotTrCondemned($att)) { $dropped = true; continue; }
            $out[] = $a;
        }
        return $dropped && $out ? $out : null;
    },
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null || intval($att['attackPower']) > 0) return null;
        $back = intval($def['power']);
        if ($back <= 0 || $back >= intval($att['remaining'])) return null;   // no strike back, or a death the core already prices
        return $tv - floatval($W['chip']) * $back;
    },
]);
