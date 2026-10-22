<?php
// Helpers for the 'tempo' levers (feature group p58, spec docs/superpowers/specs/2026-10-20-swusim-tempo-flavour-design.md §2). Registers
// no lever. A deck is tempo by DETECTION (BotTempo.php); its style decides the plan, the opponent's style (meta rank by leader + base)
// decides how the tempo pieces are spent.

function _SWUBotTempoOn(int $seat): bool {
    return function_exists('SWUBotSeatIsTempo') && SWUBotSeatIsTempo($seat);
}

// The focus opponent's style rank (0 hyperaggro … 4 hardcontrol) from the meta table; midrange (2.0) when the pair is unknown.
function _SWUBotTempoOppRank(int $seat): float {
    $opp = SWUBotOpponent($seat);
    $leader = strval((GetLeader($opp)[0] ?? null)->CardID ?? '');
    $base = strval((GetBase($opp)[0] ?? null)->CardID ?? '');
    return SWUBotLeaderBaseStyleRank($leader, $base) ?? 2.0;
}

// The opponent's READY unit with the highest attack power (tie: more remaining HP) — the swing a blunting piece should take — when it
// is a real threat (SWU_BOT_THREAT_WORTH power or more). Null otherwise.
function _SWUBotTempoBiggestAttacker(int $seat): ?array {
    $best = null;
    foreach (SWUBotEnemyUnits($seat) as $v) {
        if (empty($v['ready']) || intval($v['attackPower']) < SWU_BOT_THREAT_WORTH) continue;
        if ($best === null || intval($v['attackPower']) > intval($best['attackPower'])
            || (intval($v['attackPower']) === intval($best['attackPower']) && intval($v['remaining']) > intval($best['remaining']))) $best = $v;
    }
    return $best;
}

// A piece that blunts an attack: exhaust, bounce, debuff, Weakness, capture, a "doesn't ready" lock.
function _SWUBotTempoBlunts(string $cardID): bool {
    return in_array(SWUBotTempoPieceKind($cardID), ['exhaust', 'bounce', 'debuff', 'weakness', 'capture', 'lock'], true);
}

// "I can now play my big stuff" (owner, 2026-10-20): my ready resources pay for the most expensive card in my hand; with nothing costing
// 6+ in hand, for the most expensive card left in my deck.
function _SWUBotTempoBigStuff(int $seat): bool {
    $ready = SWUResourceCount($seat, true);
    $top = 0;
    foreach (_SWUBotHandCards($seat) as $cid) $top = max($top, _SWUBotSeatCost($seat, $cid));
    if ($top < 6) foreach (GetDeck($seat) as $o) if ($o !== null && empty($o->removed)) $top = max($top, intval(CardCost(strval($o->CardID ?? ''))));
    return $ready >= $top;
}

// Has this tempo deck reached its pivot (stop stalling, close)? Midrange: my leader has deployed (the fallback; deck plans may override).
// Control: I can play my big stuff. Aggro never stalls.
function _SWUBotTempoPivoted(int $seat, int $styleRank): bool {
    if ($styleRank <= 1) return true;
    // Feature 'talzinpivot' (p59): Talzin YELLOW "plays units to stall a bit and then close out with chimaera as well as Elzar mann" — her pivot
    // is the big stuff, not the deploy (owner: "the generic big stuff rule is good here").
    if (function_exists('_SWUBotTalzinColour') && SWUBotFeatureOn('talzinpivot') && _SWUBotTalzinColour($seat) === 'yellow') {
        _SWUBotFired('talzinpivot', $seat);
        return _SWUBotTempoBigStuff($seat);
    }
    if ($styleRank === 2) {
        foreach (SWUBotUnits($seat) as $u) if (!empty($u['isLeader'])) return true;
        return false;
    }
    return _SWUBotTempoBigStuff($seat);
}
