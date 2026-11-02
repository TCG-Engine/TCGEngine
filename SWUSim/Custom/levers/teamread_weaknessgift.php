<?php
// LEVER 'weaknessgift' (theme 'teamread', team games 2026-10-29, report D2). A Weakness token is a TOKEN UPGRADE its host's controller
// controls (CR 3.50.f, SWUFriendlyTokenMzIDs), so some decks cash it: Han Solo LAW_017 defeats it to ping, Alliance Outpost LAW_019's Epic
// defeats it for a Shield / Experience / Credit, Shuttle ST-149 JTL_242 moves it, Reforge ASH_090 / Pegasus Tri-Wing ASH_171 defeat it for
// value. Hemlock's 5 Tatooine and 3 Red backfires all came from such gives, and every one of those games was lost. A give that kills (the
// token defeats the unit) is unchanged. One that only softens, on a unit whose controller shows a live sink:
//   REPEATABLE sink (a leader that defeats friendly tokens — Han's Action / On Attack; a unit in play that moves or defeats upgrades) — capped
//     just below a pass ('weaknessgift.cap'); the cap stays above most gives onto my OWN units, so a mandatory pick still lands on the enemy;
//   ONE-SHOT sink (an unused "Epic Action [defeat a friendly token]" base; a Reforge / Pegasus already in their discard) — it eats one token,
//     so the soften keeps 'weaknessgift.oneshot' of its value (smoke 2026-10-29: a full cap held Hemlock's Action all game against an
//     unused Outpost, and the game flipped to a loss).
// Sinks are public: their leader / base text, their units in play, and their discard pile (a sink they already played). Never their hand.

// 2 = a repeatable sink, 1 = one-shot only, 0 = none.
function _SWUBotTrWeaknessSink(int $opp): int {
    $tokenRe = '/defeat (?:a|any number of) friendly tokens?/i';
    $moveRe = '/take control of a token upgrade/i';
    $upRe = '/defeat (?:a friendly upgrade|an upgrade on a friendly unit)/i';
    foreach (GetLeader($opp) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $cid = strval($l->CardID ?? '');
        $deployed = in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true);
        if (preg_match($tokenRe, strval($deployed ? CardDeployText($cid) : CardText($cid)))) return 2;
    }
    foreach (SWUBotUnits($opp) as $u) {
        $t = strval(CardText($u['cardID']));
        if (preg_match($moveRe, $t) || preg_match($tokenRe, $t)) return 2;
    }
    $base = GetBase($opp)[0] ?? null;
    if ($base !== null && !in_array(strval($base->EpicActionUsed ?? 'false'), ['true', '1'], true)
        && preg_match('/Epic Action \[defeat a friendly token\]/i', strval(CardText(strval($base->CardID ?? ''))))) return 1;
    foreach (GetDiscard($opp) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        if (preg_match($upRe, strval(CardText(strval($o->CardID ?? ''))))) return 1;
    }
    return 0;
}

SWUBotLeverRegister('teamread', 'weaknessgift', [
    'weakness' => function (float $score, int $seat, array $v, bool $enemy, int $weak, array $W, int $loss): ?float {
        if (!$enemy || $score <= 0.0 || SWUBotDamageKills($v, $loss)) return null;   // mine, already below a pass, or a kill
        $opp = intval($v['controller'] ?? 0);
        if ($opp <= 0 || $opp === $seat || ($sink = _SWUBotTrWeaknessSink($opp)) === 0) return null;
        return $sink === 2 ? min($score, SWUBotLeverNum('weaknessgift.cap', -0.25)) : $score * SWUBotLeverNum('weaknessgift.oneshot', 0.5);
    },
]);
