<?php
// LEVER 'leaderanswerhold' (theme 'teamread', report D9; team games 2026-10-29). Maul held Legacy / Anakin until the enemy leader deployed
// and won 3 of 3; firing it first into a small unit lost (8c341c65). Krennic's deploy shot killed 12 enemy leaders. 'threathold' (p5) holds
// a bomb-killer only while every target is cheap and harmless, and only for control seats; nothing keeps an answer for a leader that is
// about to land. Here, any seat holds its ONLY big answer — a 'removal' card that can take the enemy leader unit (_SWUBotTrLahKillsLeader)
// — while an enemy leader is undeployed and inside its deploy window (it can deploy now, or became able to within the
// last SWU_BOT_TR_LAH_WINDOW rounds), and the best unit the answer kills now costs less than that leader.
// Released when: I hold 2+ such answers (spend one, keep one), their clock on me is <= 1, or the window has passed (a leader that stays home
// — Hemlock's Action leader — never pins the answer for long).
const SWU_BOT_TR_LAH_WINDOW = 1;   // rounds after the deploy threshold the hold still applies (0 = only the round they first can)

// The undeployed enemy leader inside its deploy window: ['cid', 'hp', 'cost'] or null.
function _SWUBotTrLahLeader(int $seat): ?array {
    if (!function_exists('_SWUBotLeaderThreshold')) return null;
    $truthy = fn($v) => !empty($v) && strval($v) !== 'false';
    foreach (SWUBotOpponents($seat) as $opp) {
        $r = count(array_filter(GetResources(intval($opp)), fn($o) => $o !== null && empty($o->removed)));
        foreach (GetLeader(intval($opp)) as $l) {
            if ($l === null || !empty($l->removed) || $truthy($l->Deployed ?? null) || $truthy($l->EpicActionUsed ?? null)) continue;
            if (($th = _SWUBotLeaderThreshold(intval($opp), $l)) <= 0) continue;   // no deploy gate to time (Grogu, Flipatine)
            $surplus = $r - $th;
            if ($surplus < 0 || $surplus > intval(SWUBotLeverNum('lah.window', SWU_BOT_TR_LAH_WINDOW))) continue;
            $cid = strval($l->CardID ?? '');
            if (intval(CardHp($cid)) <= 0) continue;   // no unit side
            return ['cid' => $cid, 'hp' => intval(CardHp($cid)), 'cost' => max(SWU_BOT_LEADER_TARGET_WORTH, intval(CardCost($cid)))];
        }
    }
    return null;
}
// An answer for that leader: a 'removal' card that may target a leader and either defeats a unit outright, or gives -N/-N with N >= 3 (the
// pilots' Anakin -3/-3: it finishes a leader after one attack). Conditional defeats ("with 2 or less remaining HP", "costs 2 or less", "that
// dealt damage to a base") are not answers to a fresh leader. Most HMW removal reads "non-leader" — those never count.
function _SWUBotTrLahKillsLeader(string $cid, array $leader): bool {
    if (!in_array('removal', SWUBotCardTags($cid), true)) return false;
    $t = strval(CardText($cid));
    if (stripos($t, 'non-leader') !== false) return false;
    if (preg_match('/-(\d+)\/-\d+/', str_replace('–', '-', $t), $m)) return intval($m[1]) >= intval(SWUBotLeverNum('lah.shrink', 3));
    return (bool)preg_match('/\bdefeat (?:an enemy|a) unit\b(?! with| that)/i', $t);
}

SWUBotLeverRegister('teamread', 'leaderanswerhold', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || !in_array('removal', SWUBotCardTags($cid), true) || !_SWUBotLvIsViewer($seat)) return null;
        if (($leader = _SWUBotTrLahLeader($seat)) === null || !_SWUBotTrLahKillsLeader($cid, $leader)) return null;
        $answers = 0;
        foreach (_SWUBotHandCards($seat) as $h) if (_SWUBotTrLahKillsLeader($h, $leader)) $answers++;
        if ($answers >= 2) return null;                                           // keep one, spend one
        $best = 0;
        foreach (SWUBotEnemyUnits($seat) as $e) if (SWUBotRemovalKills($cid, $e)) $best = max($best, $e['isLeader'] ? SWU_BOT_LEADER_TARGET_WORTH : intval($e['cost']));
        if ($best >= $leader['cost']) return null;                                // a target as good as the leader is here now
        if (function_exists('_SWUBotLvTheirClock') && _SWUBotLvTheirClock($seat)['clock'] <= 1) return null;   // dying: use it
        return min($v, -0.4);
    },
]);
