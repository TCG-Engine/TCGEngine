<?php
// LEVER 'deploywait' (theme 'teamread', team games 2026-10-29, report D1). A leader deploy waits until the opponent can no longer answer it
// this round: deployed after the opponent claimed or spent out, 0 of 23 human leaders died that round; deployed mid-round, 16 of 59 did —
// every Hemlock death was HELD removal. The Talzin plan (plan_talzinflip.php) already times her flip this way; this is that rule for every
// other leader. GO = safe (_SWUBotTrDeploySafe) or forced (_SWUBotTrDeployForced — never waste the round); otherwise the deploy waits (-0.4)
// behind the round's other plays and attacks.
// deploy — exempt: Talzin (her own plan), a deploy already scored as a deliberate premium (>= 3.0: Kylo replay, Chewie, Lando bomb flip),
//          a deploy that replays a unit defeated this phase (the discard target is live now), and lethal on board.
// Knob 'deploywait.maxhp' (default 99 = every leader): only leaders whose deployed HP is at most this wait — a fragile-only arm.

SWUBotLeverRegister('teamread', 'deploywait', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if ($dv <= -0.4 || $dv >= 3.0) return null;
        $leader = _SWUBotLeaderOfAction($seat, $action);
        if ($leader === null || strval($leader->CardID ?? '') === 'LOF_002') return null;
        if (_SWUBotTrDeployHp($leader) > SWUBotLeverNum('deploywait.maxhp', 99)) return null;
        if (preg_match(SWU_BOT_DEPLOYREPLAY_RE, strval(CardDeployText(strval($leader->CardID ?? ''))))) {
            foreach (GetDiscard($seat) as $o) {   // a replay target is live now — the deploy is the play, not a body
                if ($o !== null && empty($o->removed) && GlobalEffectCount($seat, 'SWU_DEFEATED_CARD_' . strval($o->CardID ?? '')) > 0) return null;
            }
        }
        $opp = SWUBotOpponent($seat);
        if (SWUBotLethalNow($seat, $opp)) return null;
        if (_SWUBotTrDeploySafe($seat, $leader) || _SWUBotTrDeployForced($ctx, $seat)) return null;
        return min($dv, -0.4);
    },
]);
