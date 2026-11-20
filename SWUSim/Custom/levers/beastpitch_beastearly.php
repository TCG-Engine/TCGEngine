<?php
// LEVER 'beastearly' (theme 'beastpitch', owner 2026-11-08: "yes let's rethink that because it is for sure hurting the Tarfful bot"). Revisits
// the 2026-10-08 ruling that Tarfful's "Action [2 resources, Exhaust, discard a card from your hand]: Create a Beast token" is ONLY the pitch
// line for Anakin. Human Tarfful Blue took it in round 1 in 27 of 27 SWU Forge games — 12 pitching the Villainy card, 15 pitching anything
// else (Bo-Katan, Hunter, Blue Leader, Kelleran Beq …) — and 14 more times in rounds 2-4; the bot took it in round 1 in 57 of 100 games and
// claimed or played Owen Lars instead. Before the flip turn (< 6 resources), with no Villainy pitch on offer (that case keeps 'villainpitch'):
//   round 1 — always: the 2 resources ARE the last;
//   rounds 2-5 — with the last N resources, after the plays (the pitch line's own order, as _SWUBotPitchActionValue).
// The card it costs is 'discardpick''s lowest keep value (or 'spacepitch''s space Sentinel).
SWUBotLeverRegister('beastpitch', 'beastearly', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!preg_match(SWU_BOT_BEASTPITCH_ACTION_RE, _SWUBotActionSourceText($seat, $action), $m)) return null;
        if (_SWUBotBeastPitchLeaderHome($seat) === null || _SWUBotBeastPitchHasVillainyPitch($seat)) return null;
        if (intval(GetTurnNumber()) > 1 && SWUTotalPaymentCapacity($seat) > intval($m[1])) {
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) === 'play' && SWUBotScoreAction($ctx, $a, intval($i)) > 0.0) return 0.02;   // the plays first
            }
        }
        return SWUBotLeverNum('PITCH_LINE', SWU_BOT_PITCH_LINE);
    },
]);
