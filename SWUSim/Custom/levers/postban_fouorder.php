<?php
// LEVER 'fouorder' (theme 'postban'; Force Fam post-ban team read 2026-11-12, lever H2). Figure of Unity SEC_104 gives its bearer Raid 1 and
// Restore 1 only while it is ready — Dralack attacked with every other unit first and the bearer last in 8+ games (8f77b915, 951d7dea,
// 91e4882b, fb3a981e, ff8ea5e1, f881c3f5 …). The bot has no attack-order logic for a "while this unit is ready" aura. Here the bearer's
// attack scores 1.0 lower (floored just above zero, so it still attacks) while another friendly unit is ready to attack. Hook: 'attack'.
// (Not covered: WHERE the upgrade goes — humans put it on the deployed Wicket in R3.)

const SWU_BOT_LV_FOUORDER_ID = 'SEC_104';
const SWU_BOT_LV_FOUORDER_LATER = 1.0;

SWUBotLeverRegister('postban', 'fouorder', [
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if ($s <= 0.02) return null;
        $bearer = false;
        foreach (GetUpgradesOnUnit($att['obj']) as $u) if (strval($u->CardID ?? '') === SWU_BOT_LV_FOUORDER_ID) $bearer = true;
        if (!$bearer) return null;
        foreach (SWUBotUnits($seat) as $u) {
            if ($u['uid'] !== $att['uid'] && $u['ready']) { _SWUBotFired('fouorder', $seat); return max(0.02, $s - SWU_BOT_LV_FOUORDER_LATER); }
        }
        return null;
    },
]);
