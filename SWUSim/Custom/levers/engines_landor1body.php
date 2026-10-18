<?php
// LEVER 'landor1body' (theme 'engines', ASH lever hunt G4-17). Off by default: '@try-landor1body' / '@try-engines'.
// LAW_018 Lando's round 1 was the 1-resource mill Action in 226 of 226 games; with a 2-cost-or-less unit in hand (38 traced games) no R1
// unit was played in 29 — Loth-Wolf (2, Sentinel 3/3) waited a round. Lando Colossus vs Ahsoka is −18. Against an AGGRO leader only,
// round 1: when a castable 2-cost-or-less blocker (a printed Sentinel, or 3+ HP) cannot be paid for together with the Action, the
// Action waits (−0.5) and the body goes down; the mill starts in round 2. ⚠ Contradicts the owner's 'landomill' line (mill MY deck for
// the Credit) — a judgement call for the owner, vs aggro only.

SWUBotLeverRegister('engines', 'landor1body', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability' || intval(GetTurnNumber()) !== 1) return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'LAW_018' || _SWUBotLeaderFront($seat, 'LAW_018') === null) return null;
        if (!SWUBotOpponentIsAggroLeader($seat)) return null;
        $cap = SWUTotalPaymentCapacity($seat);
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $cid = strval($o->CardID ?? '');
            $cost = intval(SWUComputePlayCost($seat, $o));
            if (!str_contains(strval(CardType($cid)), 'Unit') || intval(CardCost($cid)) > 2 || $cost > $cap) continue;
            if (!_SWUBotHasPrintedSentinel($cid) && intval(CardHp($cid)) < 3) continue;
            if ($cost + 1 > $cap) return -0.5;
        }
        return null;
    },
]);
