<?php
// LEVER 'widewipe' (theme 'controlread', report S4; owner 2026-10-19: "when you see the opponent going wide, it is a good time to wipe" —
// cast the wipe rather than sandbag it, above all while they are stripping my hand). The wipe gates (wipegate / wipekeep / threathold)
// can hold a castable unbounded wipe while the board widens; Garindan + HFQ then took 2 SRIs and an Annihilator from Ninin (8996, d6f).
// A wipe that defeats 3+ enemy units, at least 2 more of theirs than mine, AND more of their unit value than mine gets a bonus per unit of
// that margin; x1.5 once the opponent has looked at my hand (SWUHandSeenSnapshot) — the held wipe is the card they will take.
// Units the wipe cannot defeat (SWUBotDefeatFizzles) are not counted; "non-leader" wipes (LAW_096) leave leaders out (final review M8).
const SWU_BOT_LV_WIDEWIPE = 0.5;   // x W[kill] per unit of (their units hit - mine)

// ['arena' => 'all'|'Space'|'Ground', 'leaders' => bool] for an unbounded "defeat all/each [non-leader] [space|ground] units", else null.
function _SWUBotLvWideWipeShape(string $cid): ?array {
    $t = strval(CardText($cid));
    if (preg_match('/total of \d+ or less|isn\'t upgraded/i', $t)) return null;   // budget wipes / Nebula: not this lever's shape
    if (!preg_match('/Defeat (?:all|each) (non-leader )?(?:(space|ground) )?units?\b/i', $t, $m)) return null;
    return ['arena' => empty($m[2]) ? 'all' : ucfirst(strtolower($m[2])), 'leaders' => empty($m[1])];
}

SWUBotLeverRegister('controlread', 'widewipe', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!in_array('wipe', SWUBotCardTags($cid), true) || ($sh = _SWUBotLvWideWipeShape($cid)) === null) return null;
        $hit = fn(array $u) => ($sh['arena'] === 'all' || $u['arena'] === $sh['arena']) && ($sh['leaders'] || empty($u['isLeader'])) && !SWUBotDefeatFizzles($u);
        $theirs = array_values(array_filter(SWUBotEnemyUnits($seat), $hit));
        $mine = array_values(array_filter(SWUBotUnits($seat), $hit));
        $margin = count($theirs) - count($mine);
        if (count($theirs) < 3 || $margin < 2) return null;
        $value = fn(array $us) => array_sum(array_map('SWUBotUnitValue', $us));
        if ($value($theirs) <= $value($mine)) return null;   // three tokens against my one 8-drop is not a good wipe
        $seen = false;
        foreach (SWUBotOpponents($seat) as $o) if (SWUHandSeenSnapshot(intval($o), $seat) !== null) $seen = true;
        return $v + SWU_BOT_LV_WIDEWIPE * floatval($W['kill']) * $margin * ($seen ? 1.5 : 1.0);
    },
]);
