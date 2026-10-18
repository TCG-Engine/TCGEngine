<?php
// LEVER 'flipguard' (theme 'aggroanswer', G3-32 flip-turn-guard). Ahsoka Blue's base damage per round: R3 4.5, R4 6.6, R5 10.5 (the
// 6-resource Support deploy + Plot Jar Jars), R6 6.4; Lando takes 11.6 combat damage in R5 vs her; Ahsoka over-performs vs every control
// deck (+20 to +38). 'mitigation' keeps a Sentinel while a leader able to deploy NOW can hit; this looks one round ahead.
// In the round BEFORE an aggro leader reaches its deploy threshold, a midrange / control seat (rank >= 2):
//   keep      — keeps one Sentinel unit and one removal card out of the resource pick (+50 each, the key-card size);
//   play      — holds its LAST removal card unless it kills a 3+ power unit now (that one is for the flip turn);
//   killextra — prefers killing the unit most likely to take the Support buff: the highest-power enemy unit with less power than the leader.
const SWU_BOT_LV_FLIPGUARD_KEEP = 50.0;

// The opponent's aggro leader card deploys next round (not now): its threshold is one resource away.
function _SWUBotLvFlipguardLeader(int $seat): ?string {
    if (!SWUBotOpponentIsAggroLeader($seat)) return null;
    foreach (SWUBotOpponents($seat) as $o) {
        foreach (GetLeader($o) as $l) {
            if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
            $t = _SWUBotLeaderThreshold($o, $l); $r = SWUResourceCount($o);
            if ($t > 0 && $r < $t && $r + 1 >= $t) return strval($l->CardID ?? '');
        }
    }
    return null;
}
function _SWUBotLvFlipguardIsRemoval(string $cid): bool {
    return in_array('removal', SWUBotCardTags($cid), true);
}

SWUBotLeverRegister('aggroanswer', 'flipguard', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 2 || _SWUBotLvFlipguardLeader($seat) === null) return null;
        $sentinel = _SWUBotHasPrintedSentinel($cid) && str_contains(strval(CardType($cid)), 'Unit');
        $removal = _SWUBotLvFlipguardIsRemoval($cid);
        if (!$sentinel && !$removal) return null;
        // Only the FIRST such card in hand: one of each is kept.
        foreach (GetHand($seat) as $j => $o) {
            if ($j >= $i || $o === null || !empty($o->removed)) continue;
            $oc = strval($o->CardID ?? '');
            if ($sentinel && _SWUBotHasPrintedSentinel($oc) && str_contains(strval(CardType($oc)), 'Unit')) $sentinel = false;
            if ($removal && _SWUBotLvFlipguardIsRemoval($oc)) $removal = false;
        }
        return ($sentinel || $removal) ? $keep + SWU_BOT_LV_FLIPGUARD_KEEP : null;
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || intval($W['archetypeRank'] ?? 0) < 2 || !_SWUBotLvFlipguardIsRemoval($cid) || _SWUBotLvFlipguardLeader($seat) === null) return null;
        $n = 0;
        foreach (_SWUBotHandCards($seat) as $hc) if (_SWUBotLvFlipguardIsRemoval($hc)) $n++;
        if ($n > 1) return null;
        foreach (SWUBotEnemyUnits($seat) as $u) if (intval($u['attackPower']) >= SWU_BOT_THREAT_WORTH && SWUBotHandCardKills($cid, $u)) return null;
        return min($v, -0.4);
    },
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        if ($seat <= 0 || intval($W['archetypeRank'] ?? 0) < 2 || !empty($def['isLeader']) || ($lc = _SWUBotLvFlipguardLeader($seat)) === null) return null;
        $lp = intval(CardPower($lc));
        $best = null;
        foreach (SWUBotUnits(intval($def['controller'])) as $u) {
            if (!empty($u['isLeader']) || intval($u['power']) >= $lp) continue;
            if ($best === null || intval($u['power']) > intval($best['power'])) $best = $u;
        }
        if ($best === null || $best['uid'] !== $def['uid']) return null;
        return $extra + floatval($W['base']) * 2.0;
    },
]);
