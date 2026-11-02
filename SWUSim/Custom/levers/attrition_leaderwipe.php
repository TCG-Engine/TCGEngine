<?php
// LEVER 'leaderwipe' (theme 'attrition'). Ninin vs Boba Fett R6: Hyperspace Disaster ("Defeat all space units") on ONE ship — the TIE Bomber
// Boba Fett (JTL_009) piloted. That removed the deployed leader for the rest of the game (owner 2026-10-19: a defeated leader is gone as a
// unit, except Poe JTL_013 / Trench JTL_014 / Grogu ASH_018). The wipe's value is its flat tags plus its curve read, which carries no
// leader kill (2.80 on the replay board). An unbounded wipe that defeats an enemy LEADER unit (a pilot-led Vehicle included) now earns
// W[kill] x that unit's value — half for a leader that can come out again.
SWUBotLeverRegister('attrition', 'leaderwipe', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!function_exists('_SWUBotLvWideWipeShape') || !in_array('wipe', SWUBotCardTags($cid), true) || ($sh = _SWUBotLvWideWipeShape($cid)) === null || !$sh['leaders']) return null;
        $add = 0.0;
        foreach (SWUBotEnemyUnits($seat) as $e) {
            if (empty($e['isLeader']) || SWUBotDefeatFizzles($e) || ($sh['arena'] !== 'all' && $e['arena'] !== $sh['arena'])) continue;
            $back = false;
            foreach (SWUBotOpponents($seat) as $o) {
                $l = strval((GetLeader(intval($o))[0] ?? null)->CardID ?? '');
                if (in_array($l, SWU_BOT_LV_REDEPLOY_LEADERS, true)) $back = true;
            }
            $add += floatval($W['kill']) * SWUBotUnitValue($e) * ($back ? 0.5 : 1.0);
        }
        return $add > 0.0 ? $v + $add : null;
    },
]);
