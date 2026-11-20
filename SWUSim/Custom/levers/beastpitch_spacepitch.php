<?php
// LEVER 'spacepitch' (theme 'beastpitch', owner 2026-11-08: T-6 Shuttle "against ground aggro, it's an easy pitch for beast target").
// Against ground aggro (_SWUBotGroundAggroOpponent), while a pitch-line leader is home before the flip turn, a space Sentinel (ASH_109 T-6
// Shuttle 1974) is the card the Beast Action discards — not an early play. Human Tarfful Blue cast T-6 in 0 of 27 games (held it, then lost
// it to a discard); the bot cast it in 64 of 100, 39 of them in rounds 3-4.
// play     — the space Sentinel waits (the 'maulpitch' hold, -0.4), so it is still in hand when the Action wants a card.
// decision — the Action's discard cost takes it: just under the Villainy pitch ('discardpick' scores that 0.5 + 0.01 x W[removal]),
//            above every ordinary card (0.5 - 0.01 x keep, keep >= 0).
SWUBotLeverRegister('beastpitch', 'spacepitch', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!_SWUBotIsSpaceSentinel($cid) || _SWUBotBeastPitchLeaderHome($seat) === null || !_SWUBotGroundAggroOpponent($seat)) return null;
        return min($v, -0.4);
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type === 'MZMULTICHOOSE' || stripos($tip, 'discard') === false || !preg_match('/^myHand-(\d+)$/', $c, $hm)) return null;
        if (!preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $hd) || !preg_match(SWU_BOT_BEASTPITCH_ACTION_RE, strval(CardText($hd[1])))) return null;
        $o = GetHand($seat)[intval($hm[1])] ?? null;
        if ($o === null || !_SWUBotIsSpaceSentinel(strval($o->CardID ?? '')) || !_SWUBotGroundAggroOpponent($seat)) return null;
        return 0.5 + 0.005 * floatval($W['removal']);
    },
]);
