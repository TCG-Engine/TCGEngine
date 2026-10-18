<?php
// LEVER 'wipewaitspilot' (theme 'aggroanswer', G4-03 space-wipe-waits-for-the-pilot). Opponents' first Hyperspace Disaster cast while Luke
// JTL's host lived: Luke won 30% of 217 games; cast BEFORE Luke deployed: 57% of 142 — he redeploys onto a fresh fighter and swings 7-9 a
// round. The sampled Krennic games fired it on 3 cheap fighters one round before the pilot landed.
// filter — a midrange / control seat holds a SPACE-only wipe while an enemy pilot leader ("Deploy this leader as an upgrade on a friendly
// Vehicle") is undeployed and can deploy within a round (its resources + 1 reach the threshold), unless the wipe already kills 3+ enemy
// units or 7+ cost of enemy space units, or my base is within 2 rounds of dying. ('wipeinit' then claims for it the round the pilot lands.)
const SWU_BOT_LV_WIPEWAITSPILOT_KILLS = 3;
const SWU_BOT_LV_WIPEWAITSPILOT_COST = 7;

// Some enemy pilot leader is undeployed and can deploy within a round.
function _SWUBotLvWipewaitspilotPilotSoon(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) {
        foreach (GetLeader($o) as $l) {
            if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
            if (in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true)) continue;
            if (!preg_match('/Deploy this leader as an upgrade on a friendly Vehicle/i', strval(CardText(strval($l->CardID ?? ''))))) continue;
            $t = _SWUBotLeaderThreshold($o, $l);
            if ($t > 0 && SWUResourceCount($o) + 1 >= $t) return true;
        }
    }
    return false;
}
// Should this space-wipe play wait? (Shared with 'midspacewipe' so that lever never forces a wipe this one holds.)
function _SWUBotLvWipewaitspilotHolds(int $seat, string $cid): bool {
    if (!in_array('wipe', SWUBotCardTags($cid), true) || _SWUBotWipeArenas($cid) !== ['Space']) return false;
    if (!_SWUBotLvWipewaitspilotPilotSoon($seat)) return false;
    foreach (SWUBotOpponents($seat) as $o) if (SWUBotClock($o, $seat) <= 2) return false;
    $kills = 0; $cost = 0;
    foreach (SWUBotEnemyUnits($seat) as $u) {
        if ($u['arena'] !== 'Space' || SWUBotDefeatFizzles($u)) continue;
        $kills++; $cost += max(0, intval($u['cost']));
    }
    return $kills < SWU_BOT_LV_WIPEWAITSPILOT_KILLS && $cost < SWU_BOT_LV_WIPEWAITSPILOT_COST;
}

SWUBotLeverRegister('aggroanswer', 'wipewaitspilot', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play' || SWUBotStyleRank(strval($ctx['style'] ?? '')) < 2) return null;
        $seat = intval($ctx['seat']);
        $out = []; $cut = false;
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) === 'play' && ($o = _SWUBotHandObject($seat, $a)) !== null && _SWUBotLvWipewaitspilotHolds($seat, strval($o->CardID ?? ''))) { $cut = true; continue; }
            $out[] = $a;
        }
        return ($cut && !empty($out)) ? $out : null;
    },
]);
