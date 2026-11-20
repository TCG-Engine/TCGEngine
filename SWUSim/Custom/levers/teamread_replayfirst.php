<?php
// LEVER 'replayfirst' (theme 'teamread', team games 2026-10-29, found by the D3 smoke). Every trigger orders at 1.0, so a tie goes to stack
// index 0 — and a Plot window opened by the deploy (SEC_186 Garindan) sat above Maul's own "When Deployed: play a unit defeated this phase
// from your discard" (HMW_016). The Plot was played first, spent the resources, and the replay found nothing it could afford (Maul smoke,
// 4 of 4 empty deploys inspected). The human order: replay first — the Plot window waits on the stack and is still offered after.
// decision — at "Choose trigger to resolve", the When Deployed of a deploy-replay leader (SWU_BOT_DEPLOYREPLAY_RE) goes ahead of a Plot window.

SWUBotLeverRegister('teamread', 'replayfirst', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($tip !== 'Choose_trigger_to_resolve' || !preg_match('/EffectStack-(\d+)$/', $c, $m)) return null;
        $stack = GetEffectStack();
        $plot = false;
        foreach ($stack as $o) if ($o !== null && empty($o->removed) && strval($o->TriggerType ?? '') === 'SWU_PLOT_WINDOW') $plot = true;
        if (!$plot) return null;
        $e = $stack[intval($m[1])] ?? null;
        if ($e === null || strval($e->TriggerType ?? '') !== 'WhenPlayed') return null;
        return preg_match(SWU_BOT_DEPLOYREPLAY_RE, strval(CardDeployText(strval($e->CardID ?? '')))) ? 1.5 : null;
    },
]);
