<?php
// LEVER 'unitylethal' (theme 'postban'; Force Fam post-ban team read 2026-11-12, lever H1). Unity of Purpose JTL_106 ("For each friendly
// unit with a different name, give each unit you control +1/+1 for this phase") is the post-ban Wicket closer: Dralack cast it 6 times,
// always as the round's FIRST action, R5-R6, and won that round 6/6 (10948ce9, fb3a981e, 356d3bef, 8f77b915, 4da375e0, 81a73fed). It is
// double Command, so on Wicket + Aldhani Garrison LAW_022 it costs 8 unless the base's Epic Action waives one penalty — all 6 human casts
// went through it. The bot cast it 41 times (via the Epic, mostly R7) and only 22 of those games ended that round: it is a flat 'buff'
// tag, and lethal-now never counts a buff in hand. Here, with Unity in hand:
//   lethal — the buff on every ready attacker that can reach their base (no enemy Sentinel in its arena, or Saboteur), plus their power,
//            meets their base's remaining HP: cast it now, first (from hand, or the Epic Action and then Unity at its prompt);
//   not lethal — hold it (the hand play, and the Epic Action when Unity is the only card that action would unlock).
// Hooks: 'play', 'ability' (base Epic), 'decision' (the waiver's "Play_a_card_(ignore …)" prompt).

const SWU_BOT_LV_UNITY_ID = 'JTL_106';
const SWU_BOT_LV_UNITY_GO = 10.0;
const SWU_BOT_LV_UNITY_HOLD = -0.4;

function _SWUBotLvUnityLethal(int $seat): bool {
    $mine = SWUBotUnits($seat);
    $names = [];
    foreach ($mine as $u) $names[strval(CardTitle($u['cardID']))] = true;
    $n = count($names);
    if ($n === 0) return false;
    $opp = SWUBotOpponent($seat);
    $guarded = [];
    foreach (SWUBotUnits($opp) as $e) if ($e['sentinel']) $guarded[$e['arena']] = true;
    $dmg = 0;
    foreach ($mine as $u) {
        if (!$u['ready'] || (($guarded[$u['arena']] ?? false) && !$u['saboteur'])) continue;
        $dmg += intval($u['attackPower']) + $n;
    }
    return $dmg >= SWUBaseRemainingHp($opp);
}

function _SWUBotLvUnityHandIndex(int $seat): ?int {
    foreach (GetHand($seat) as $i => $o) if ($o !== null && empty($o->removed) && strval($o->CardID ?? '') === SWU_BOT_LV_UNITY_ID) return intval($i);
    return null;
}

SWUBotLeverRegister('postban', 'unitylethal', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($cid !== SWU_BOT_LV_UNITY_ID) return null;
        _SWUBotFired('unitylethal', $seat);
        return _SWUBotLvUnityLethal($seat) ? SWU_BOT_LV_UNITY_GO : min($v, SWU_BOT_LV_UNITY_HOLD);
    },
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'base-epic' || _SWUBotLvUnityHandIndex($seat) === null) return null;
        if (_SWUBotCardAlreadyPlayable($seat, GetHand($seat)[_SWUBotLvUnityHandIndex($seat)])) return null;   // no waiver needed
        if (_SWUBotLvUnityLethal($seat)) { _SWUBotFired('unitylethal', $seat); return SWU_BOT_LV_UNITY_GO; }
        // Not lethal: hold the waiver when Unity is the only off-aspect card in hand it would unlock.
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed) || strval($o->CardID ?? '') === SWU_BOT_LV_UNITY_ID) continue;
            if (SWUAspectPenalty($seat, strval($o->CardID ?? '')) > 0) return null;
        }
        _SWUBotFired('unitylethal', $seat);
        return SWU_BOT_LV_UNITY_HOLD;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!str_starts_with($tip, 'Play_a_card_(ignore') || !preg_match('/^myHand-(\d+)$/', $c, $m)) return null;
        if (strval((GetHand($seat)[intval($m[1])] ?? null)->CardID ?? '') !== SWU_BOT_LV_UNITY_ID) return null;
        return _SWUBotLvUnityLethal($seat) ? SWU_BOT_LV_UNITY_GO : null;
    },
]);
