<?php
// LEVER 'shrinkkillfirst' (theme 'engines', G4-36 talzin-deploy-kill-before-race). Deployed Mother Talzin's "On Attack: You may give a unit
// -1/-1 for this phase" went to Krennic JTL_032 (2/2 → 1/1, no kill) while she swung face (R5, Talzin Y vs Krennic). Vs Ahsoka Blue the
// 1-HP bodies (Han Solo 1/1, Lepi Lookout 3/1, Latts 2/1, Spy tokens) are what dies to it; Talzin Y loses that cell by 38pp.
// decision — the target of a repeatable On Attack "-N/-M for this phase" (APPLY_PHASE_DEBUFF from a card whose text reads "On Attack: You may
// give a unit -N/-M"): the enemy unit it KILLS, by value; else the ready enemy attacker whose -N power saves the most base damage this
// round; a unit that has already attacked (exhausted) last; my own units below PASS.

SWUBotLeverRegister('engines', 'shrinkkillfirst', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($head !== 'APPLY_PHASE_DEBUFF') return null;
        $parts = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
        $src = strval($parts[3] ?? '');
        if ($src === '' || !preg_match('/On Attack: You may give a unit -\d+\/-\d+ for this phase/i', strval(CardText($src)) . ' ' . strval(CardDeployText($src) ?? ''))) return null;
        if (!preg_match('/Arena-\d+$/', $c)) return null;
        $p = intval($parts[1] ?? 0); $h = intval($parts[2] ?? 0);
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        if (!SWUBotIsEnemyMz($seat, $c)) return -1.0;
        if ($h > 0 && intval($v['remaining']) <= $h) return 2.0 + floatval($W['kill']) * SWUBotUnitValue($v);
        if (!empty($v['ready'])) return 1.0 + floatval($W['base']) * min($p, max(0, intval($v['attackPower'])));
        return 0.1 + 0.01 * intval($v['power']);
    },
]);
