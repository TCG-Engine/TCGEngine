<?php
// LEVER 'nuvochain' (theme 'tempoplans', shipped in p59). Spec 2026-10-20-swusim-tempo-deck-plans-design.md §3. Owner, 2026-10-20: "ideally,
// it's a 1hp unit or a 2hp unit that was given a weakness token by Hemlock's ability previously. you never want to put the Nuvo weakness on a
// unit with no weakness tokens because then you forfeit Hemlock being able to put his weakness token on it. that said, to soften things up
// for a trade is a good consideration too". Hemlock's front Action only takes "a unit without a Weakness token on it".
// decision — while Hemlock (HMW_003) is still on his leader side, an optional Weakness give on an enemy unit with NO token scores below PASS,
//            unless the token kills it or turns a ready unit's attack on it into a clean kill this turn. Hemlock's own pick ("…without_one")
//            and units already carrying a token are left to the scorer. Every optional give, not only Nuvo's: Nuvo's When Played cannot be
//            told from another card's, and the reason (save Hemlock's targets) is the same.

SWUBotLeverRegister('tempoplans', 'nuvochain', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        // Optional gives only: in a MANDATORY pick (HMW_100 Torrent) a ban below PASS could push the token onto my own unit (final review #3).
        if ($type !== 'MZMAYCHOOSE' || stripos($tip, 'weakness') === false || stripos($tip, 'without_one') !== false || !SWUBotIsEnemyMz($seat, $c)) return null;
        if (function_exists('SeatCountForGame') && SeatCountForGame() > 2) return null;   // 2-player only (spec)
        if (strval((GetLeader($seat)[0] ?? null)->CardID ?? '') !== 'HMW_003' || _SWUBotLeaderFront($seat, 'HMW_003') === null) return null;
        if (($v = SWUBotViewForMz($seat, $c)) === null) return null;
        $tokens = 0;
        foreach (GetUpgradesOnUnit($v['obj']) as $u) if (strval($u->CardID ?? '') === 'HMW_T02') $tokens++;
        // The HP the give takes: the tokens plus any damage a Shield does not stop (Inferno Squad: "Deal 1 damage … and give a Weakness token"),
        // as _SWUBotWeaknessScore counts it — a 2-HP Inferno target is a KILL, never banned (final review #2).
        $next = strval(($ctx['following'] ?? [])[0] ?? '');
        $loss = _SWUBotWeaknessCount($tip, $next) + (intval($v['shields']) > 0 ? 0 : _SWUBotEffectAmount($tip, $next));
        if ($tokens >= 1 || $loss <= 0 || $loss >= intval($v['remaining'])) return null;
        $shrunk = $v;
        $shrunk['power'] = max(0, intval($v['power']) - $loss); $shrunk['attackPower'] = max(0, intval($v['attackPower']) - $loss);
        $shrunk['remaining'] = intval($v['remaining']) - $loss;
        foreach (SWUBotUnits($seat) as $u) {
            if (empty($u['ready']) || $u['arena'] !== $v['arena'] || intval($u['attackPower']) <= 0) continue;
            if (SWUBotCombatOutcome($u, $v) !== 'kill-survive' && SWUBotCombatOutcome($u, $shrunk) === 'kill-survive') return null;   // soften for a trade
        }
        return SWUBotLeverNum('nuvo.ban', -1.0);
    },
]);
