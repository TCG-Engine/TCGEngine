<?php
// LEVER 'chimaeranoleader' (theme 'engines', G4-14 never-chimaera-the-engine-leader). ASH_052 Chimaera's friendly pick was the deployed Grand
// Admiral Thrawn 29 times (Yellow) and 27 (DV) — top or second pick — while Ant Droid (14) and Onyx (13) were in play in many of those
// games. 'doomedsac' 2a allows the leader "only when no other fodder"; 'leaderrisk' makes it read cheap.
// sacrifice — a deployed leader unit is never the sacrifice (priced at SWU_BOT_NEVER_SAC_COST): not while another friendly non-leader unit
// exists, unless it is doomed AND its unit side has no "When you …" engine text; and when it is the only fodder, never (Chimaera then plays
// without the paired defeat — its "You may" is declined, the friendly pick scoring below PASS).

SWUBotLeverRegister('engines', 'chimaeranoleader', [
    'sacrifice' => function (float $cost, array $v): ?float {
        if (empty($v['isLeader']) || $cost >= SWU_BOT_NEVER_SAC_COST) return null;
        $seat = intval($v['controller']);
        $others = false;
        foreach (SWUBotUnits($seat) as $u) if ($u['uid'] !== $v['uid'] && empty($u['isLeader'])) { $others = true; break; }
        $engine = (bool)preg_match('/\bWhen you\b/i', strval(CardDeployText(strval($v['cardID'])) ?? ''));
        if ($others && !$engine && SWUBotUnitIsDoomed($v)) return null;
        return SWU_BOT_NEVER_SAC_COST;
    },
]);
