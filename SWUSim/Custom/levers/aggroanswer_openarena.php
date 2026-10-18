<?php
// LEVER 'openarena' (theme 'aggroanswer', G1-12 open-arena-pressure). Traced mando-vader-1: Vader had no ground unit from R1 to R4, but Mando
// cast no unit in those rounds and had dealt 5 base damage by R7 (Mando Colossus deals 1.6-1.9 by R4 against everyone). The real Mando line
// vs Vader is a ground race while space is answered by removal.
// play — a midrange / control unit play (archetype rank >= 2) into an arena where the opponent has NO unit (so no Sentinel), while the
// opponent's board sits in the other arena, adds its unblocked swings: W['base'] x power x min(2, rounds until the expected wipe) — 2 rounds,
// no wipe being known. Hera's "heal that much" adds her heal (W['heal'] per point, up to my base's damage).
const SWU_BOT_LV_OPENARENA_ROUNDS = 2;

SWUBotLeverRegister('aggroanswer', 'openarena', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || intval($W['archetypeRank'] ?? 0) < 2 || !str_contains(strval(CardType($cid)), 'Unit')) return null;
        $power = max(0, intval(CardPower($cid)));
        if ($power <= 0) return null;
        $arena = _SWUBotPlayArena($cid);
        $here = 0; $there = 0;
        foreach (SWUBotEnemyUnits($seat) as $u) { if ($u['arena'] === $arena) $here++; else $there++; }
        if ($here > 0 || $there === 0) return null;
        $add = floatval($W['base']) * $power * SWU_BOT_LV_OPENARENA_ROUNDS;
        if (preg_match('/When Attack Ends: If this unit dealt combat damage to a base, heal that much damage from your base/i', strval(CardText($cid)))) {
            $dmg = intval((GetBase($seat)[0] ?? null)->Damage ?? 0);
            $add += floatval($W['heal']) * min($dmg, $power * SWU_BOT_LV_OPENARENA_ROUNDS);
        }
        return $v + $add;
    },
]);
