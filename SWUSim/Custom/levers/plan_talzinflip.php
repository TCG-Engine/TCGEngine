<?php
// LEVER 'talzinflip' (theme 'tempoplans', shipped in p59). Spec 2026-10-20-swusim-tempo-deck-plans-design.md §1, every Talzin (LOF_002) deck.
// Owner, 2026-10-20: "you most likely want to deploy on 5. there's rarely a reason to not deploy"; within that round "if the opponent has big
// power units that could kill her, then wait until it's already attacked … better to wait until they use resources before deploying"; the
// flip turn is "once from her leader side and then again on attack from her leader unit side", with the upgrades on her ("Constructed saber
// plus Blade of Talzin makes for huge aggressive swings"). GO = safe (_SWUBotTalzinSafe) or forced (_SWUBotTalzinForced — never waste the round).
// ability  — her front −1/−1 goes first on the flip turn (GO, an enemy unit to shrink).
// deploy   — waits while that front Action is still on offer; then GO → deploy now (3.0), else wait (−0.4).
// play     — while the flip is pending, a play that would leave fewer ready resources than her upgrades need waits; before she has flipped
//            (Epic Action unused) her upgrades themselves wait — they are for HER (smoke 2026-10-21: in 10 Talzin Red games they were
//            played once, R9: resourced or spent on other units before the R4 flip).
// keep     — the resourcer keeps one copy each of Constructed Lightsaber and Blade of Talzin in hand.
// decision — Constructed Lightsaber / Blade of Talzin attach to the deployed Talzin.
// attack   — deployed Talzin's attack waits while one of those upgrades is castable.

function _SWUBotTalzinFlipPending(array $ctx, int $seat): bool {
    return _SWUBotLeaderFront($seat, 'LOF_002') !== null && _SWUBotFind($ctx, fn($a) => _SWUBotTalzinAction($seat, $a, 'deploy')) !== null;
}
// Talzin has not yet used her Epic Action (still to flip, not merely returned after a defeat).
function _SWUBotTalzinToFlip(int $seat): bool {
    $l = GetLeader($seat)[0] ?? null;
    return $l !== null && strval($l->CardID ?? '') === 'LOF_002' && !in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true)
        && !in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true);
}
function _SWUBotTalzinGo(array $ctx, int $seat): bool {
    return _SWUBotTalzinSafe($seat) || _SWUBotTalzinForced($ctx, $seat);
}

SWUBotLeverRegister('tempoplans', 'talzinflip', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!_SWUBotTalzinAction($seat, $action, 'leader-ability') || !_SWUBotTalzinFlipPending($ctx, $seat) || !SWUBotEnemyUnits($seat)) return null;
        return _SWUBotTalzinGo($ctx, $seat) ? SWUBotLeverNum('talzin.debufffirst', 3.1) : null;
    },
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if (!_SWUBotTalzinAction($seat, $action, 'deploy')) return null;
        if (SWUBotEnemyUnits($seat) && _SWUBotFind($ctx, fn($a) => _SWUBotTalzinAction($seat, $a, 'leader-ability')) !== null) return min($dv, -0.4);
        return _SWUBotTalzinGo($ctx, $seat) ? max($dv, 3.0) : min($dv, -0.4);
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4) return null;
        if (isset(SWU_BOT_TALZIN_UPGRADES[$cid])) return _SWUBotTalzinToFlip($seat) ? min($v, -0.4) : null;
        if (!_SWUBotTalzinFlipPending($ctx, $seat)) return null;
        return SWUResourceCount($seat, true) - _SWUBotSeatCost($seat, $cid) < _SWUBotTalzinReserve($seat) ? min($v, -0.4) : null;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!isset(SWU_BOT_TALZIN_UPGRADES[$cid]) || _SWUBotTalzinColour($seat) === '') return null;
        foreach (GetHand($seat) as $j => $o) {   // only the first copy in hand
            if ($j < $i && $o !== null && strval($o->CardID ?? '') === $cid) return null;
        }
        return $keep + SWUBotLeverNum('talzin.keep', 150.0);
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($head !== 'ATTACH_UPGRADE') return null;
        $up = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? '');
        if (!isset(SWU_BOT_TALZIN_UPGRADES[$up]) || ($t = _SWUBotTalzinUnit($seat)) === null) return null;
        $v = SWUBotViewForMz($seat, $c);
        return ($v !== null && intval($v['uid']) === intval($t['uid'])) ? 9.0 : null;
    },
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (empty($att['isLeader']) || $att['cardID'] !== 'LOF_002') return null;
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            if (isset(SWU_BOT_TALZIN_UPGRADES[strval($o->CardID ?? '')]) && _SWUBotLvScoresAbovePass($ctx, $a)) return min($s, -0.4);   // only one the bot would cast
        }
        return null;
    },
]);
