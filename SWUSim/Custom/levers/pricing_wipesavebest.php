<?php
// LEVER 'wipesavebest' (theme 'pricing', G2-38 rhydonium-saves-the-best-unit). LAW_096 Rhydonium Detonation ("Each player may return a non-leader
// unit to its owner's hand. Then, defeat all non-leader units"): "Choose_a_non-leader_unit_to_return" took the first listed candidate 96/96 —
// both sides returned myGroundArena-0, not their most valuable unit. The pool is every non-leader unit on the table, enemy ones included.
// decision (continuation LAW_096#PICK) — a unit I own scores 1 + its replay value (its play value as a hand card, which carries its When Played
//        tags) + 0.01 x cost (ties to the more expensive); a token scores ~0; a unit an opponent owns scores below PASS (saving it is a gift).

SWUBotLeverRegister('pricing', 'wipesavebest', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($head !== 'LAW_096#PICK') return null;
        if ($c === '-' || $c === '' || $c === 'PASS') return 0.0;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        $owner = intval($v['obj']->Owner ?? $v['controller']);
        $cid = strval($v['cardID']);
        if ($owner !== $seat) return -1.0 - SWUBotUnitValue($v);
        if (stripos(strval(CardType($cid) ?? ''), 'Token') !== false || preg_match('/_T\d+$/', $cid)) return 0.001;
        return 1.0 + max(0.0, _SWUBotPlayValue($seat, $cid, $W)) + 0.01 * intval(CardCost($cid));
    },
]);
