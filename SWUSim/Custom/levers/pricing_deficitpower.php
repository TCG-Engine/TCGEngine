<?php
// LEVER 'deficitpower' (theme 'pricing', G4-28 resource-deficit-ctxpower). "While/if you control fewer resources than an opponent" power —
// SEC_151 Kazuda Xiono (+2/+0), LAW_202 Commence the Festivities (+2/+0 for the attack) — has no 'ctxpower' arm (SWUBotContextSurplus knows
// JTL_115 and SEC_179 only). Kazuda resourced 13/50, played in 8% of Chewbacca games; Commence resourced 11/45, played 12%.
// play — + W['base'] per point of that bonus while my resource count is below an opponent's (the ctxpower price, BotFallback _SWUBotPlayValue);
// keep — + SWU_BOT_CTXPOWER_KEEP per point (the ctxpower keep, BotResourcing.php).
// The attack-event half (Commence) is left to 'sabattackevent' when that lever is on, so the theme arm counts the +2 once.

function _SWUBotLvDeficitpowerBonus(string $cid): int {
    $t = strval(CardText($cid));
    if (preg_match('/While you control fewer resources than an opponent, this unit gets \+(\d+)\/\+0/i', $t, $m)) return intval($m[1]);
    if (!SWUBotProposalOn('sabattackevent') && preg_match('/If you control fewer resources than an opponent, it gets \+(\d+)\/\+0 for this attack/i', $t, $m)) return intval($m[1]);
    return 0;
}
function _SWUBotLvDeficitpowerBehind(int $seat): bool {
    $mine = SWUResourceCount($seat);
    foreach (SWUBotOpponents($seat) as $o) if (SWUResourceCount($o) > $mine) return true;
    return false;
}

SWUBotLeverRegister('pricing', 'deficitpower', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || ($b = _SWUBotLvDeficitpowerBonus($cid)) <= 0 || !_SWUBotLvDeficitpowerBehind($seat)) return null;
        return $v + floatval($W['base']) * $b;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (($b = _SWUBotLvDeficitpowerBonus($cid)) <= 0 || !_SWUBotLvDeficitpowerBehind($seat)) return null;
        return $keep + SWU_BOT_CTXPOWER_KEEP * $b;
    },
]);
