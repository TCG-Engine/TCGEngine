<?php
// LEVER 'claimnotlethal' (theme 'order', ASH lever hunt G1-09). Off by default: '@try-claimnotlethal' / '@try-order'.
// Taking the initiative ends my round, so every remaining action is the opponent's. In the baseline, the share of losses
// where the loser claimed in the final round and was then attacked rises with the style gradient (hyper aggro 9.9% →
// hard control 15.8%); traced krennic-vader-1 R5: 'initiative-for-wipe' claimed first, Vader made 5 attacks and won.
// Never claim while the opponent's ready attackers (plus a leader that can deploy now and swing) reach my base's
// remaining HP AND a castable play of mine reduces that damage: the claim is taken off the list, so the play goes first
// (or I pass and keep the right to answer). Covers every claim: 'initiative-for-wipe', the Mando claim-draw, the fallback.

// What the opponents can still hit my base for this round: their ready units' base reach + a deployable leader's swing.
function _SWUBotLvClaimnotlethalIncoming(int $seat): int {
    $best = 0;
    foreach (SWUBotOpponents($seat) as $o) {
        $n = SWUBotBasePotential(intval($o), $seat, true);
        foreach (GetLeader(intval($o)) as $l) {
            if ($l === null || !empty($l->removed) || !_SWUBotLeaderCanDeployNow(intval($o), $l)) continue;
            $n += max(0, intval(CardPower(strval($l->CardID ?? ''))));
            break;
        }
        $best = max($best, $n);
    }
    return $best;
}

// A play that cuts THIS round's incoming damage: a Sentinel / Ambush kill / fodder (feature 'mitigation'), removal,
// an exhaust, a -X debuff, a blank ("loses all abilities"), a bounce / capture / take-control, a wipe.
function _SWUBotLvClaimnotlethalMitigates(int $seat, string $cid): bool {
    if (_SWUBotMitigationPlay($seat, $cid)) return true;
    $tags = SWUBotCardTags($cid);
    if (array_intersect($tags, ['removal', 'exhaust-enemy-unit', 'debuff', 'wipe', 'damage-wipe-enemy', 'bounce', 'capture', 'take-control'])) return true;
    return (bool)preg_match('/loses all abilities/i', strval(CardText($cid)));
}

SWUBotLeverRegister('order', 'claimnotlethal', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play') return null;
        $seat = intval($ctx['seat']);
        if (!array_filter($actions, fn($a) => SWUBotActionKind($a) === 'initiative')) return null;
        if (_SWUBotLvClaimnotlethalIncoming($seat) < SWUBaseRemainingHp($seat)) return null;
        $mitigation = false;
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            if (_SWUBotLvClaimnotlethalMitigates($seat, strval($o->CardID ?? ''))) { $mitigation = true; break; }
        }
        if (!$mitigation) return null;
        $out = array_values(array_filter($actions, fn($a) => SWUBotActionKind($a) !== 'initiative'));
        return empty($out) ? null : $out;
    },
]);
