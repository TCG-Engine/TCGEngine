<?php
// LEVER 'shieldbodykeep' (theme 'resourcing'; ASH lever hunt G4-09) — the midrange efficient-body keep ('mgkeep') counts Shielded and Ambush.
// Evidence (resourced when in hand): Darth Tyranus LOF_231 (4c 4/3 Shielded, Ambush while the Force is with you) 197/332 (Talzin Y), 383/547
// (R); Blade of Talzin 61/86, 225/261. _SWUBotIsEfficientBody counts printed power + HP >= 2 x cost only, so 4/3 for 4 = 7 < 8 is "not
// efficient" while a 2/2 for 1 is. Change (rank 2, wherever 'mgkeep' / 'mgkeepbody' keeps a body): a unit that fails the printed test but
// passes it with its keywords also keeps the mgkeep +100 — a Shield counts +P (the owner's rule of thumb: a Shield ~ +P per Shield), Ambush
// counts +P/2 (a free attack on entry, judged half a swing) when unconditional or when its condition holds (the Force with me, or a Force
// base). And an upgrade that returns itself to hand when its host dies (Blade of Talzin: "on a friendly Night unit") is kept like a body while
// such a host is in play or hand. Hook: 'keep' (midrange: the plain resourcer).

const SWU_BOT_LV_SHIELDBODYKEEP_BONUS = 100.0;   // = the mgkeep body bonus

function _SWUBotLvShieldbodykeepForceOn(int $seat): bool {
    if (function_exists('PlayerHasTheForce') && PlayerHasTheForce($seat)) return true;
    return (bool)preg_match('/The Force is with you/i', strval(CardText(strval((GetBase($seat)[0] ?? null)->CardID ?? ''))));
}

function _SWUBotLvShieldbodykeepBody(int $seat, string $cid): bool {
    if (strval(CardType($cid)) !== 'Unit') return false;
    $cost = _SWUBotSeatCost($seat, $cid);
    if ($cost <= 0) return false;
    $t = strval(CardText($cid)); $p = max(0, intval(CardPower($cid)));
    $extra = 0;
    if (preg_match('/(^|\n)\s*Shielded\b/i', $t)) $extra += $p;
    $ambush = preg_match('/(^|\n)\s*Ambush\b/i', $t)
        || (preg_match('/While the Force is with you, this unit gains Ambush/i', $t) && _SWUBotLvShieldbodykeepForceOn($seat));
    if ($ambush) $extra += intdiv($p, 2);
    return $extra > 0 && $p + intval(CardHp($cid)) + $extra >= 2 * $cost;
}

// A recurring upgrade: "If this upgrade was on a friendly <Trait> unit, return this upgrade from your discard pile to your hand" — with a unit
// of that trait in play or in hand.
function _SWUBotLvShieldbodykeepRecurring(int $seat, string $cid): bool {
    if (!str_contains(strval(CardType($cid)), 'Upgrade')
        || !preg_match('/If this upgrade was on a friendly (\w+) unit, return this upgrade from your discard pile to your hand/i', strval(CardText($cid)), $m)) return false;
    $trait = $m[1];
    foreach (SWUBotUnits($seat) as $v) if (str_contains(strval(CardTrait($v['cardID']) ?? ''), $trait)) return true;
    foreach (_SWUBotHandCards($seat) as $h) if (str_contains(strval(CardType($h)), 'Unit') && str_contains(strval(CardTrait($h) ?? ''), $trait)) return true;
    return false;
}

SWUBotLeverRegister('resourcing', 'shieldbodykeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (SWUBotStyleRank(strval($ctx['style'] ?? '')) !== 2 || !(SWUBotFeatureOn('mgkeep') || SWUBotProposalOn('mgkeepbody'))) return null;
        if (_SWUBotIsEfficientBody($seat, $cid)) return null;   // mgkeep already keeps it
        return (_SWUBotLvShieldbodykeepBody($seat, $cid) || _SWUBotLvShieldbodykeepRecurring($seat, $cid)) ? $keep + SWU_BOT_LV_SHIELDBODYKEEP_BONUS : null;
    },
]);
