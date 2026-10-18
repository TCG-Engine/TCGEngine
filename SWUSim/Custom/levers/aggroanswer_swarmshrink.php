<?php
// LEVER 'swarmshrink' (theme 'aggroanswer', G1-14 swarm-answer-on-curve). Control's cheap answers sit in hand vs space swarms (Let's Call It
// War cast 8 of 40 drawn, Collateral Damage 4/19, Piercing Shot 5/29): 'shrink-first' needs a READY 3+ power target, but the swarm is 1-2
// power ships buffed by Victor Leader / Clone Combat Squadron.
// filter — the control wing (as 'shrink-first': archetype and acting rank >= 3), against a board carrying an aura buffer, counts a ready
// enemy unit's power for the rule's bar WITH the aura power its death removes (its own grant to the others, and 1 per "+1/+1 for each other
// friendly unit" ship that counts it). When only that aura-adjusted reading qualifies a target, the best castable cheap (cost <= 3) removal
// that kills it is the move — the list is cut to it, as the rule would have picked it. Not while lethal is on either side.
const SWU_BOT_LV_SWARMSHRINK_MAX_COST = 3;

// The aura power a death of enemy unit $u removes from its side.
function _SWUBotLvSwarmshrinkAuraLoss(array $u): int {
    $loss = function_exists('_SWUBotAuraGrantedPower') ? intval(_SWUBotAuraGrantedPower($u)) : 0;
    foreach (SWUBotUnits(intval($u['controller'])) as $o) {
        if ($o['uid'] === $u['uid']) continue;
        if (preg_match('/gets \+(\d+)\/\+\d+ for each other friendly (space |ground )?unit/i', strval(CardText(strval($o['cardID']))), $m)) {
            $sc = strtolower(trim($m[2] ?? ''));
            if ($sc === '' || $sc === strtolower($u['arena'])) $loss += intval($m[1]);
        }
    }
    return $loss;
}
function _SWUBotLvSwarmshrinkHasBuffer(int $opp): bool {
    foreach (SWUBotUnits($opp) as $o) {
        $t = strval(CardText(strval($o['cardID'])));
        if (preg_match('/Each other friendly (space |ground )?unit gets \+\d+\/|for each other friendly (space |ground )?unit/i', $t)) return true;
    }
    return false;
}

SWUBotLeverRegister('aggroanswer', 'swarmshrink', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play' || strval(GetCurrentPhase()) !== 'MAIN') return null;
        $style = strval($ctx['style'] ?? ''); $seat = intval($ctx['seat']); $opp = intval($ctx['opp'] ?? SWUBotOpponent($seat));
        if (SWUBotStyleRank($style) < 3 || SWUBotRacingRank($style, $seat) < 3 || !_SWUBotLvSwarmshrinkHasBuffer($opp)) return null;
        if (SWUBotLethalNow($seat, $opp) || SWUBotClock($opp, $seat) <= 1) return null;
        $bar = SWU_BOT_THREAT_WORTH;
        $plain = false; $threats = [];
        foreach (SWUBotUnits($opp) as $u) {
            if (empty($u['ready'])) continue;
            if (intval($u['attackPower']) >= $bar) { $plain = true; break; }
            if (intval($u['attackPower']) + _SWUBotLvSwarmshrinkAuraLoss($u) >= $bar) $threats[] = $u;
        }
        if ($plain || empty($threats)) return null;   // the rule already reads this board (or nothing qualifies)
        $best = null; $bestS = null;
        foreach ($actions as $k => $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $cid = strval($o->CardID ?? '');
            if (intval(SWUComputePlayCost($seat, $o)) > SWU_BOT_LV_SWARMSHRINK_MAX_COST || !array_intersect(SWUBotCardTags($cid), ['removal', 'damage-enemy-unit'])) continue;
            $kills = false;
            foreach ($threats as $u) if (SWUBotHandCardKills($cid, $u)) { $kills = true; break; }
            if (!$kills) continue;
            $s = SWUBotScoreAction($ctx, $a, intval($k));
            if ($s > -0.4 && ($bestS === null || $s > $bestS)) { $best = $a; $bestS = $s; }
        }
        return $best === null ? null : [$best];
    },
]);
