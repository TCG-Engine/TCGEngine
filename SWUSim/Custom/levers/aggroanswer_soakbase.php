<?php
// LEVER 'soakbase' (theme 'aggroanswer', G2-33 indirect-soak-to-base-while-healthy). Incoming indirect damage went on UNITS in 65% of soft
// control's assignments and 82% of midrange's (hard control 22%); trace piett_lukejtl R2-R4 spread 3s onto Partisans / GNK / Piett at 25+
// base HP and one GNK died.
// decision — my own indirect assignment (MZSPLITASSIGN "…indirect…" with only my base and units in the candidate), for a midrange / control
// seat (rank >= 2) whose base is above half its HP: on top of the split pricing, a share that DEFEATS my unit costs that loss again, and one
// that brings my Sentinel inside the reach of an enemy attacker in its arena costs 0.75 of it — so the points go to the base.
const SWU_BOT_LV_SOAKBASE_REACH = 0.75;

SWUBotLeverRegister('aggroanswer', 'soakbase', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'MZSPLITASSIGN' || stripos($tip, 'indirect') === false || stripos($tip, 'heal') !== false || intval($W['archetypeRank'] ?? 0) < 2) return null;
        $base = GetBase($seat)[0] ?? null;
        $hp = $base !== null ? intval(CardHp(strval($base->CardID ?? ''))) : 0;
        if ($hp <= 0 || 2 * SWUBaseRemainingHp($seat) <= $hp) return null;
        $extra = 0.0;
        foreach (explode(',', $c) as $pair) {
            $bits = explode(':', $pair);
            if (count($bits) < 2 || intval($bits[1]) <= 0) continue;
            $mz = trim($bits[0]); $amt = intval($bits[1]);
            if (SWUBotIsEnemyMz($seat, $mz)) return null;   // not my own assignment
            if (str_contains($mz, 'Base')) continue;
            $v = SWUBotViewForMz($seat, $mz);
            if ($v === null) continue;
            if ($amt >= intval($v['remaining'])) { $extra -= floatval($W['loss']) * SWUBotUnitValue($v); continue; }
            if (!empty($v['sentinel']) && function_exists('_SWUBotSoakIntoReach') && _SWUBotSoakIntoReach($seat, $v, $amt))
                $extra -= SWU_BOT_LV_SOAKBASE_REACH * floatval($W['loss']) * SWUBotUnitValue($v);
        }
        return _SWUBotSplitScore($seat, $c, $W, true) + $extra;
    },
]);
