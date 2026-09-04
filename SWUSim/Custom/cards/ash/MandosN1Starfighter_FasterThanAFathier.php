<?php
// ASH_203
// Cost 2 - Mando's N-1 Starfighter - Faster than a Fathier - [Cunning,Heroism] - Power 1 - HP 3
// Text: Support / On Attack: You may exhaust a friendly (non-upgrade) leader. If you do, this unit gets +2/+0 for this attack.

// ASH_203 Mando's N-1 Starfighter — On Attack: you may exhaust a friendly (non-upgrade) leader. If you
// do, this unit gets +2/+0 for this attack. Offered only when a friendly leader is ready to pay.
//
// ⚠ "A FRIENDLY LEADER" IS NOT "LEADER 0". This read `GetLeader($player)[0]` on both the offer and the
// pay, which in Twin Suns (two leaders per seat) produced two live bugs from one hardcode: with the
// first leader already exhausted the ability never prompted at all, and with both ready it silently
// spent leader 0 with no choice offered (live report 2026-09-03). Sibling fixed alongside it: TWI_018
// Quinlan Vos exhausted $leaderArr[0] for "exhaust THIS leader" (CardID-keyed there — one instance).

// Every friendly leader that can pay the cost, as ['tok' => …, 'label' => …].
//   tok = "L{liveIndex}" for an UNDEPLOYED leader (the Leader-zone Ready flag is its exhaust state)
//       = the arena mzID for a DEPLOYED leader UNIT (its unit Status is its exhaust state)
// A leader deployed as a PILOT is excluded — that is exactly what the printed "(non-upgrade)" says, and
// such a leader lives as a Subcard on its host with DeployedUniqueID 0.
function _SWUAsh203ExhaustableLeaders(int $player): array {
    $out  = [];
    $arr  = &GetLeader($player);
    $live = 0;
    for ($i = 0; $i < count($arr); $i++) {
        if (!empty($arr[$i]->removed)) continue;
        $idx = $live; $live++;
        $l     = $arr[$i];
        $label = str_replace(' ', '_', (string)(CardTitle($l->CardID ?? '') ?? 'Leader'));
        if (!empty($l->Deployed)) {
            $duid = intval($l->DeployedUniqueID ?? 0);
            if ($duid <= 0) continue;                       // deployed as a Pilot upgrade → not eligible
            $mz = SWUFindMzByUID($duid);
            if ($mz === null) continue;
            $u = GetZoneObject($mz);
            if ($u === null || !empty($u->removed) || intval($u->Status ?? 0) !== 1) continue;
            $out[] = ['tok' => $mz, 'label' => $label];
        } else {
            if (empty($l->Ready)) continue;
            $out[] = ['tok' => "L{$idx}", 'label' => $label];
        }
    }
    // OPTIONCHOOSE submits the button's label VERBATIM, so two leaders must never share one. Titles are
    // distinct in practice; disambiguate with the subtitle rather than trusting that.
    $seen = [];
    foreach ($out as $k => $c) {
        if (!isset($seen[$c['label']])) { $seen[$c['label']] = true; continue; }
        $sub = str_replace(' ', '_', (string)(CardSubtitle(
            (SWUGetLeaderByIndex($player, intval(substr($c['tok'], 1))) ?? (object)['CardID' => ''])->CardID ?? '') ?? ''));
        $out[$k]['label'] = $c['label'] . ($sub !== '' ? "_-_{$sub}" : "_({$k})");
    }
    return $out;
}

// Pay the cost: exhaust the chosen leader. Returns false when it can no longer pay (it was exhausted or
// left play between the offer and the answer), which must leave the +2/+0 ungranted — "IF YOU DO".
function _SWUAsh203ExhaustLeader(int $player, string $tok): bool {
    if ($tok === '') return false;
    if ($tok !== '' && $tok[0] === 'L' && ctype_digit(substr($tok, 1))) {
        $l = SWUGetLeaderByIndex($player, intval(substr($tok, 1)));
        if ($l === null || !empty($l->removed) || empty($l->Ready)) return false;
        $l->Ready = false;
        return true;
    }
    $u = GetZoneObject($tok);
    if ($u === null || !empty($u->removed) || intval($u->Status ?? 0) !== 1) return false;
    OnExhaustCard($player, $tok);                 // honours "can't be exhausted" (LOF_040/LOF_073)
    if (intval($u->Status ?? 0) === 1) return false;
    // A deployed leader's exhaust state is its UNIT's Status — the Leader-zone Ready flag only gates the
    // undeployed Action. The old code flipped ONLY that flag, so the leader unit stayed ready and could
    // still attack after "paying" with it. Both are set now: the unit because that is the real cost, the
    // zone flag to keep the two faces consistent for anything that reads the leader entry.
    $l = SWUFindLeaderByDeployedUID($player, intval($u->UniqueID ?? 0));
    if ($l !== null) $l->Ready = false;
    return true;
}

$onAttackAbilities["ASH_203:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $cands = _SWUAsh203ExhaustableLeaders(intval($player));
    if (empty($cands)) return;                     // no leader able to pay → no offer
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    if (count($cands) === 1) {
        // One payer: a bare "you may" is the whole decision — a one-option picker would add a click.
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_your_leader_for_+2/+0_this_attack?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_203#0|{$uid}|{$cands[0]['tok']}", 1);
        return;
    }
    $labels = implode(',', array_column($cands, 'label'));
    $toks   = implode(',', array_column($cands, 'tok'));
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_a_friendly_leader_for_+2/+0_this_attack?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_203#1|{$uid}|{$labels}|{$toks}", 1);
};

// Single-payer branch: YES → exhaust it and grant the bonus.
$customDQHandlers["ASH_203#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!_SWUAsh203ExhaustLeader(intval($player), (string)($parts[1] ?? ''))) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUAddAttackPowerBonus($mz, 2);
};

// Multi-payer branch: YES → ask WHICH leader.
$customDQHandlers["ASH_203#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $uid    = intval($parts[0] ?? 0);
    $labels = (string)($parts[1] ?? '');
    $toks   = (string)($parts[2] ?? '');
    if ($labels === '' || $toks === '') return;
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", str_replace(',', '&', $labels), 1,
        tooltip: "Which_leader_do_you_want_to_exhaust?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_203#2|{$uid}|{$labels}|{$toks}", 1);
};

$customDQHandlers["ASH_203#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid    = intval($parts[0] ?? 0);
    $labels = explode(',', (string)($parts[1] ?? ''));
    $toks   = explode(',', (string)($parts[2] ?? ''));
    $pick   = array_search((string)$lastDecision, $labels, true);
    if ($pick === false || !isset($toks[$pick])) return;   // unknown label → no cost paid, no bonus
    if (!_SWUAsh203ExhaustLeader(intval($player), $toks[$pick])) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) SWUAddAttackPowerBonus($mz, 2);
};
