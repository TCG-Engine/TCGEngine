<?php
// LEVER 'pilothostkill' (theme 'aggroanswer', G4-02 pilot-host-is-the-target). A pilot LEADER deployed onto a Vehicle (Luke JTL_012,
// Vader JTL_006, Boba JTL_009, Rio JTL_015) makes that host a leader unit that swings 7-9 a round: Luke's host dealt 11.9 base damage a game
// in his wins vs 4.8 in losses and lived a median 1 round, while opponents attacked their base 4,136 times and the host 95.
//   killextra — a kill of such a host earns the base damage it would deal over the next 2 rounds, for EVERY style ('leaderkill', p44,
//               already pays this to midrange/control kills, so here only where it does not apply).
//   target    — a non-lethal hit that leaves the host inside the reach of another ready unit of mine in its arena this phase (the kill is
//               set up) earns its share (attack / HP left) of that kill.
// Not built: (b) the removal picker already takes the host (59/66 casts); (c) arena-aware blocker play needs a rule hook.
const SWU_BOT_LV_PILOTHOSTKILL_ROUNDS = 2;

// A leader unit that is a Vehicle carrying a pilot LEADER upgrade.
function _SWUBotLvPilothostkillIsHost(array $v): bool {
    if (empty($v['isLeader']) || !isset($v['obj']) || !str_contains(strval(CardTrait(strval($v['cardID'])) ?? ''), 'Vehicle')) return false;
    foreach (GetUpgradesOnUnit($v['obj']) as $s) {
        $sid = strval($s->CardID ?? '');
        if (str_contains(strval(CardType($sid)), 'Leader') && str_contains(strval(CardTrait($sid) ?? ''), 'Pilot')) return true;
    }
    return false;
}
function _SWUBotLvPilothostkillThreat(array $def, array $W): float {
    return SWU_BOT_LV_PILOTHOSTKILL_ROUNDS * floatval($W['base']) * max(0, intval($def['attackPower']));
}

SWUBotLeverRegister('aggroanswer', 'pilothostkill', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        if (!_SWUBotLvPilothostkillIsHost($def)) return null;
        // 'leaderkill' already adds the same 2 rounds for a midrange / control killer.
        if (SWUBotFeatureOn('leaderkill') && intval($W['archetypeRank'] ?? 0) >= 2) return null;
        return $extra + _SWUBotLvPilothostkillThreat($def, $W);
    },
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null || !_SWUBotLvPilothostkillIsHost($def) || intval($def['shields']) > 0) return null;
        if (SWUBotCombatOutcome($att, $def) !== 'bounce') return null;
        $left = intval($def['remaining']) - max(0, intval($att['attackPower']));
        if ($left <= 0) return null;
        $seat = intval($att['controller']);
        // Another enemy Sentinel in the arena walls the follow-up off the host (a Saboteur ignores it).
        $walled = false;
        foreach (SWUBotUnits(intval($def['controller'])) as $u) if ($u['uid'] !== $def['uid'] && $u['arena'] === $def['arena'] && !empty($u['sentinel'])) $walled = true;
        $reach = 0;
        foreach (SWUBotUnits($seat) as $u) {
            if ($u['uid'] === $att['uid'] || empty($u['ready']) || $u['arena'] !== $def['arena'] || ($walled && empty($u['saboteur']))) continue;
            $reach = max($reach, intval($u['attackPower']));
        }
        if ($reach < $left) return null;
        $kill = floatval($W['kill']) * SWUBotUnitValue($def) + _SWUBotLvPilothostkillThreat($def, $W);
        return $tv + min(1.0, max(0, intval($att['attackPower'])) / max(1, intval($def['remaining']))) * $kill;
    },
]);
