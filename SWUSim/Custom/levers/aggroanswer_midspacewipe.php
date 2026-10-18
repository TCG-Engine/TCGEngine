<?php
// LEVER 'midspacewipe' (theme 'aggroanswer', G4-40 talzin-hsd-and-space-vs-vader). Talzin Yellow/Red play as midrange, so the control wing's
// wipe rules are off: vs Vader Yellow Talzin makes 0.05-0.20 unit attacks in R1-4 (ground units cannot reach space) and takes 26.6-28.0 by R5
// in losses; Hyperspace Disaster is held (0-11% resourced) but cast in only 5-6% of games. Gaps: Yellow -30, Red -29.
// filter — a MIDRANGE seat (archetype rank 2) facing a space-flavoured aggro leader with 3+ enemy space units borrows the control wing's
// 'control-wipe' (a castable space wipe that stabilises) and 'initiative-for-wipe' (claim to cast it next round before the ships swing),
// both judged exactly as for a soft-control seat; the list is cut to the move they pick. Never while lethal is on either side, and never a
// wipe 'wipewaitspilot' holds (when that lever is on too).
const SWU_BOT_LV_MIDSPACEWIPE_SHIPS = 3;

function _SWUBotLvMidspacewipeIsSpaceWipe(string $cid): bool {
    return in_array('wipe', SWUBotCardTags($cid), true) && _SWUBotWipeArenas($cid) === ['Space'];
}

SWUBotLeverRegister('aggroanswer', 'midspacewipe', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play' || strval(GetCurrentPhase()) !== 'MAIN' || SWUBotStyleRank(strval($ctx['style'] ?? '')) !== 2) return null;
        $seat = intval($ctx['seat']); $opp = intval($ctx['opp'] ?? SWUBotOpponent($seat));
        if (!SWUBotOpponentIsAggroLeader($seat) || !in_array('space', SWUBotDeckFlavours($opp), true)) return null;
        if (count(array_filter(SWUBotUnits($opp), fn($u) => $u['arena'] === 'Space')) < SWU_BOT_LV_MIDSPACEWIPE_SHIPS) return null;
        $hasWipe = false;
        foreach (_SWUBotHandCards($seat) as $hc) if (_SWUBotLvMidspacewipeIsSpaceWipe($hc)) { $hasWipe = true; break; }
        if (!$hasWipe || SWUBotLethalNow($seat, $opp) || SWUBotClock($opp, $seat) <= 1) return null;
        $held = fn(string $cid) => SWUBotProposalOn('wipewaitspilot') && function_exists('_SWUBotLvWipewaitspilotHolds') && _SWUBotLvWipewaitspilotHolds($seat, $cid);
        $as = array_merge($ctx, ['style' => 'softcontrol', 'actions' => array_values($actions)]);
        // 1. A castable space wipe that qualifies for the control wing's rule.
        $wipes = [];
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $cid = strval($o->CardID ?? '');
            if (_SWUBotLvMidspacewipeIsSpaceWipe($cid) && !$held($cid)) $wipes[] = $a;
        }
        if (!empty($wipes) && ($pick = SWUBotRuleControlWipe(array_merge($as, ['actions' => $wipes]))) !== null) return [$pick];
        if (!empty($wipes)) return null;
        // 2. Not castable now: claim for it as the control wing would.
        $w = _SWUBotWipeNextRound($seat);
        if ($w === null || !_SWUBotLvMidspacewipeIsSpaceWipe(strval($w['cid'])) || $held(strval($w['cid']))) return null;
        $init = SWUBotRuleInitiativeForWipe($as);
        return $init !== null ? [$init] : null;
    },
]);
