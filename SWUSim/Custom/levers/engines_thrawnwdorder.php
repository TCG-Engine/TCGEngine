<?php
// LEVER 'thrawnwdorder' (theme 'engines', G2-23 thrawn-wd-turn-keeps-the-leader-ready). JTL_002 Thrawn reuses a When Defeated once a round
// (front: exhaust; deployed: once a round): ~0.9 reuses a game, and the WD units are traded in the same rounds the leader is spent on
// nothing better. A small When Defeated (Onyx Squadron Brute's heal 2) spent the round's reuse before a big one (The Legacy Run, Morgan
// Elsbeth, Karis with the Force) could die. 'thrawnwd' (p42) declines only a reuse that helps an opponent.
// decision — Thrawn's "use that ability again" (THRAWN_REUSE YESNO) is declined while another friendly unit whose When Defeated is worth more
// than 1.5x this one (+0.5) is in play and can die this round (doomed, or an enemy ready unit in its arena hits that hard). Otherwise no
// opinion. ('wdemptyboard''s no-target decline is folded in when that lever is on.)

// A When Defeated's value now, for $seat (score points): damage by what it kills, a -N/-N by the unit it defeats, heal / draw / create.
function _SWUBotLvThrawnwdorderWdValue(int $seat, string $cid, array $W): float {
    if (!preg_match('/When Defeated:(.*)$/is', strval(CardText($cid)), $m)) return 0.0;
    $wd = strval($m[1]);
    if (_SWUBotWdHelpsOpponent($wd)) return 0.0;
    $enemies = SWUBotEnemyUnits($seat);
    if (_SWUBotWdDamage($cid) !== null) return _SWUBotWdBoardValue($seat, $cid, $enemies, $W)[0];
    if (preg_match('/use the Force/i', $wd) && !(function_exists('PlayerHasTheForce') && PlayerHasTheForce($seat))) return 0.0;
    if (preg_match('/give a unit -\d+\/-(\d+)/i', $wd, $dm)) {
        $b = 0.0;
        foreach ($enemies as $e) if (intval($e['remaining']) <= intval($dm[1])) $b = max($b, floatval($W['kill']) * SWUBotUnitValue($e));
        return $b;
    }
    if (preg_match('/\bheal (\d+)/i', $wd, $hm)) return floatval($W['heal']) * min(intval($hm[1]), intval((GetBase($seat)[0] ?? null)->Damage ?? 0));
    if (preg_match('/\bdraw (a card|\d+)/i', $wd)) return floatval($W['draw'] ?? 0.0);
    if (preg_match('/\bcreate (a|an|\d+)\b/i', $wd)) return floatval($W['develop']) * 2;
    return 0.1;
}
function _SWUBotLvThrawnwdorderCanDie(int $seat, array $v): bool {
    if (SWUBotUnitIsDoomed($v)) return true;
    if (intval($v['shields']) > 0) return false;
    foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready']) && $e['arena'] === $v['arena'] && intval($e['attackPower']) >= intval($v['remaining'])) return true;
    return false;
}
// Should this reuse wait for a bigger When Defeated this round?
function _SWUBotLvThrawnwdorderWaits(int $seat, string $src, string $srcMz, array $W): bool {
    $mine = _SWUBotLvThrawnwdorderWdValue($seat, $src, $W);
    foreach (SWUBotUnits($seat) as $u) {
        if (!empty($u['isLeader']) || strval($u['cardID']) === $src) continue;
        $o = _SWUBotLvThrawnwdorderWdValue($seat, strval($u['cardID']), $W);
        if ($o > 1.5 * $mine + 0.5 && _SWUBotLvThrawnwdorderCanDie($seat, $u)) return true;
    }
    return false;
}

SWUBotLeverRegister('engines', 'thrawnwdorder', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'YESNO' || $head !== 'THRAWN_REUSE' || !in_array($c, ['YES', 'NO'], true)) return null;
        $p = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
        $src = strval($p[2] ?? ''); $mz = strval($p[3] ?? '');
        if ($src === '') return null;
        $no = _SWUBotLvThrawnwdorderWaits($seat, $src, $mz, $W)
            || (SWUBotProposalOn('wdemptyboard') && function_exists('_SWUBotLvWdemptyboardNoTarget') && _SWUBotLvWdemptyboardNoTarget($seat, $src));
        if (!$no) return null;
        return $c === 'NO' ? 0.1 : 0.0;
    },
]);
