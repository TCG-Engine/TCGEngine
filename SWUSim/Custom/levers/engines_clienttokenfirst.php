<?php
// LEVER 'clienttokenfirst' (theme 'engines', ASH lever hunt G3-29). Off by default: '@try-clienttokenfirst' / '@try-engines'.
// LAW_016 The Client: front "Action [Exhaust]: If you created a token this phase, exhaust an enemy unit"; deployed "On Attack: If you
// created a token this phase, exhaust an enemy unit". The Action ran in 39% of pre-deploy rounds (claims without it 55%) while token
// makers (Black Sun Patroller — a Shield is a token — 78% played, Shin Hati 72%, ST-149 22%) sat in hand most games.
// Sequenced: a castable token maker (a Shielded unit, a card that gives a Shield or creates a token) goes first — it carries the
// exhaust it switches on (the base damage of the enemy's best ready attacker, denied) while the Client is ready and no token was made
// yet; the front Action waits until a token exists, and is then worth that exhaust; deployed, the Client's attack waits for the token play.

function _SWUBotLvClienttokenfirstMaker(string $cid): bool {
    foreach (SWUBotCardTags($cid) as $t) {
        if ($t === 'shielded' || $t === 'gives-shield' || (str_starts_with($t, 'create-') && str_ends_with($t, '-token'))) return true;
    }
    return false;
}
function _SWUBotLvClienttokenfirstTokenMade(int $seat): bool {
    return GlobalEffectCount($seat, 'SWU_CREATED_TOKEN') > 0;
}
function _SWUBotLvClienttokenfirstBestReadyEnemy(int $seat): int {
    $best = 0;
    foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready'])) $best = max($best, intval($e['attackPower']));
    return $best;
}
// Front (undeployed, ready) or deployed (ready leader unit) Client of mine: 'front' | 'unit' | null.
function _SWUBotLvClienttokenfirstClient(int $seat): ?string {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || strval($l->CardID ?? '') !== 'LAW_016') continue;
        if (!in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) return _SWUBotLeaderReady($l) ? 'front' : null;
        foreach (SWUBotUnits($seat) as $u) if (!empty($u['isLeader']) && $u['cardID'] === 'LAW_016' && !empty($u['ready'])) return 'unit';
    }
    return null;
}
// The best castable token-maker play's score (> 0), or null.
function _SWUBotLvClienttokenfirstMakerPlay(array $ctx, int $seat): ?float {
    $best = null;
    $GLOBALS['SWUBotLvClienttokenfirstDepth'] = 1;
    try {
        foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            if (!_SWUBotLvClienttokenfirstMaker(strval($o->CardID ?? ''))) continue;
            $s = SWUBotScoreAction($ctx, $a, intval($i));
            if ($s > 0.0) $best = max($best ?? $s, $s);
        }
    } finally { $GLOBALS['SWUBotLvClienttokenfirstDepth'] = 0; }
    return $best;
}

SWUBotLeverRegister('engines', 'clienttokenfirst', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'LAW_016' || _SWUBotLeaderFront($seat, 'LAW_016') === null) return null;
        if (_SWUBotLvClienttokenfirstTokenMade($seat)) {
            $p = _SWUBotLvClienttokenfirstBestReadyEnemy($seat);
            return $p > 0 ? floatval($W['ability']) + floatval($W['base']) * $p : null;
        }
        return _SWUBotLvClienttokenfirstMakerPlay($ctx, $seat) !== null ? -0.5 : null;
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvClienttokenfirstDepth']) || !_SWUBotLvClienttokenfirstMaker($cid) || _SWUBotLvClienttokenfirstTokenMade($seat)) return null;
        if (_SWUBotLvClienttokenfirstClient($seat) === null || ($p = _SWUBotLvClienttokenfirstBestReadyEnemy($seat)) <= 0 || $v <= 0.0) return null;
        return $v + floatval($W['base']) * $p;
    },
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (empty($att['isLeader']) || strval($att['cardID'] ?? '') !== 'LAW_016' || _SWUBotLvClienttokenfirstTokenMade($seat)) return null;
        if (_SWUBotLvClienttokenfirstBestReadyEnemy($seat) <= 0 && empty(SWUBotEnemyUnits($seat))) return null;
        return _SWUBotLvClienttokenfirstMakerPlay($ctx, $seat) !== null ? min($s, -0.4) : null;
    },
]);
