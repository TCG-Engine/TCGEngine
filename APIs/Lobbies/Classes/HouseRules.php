<?php
// House rules — a room-forming vote on optional rule tweaks (Twin Suns first). Pure functions over a $lobby stdClass,
// same contract as HostVote.php / TeamRooms.php: no APCu, no HTTP, no globals, so the whole vote is unit-testable
// (SWUSim/DevTools/tests/lobby_house_rules_test.php). Spec: docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
//
// The rule MENU is the sim's (passed in as $defs — see SWUSim/HouseRuleDefs.php); this file only knows how a room
// agrees on it:
//   • Public room: every HUMAN seat votes per rule. A rule is on at a strict majority of the humans seated now
//     (intdiv(h,2)+1). Bots never vote and never count — same reasoning as the Kick Host vote.
//   • Private room: the host is the only voter, so the threshold is 1 and the host's checkbox IS the rule.
//   • Only the host edits a rule's number. Everyone else sees it.
//   • An exclusive group (Take Credit / Take Restore) keeps at most one rule active: whichever reached its threshold
//     first stays; the other cannot take NEW votes until the incumbent drops. When both qualify in the same resolve
//     (a leaver lowered the threshold), definition order breaks the tie.
//   • Touching the rules unreadies people so nobody starts against rules they have not seen: a vote unreadies the
//     voter; a host value change — and any host toggle in a private room — unreadies every other human; and ANY change
//     to the active set (a vote lighting a rule, or a join/leave moving the threshold) unreadies every human.
//   • A private room's rules survive a host change: the new host inherits the old host's choices.
//
// State: $lobby->houseRules = ['votes' => [ruleId => [playerID => true]], 'values' => [ruleId => int],
//                              'active' => [ruleId, ...]]   ('active' in activation order)

require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/HostVote.php';   // SWUHostVoteHumanPlayerIDs

function _LobbyHouseRuleDef(array $defs, string $ruleId): ?array {
    foreach ($defs as $d) if (($d['id'] ?? null) === $ruleId) return $d;
    return null;
}

function LobbyHouseRulesState(object $lobby, array $defs): array {
    $hr = is_array($lobby->houseRules ?? null) ? $lobby->houseRules : [];
    $hr['votes']  = is_array($hr['votes'] ?? null) ? $hr['votes'] : [];
    $hr['values'] = is_array($hr['values'] ?? null) ? $hr['values'] : [];
    $hr['active'] = is_array($hr['active'] ?? null) ? array_values($hr['active']) : [];
    foreach ($defs as $d) {
        $id = $d['id'];
        if (!isset($hr['votes'][$id]) || !is_array($hr['votes'][$id])) $hr['votes'][$id] = [];
        if (is_array($d['value'] ?? null) && !isset($hr['values'][$id])) $hr['values'][$id] = intval($d['value']['default']);
    }
    $lobby->houseRules = $hr;
    return $hr;
}

// Who may vote right now. Private: the host alone. Public: every human seat.
function LobbyHouseRulesVoterIDs(object $lobby): array {
    if (!empty($lobby->isPrivate)) return [intval($lobby->hostPlayerID ?? 1)];
    return SWUHostVoteHumanPlayerIDs($lobby);
}

function LobbyHouseRulesNeeded(object $lobby): int {
    return intdiv(count(LobbyHouseRulesVoterIDs($lobby)), 2) + 1;
}

function _LobbyHouseRulesGroupHolder(array $hr, array $defs, ?string $group, string $exceptId): ?string {
    if ($group === null) return null;
    foreach ($hr['active'] as $aid) {
        if ($aid === $exceptId) continue;
        $ad = _LobbyHouseRuleDef($defs, $aid);
        if ($ad !== null && ($ad['group'] ?? null) === $group) return $aid;
    }
    return null;
}

// Prune votes from anyone who is no longer a voter (left, kicked, or no longer host in a private room), drop active
// rules that no longer qualify, then activate newly qualifying ones in definition order unless their group is taken.
// Returns whether the stored state changed (LobbyMutate's skip-the-write convention).
function LobbyHouseRulesResolve(object $lobby, array $defs): bool {
    $before = serialize($lobby->houseRules ?? null);
    $hr = LobbyHouseRulesState($lobby, $defs);
    $activeBefore = $hr['active'];
    sort($activeBefore);
    $voters = array_flip(LobbyHouseRulesVoterIDs($lobby));
    // A private room's choice belongs to the ROOM: the host is its only voter, so a vote cast by an earlier host (who
    // went away, left, or was voted out) passes to the current one instead of being pruned — pruning wiped every rule
    // on a host change.
    if (!empty($lobby->isPrivate)) {
        $host = intval($lobby->hostPlayerID ?? 1);
        foreach ($hr['votes'] as $id => $byPlayer) {
            if (!empty($byPlayer)) $hr['votes'][$id] = [$host => true];
        }
    }
    foreach ($hr['votes'] as $id => $byPlayer) {
        foreach (array_keys($byPlayer) as $pid) {
            if (!isset($voters[intval($pid)])) unset($hr['votes'][$id][$pid]);
        }
    }
    $needed = intdiv(count($voters), 2) + 1;
    $qualifies = fn($id) => count($hr['votes'][$id] ?? []) >= $needed;
    $hr['active'] = array_values(array_filter($hr['active'],
        fn($id) => _LobbyHouseRuleDef($defs, $id) !== null && $qualifies($id)));
    foreach ($defs as $d) {
        $id = $d['id'];
        if (in_array($id, $hr['active'], true) || !$qualifies($id)) continue;
        if (_LobbyHouseRulesGroupHolder($hr, $defs, $d['group'] ?? null, $id) !== null) continue;
        $hr['active'][] = $id;
    }
    $lobby->houseRules = $hr;
    // The rule set everyone readied against has changed — whether by a vote, or with nobody clicking (a join/leave
    // moved the threshold). Every human must look again before the game can start.
    $activeAfter = $hr['active'];
    sort($activeAfter);
    if ($activeAfter !== $activeBefore) _LobbyHouseRulesUnreadyOthers($lobby, 0);
    return serialize($lobby->houseRules) !== $before;
}

function _LobbyHouseRulesUnreadyOthers(object $lobby, int $exceptPlayerID): void {
    foreach (($lobby->players ?? []) as $p) {
        if (!($p instanceof Player) || $p->getBotProfile() !== '') continue;
        if (intval($p->getPlayerID()) !== $exceptPlayerID) $p->setReady(false);
    }
}

function _LobbyHouseRulesPlayer(object $lobby, int $playerID): ?Player {
    foreach (($lobby->players ?? []) as $p) {
        if ($p instanceof Player && intval($p->getPlayerID()) === $playerID) return $p;
    }
    return null;
}

// Once a Start is under way the set is frozen (StartRoom has already read it): no more votes or value changes.
function _LobbyHouseRulesFrozen(object $lobby): bool {
    return !empty($lobby->gameName) || in_array(strval($lobby->state ?? ''), ['starting', 'started'], true);
}

// StartRoom renumbers seats 1..N; move each vote to its player's new id ([oldID => newID]). Done in the same write as
// the renumbering, so a Start that then fails and rolls the room back to 'open' still has every vote on the right seat.
function LobbyHouseRulesRemapPlayerIDs(object $lobby, array $idMap): void {
    if (!is_array($lobby->houseRules ?? null) || !is_array($lobby->houseRules['votes'] ?? null)) return;
    $hr = $lobby->houseRules;
    foreach ($hr['votes'] as $id => $byPlayer) {
        $moved = [];
        foreach (array_keys((array)$byPlayer) as $pid) {
            if (isset($idMap[intval($pid)])) $moved[intval($idMap[intval($pid)])] = true;
        }
        $hr['votes'][$id] = $moved;
    }
    $lobby->houseRules = $hr;
}

// Check or uncheck $playerID's vote on $ruleId. Returns an error message, or null on success.
function LobbyHouseRulesSetVote(object $lobby, array $defs, int $playerID, string $ruleId, bool $on): ?string {
    if (_LobbyHouseRulesFrozen($lobby)) return 'The game is already starting.';
    $def = _LobbyHouseRuleDef($defs, $ruleId);
    if ($def === null) return 'Unknown house rule.';
    if (!in_array($playerID, LobbyHouseRulesVoterIDs($lobby), true)) {
        return !empty($lobby->isPrivate) ? 'Only the host can choose house rules in a private room.'
                                         : 'Only players can vote on house rules.';
    }
    $hr = LobbyHouseRulesState($lobby, $defs);
    $mine = isset($hr['votes'][$ruleId][$playerID]);
    if ($on === $mine) return null;
    if ($on && _LobbyHouseRulesGroupHolder($hr, $defs, $def['group'] ?? null, $ruleId) !== null) {
        return 'Another rule that cannot be combined with this one is already active.';
    }
    if ($on) $hr['votes'][$ruleId][$playerID] = true;
    else     unset($hr['votes'][$ruleId][$playerID]);
    $lobby->houseRules = $hr;
    if (!empty($lobby->isPrivate)) {
        _LobbyHouseRulesUnreadyOthers($lobby, $playerID);   // the host's toggle IS the rule: guests must re-read it
    } else {
        $me = _LobbyHouseRulesPlayer($lobby, $playerID);
        if ($me !== null) $me->setReady(false);
    }
    LobbyHouseRulesResolve($lobby, $defs);
    return null;
}

// Host-only: set $ruleId's number, clamped to its range and snapped to its step. Returns an error, or null.
function LobbyHouseRulesSetValue(object $lobby, array $defs, int $playerID, string $ruleId, int $value): ?string {
    if (_LobbyHouseRulesFrozen($lobby)) return 'The game is already starting.';
    $def = _LobbyHouseRuleDef($defs, $ruleId);
    if ($def === null) return 'Unknown house rule.';
    if (!is_array($def['value'] ?? null)) return 'This house rule has no value to change.';
    if ($playerID !== intval($lobby->hostPlayerID ?? 1)) return 'Only the host can change house rule values.';
    $v = $def['value'];
    $step = max(1, intval($v['step']));
    $value = max(intval($v['min']), min(intval($v['max']), $value));
    $value = intval($v['min']) + intval(round(($value - intval($v['min'])) / $step)) * $step;
    $value = min(intval($v['max']), $value);
    $hr = LobbyHouseRulesState($lobby, $defs);
    if (intval($hr['values'][$ruleId]) === $value) return null;
    $hr['values'][$ruleId] = $value;
    $lobby->houseRules = $hr;
    _LobbyHouseRulesUnreadyOthers($lobby, $playerID);
    LobbyHouseRulesResolve($lobby, $defs);
    return null;
}

// The rules a game started now would use: [ruleId => value] for adjustable rules, [ruleId => true] for on/off ones,
// in definition order.
function LobbyHouseRulesActive(object $lobby, array $defs): array {
    $hr = LobbyHouseRulesState($lobby, $defs);
    $out = [];
    foreach ($defs as $d) {
        $id = $d['id'];
        if (!in_array($id, $hr['active'], true)) continue;
        $out[$id] = is_array($d['value'] ?? null) ? intval($hr['values'][$id]) : true;
    }
    return $out;
}

// What one viewer's waiting-room panel draws.
function LobbyHouseRulesView(object $lobby, array $defs, int $viewerPlayerID): array {
    $hr = LobbyHouseRulesState($lobby, $defs);
    $isVoter = in_array($viewerPlayerID, LobbyHouseRulesVoterIDs($lobby), true);
    $isHost  = $viewerPlayerID > 0 && $viewerPlayerID === intval($lobby->hostPlayerID ?? 1);
    $rows = [];
    foreach ($defs as $d) {
        $id = $d['id'];
        $mine = isset($hr['votes'][$id][$viewerPlayerID]);
        $locked = _LobbyHouseRulesGroupHolder($hr, $defs, $d['group'] ?? null, $id) !== null;
        $value = null;
        if (is_array($d['value'] ?? null)) {
            $value = ['cur' => intval($hr['values'][$id]), 'min' => intval($d['value']['min']),
                      'max' => intval($d['value']['max']), 'step' => intval($d['value']['step'])];
        }
        $rows[] = [
            'id' => $id, 'label' => strval($d['label']), 'desc' => strval($d['desc'] ?? ''), 'value' => $value,
            'votes' => count($hr['votes'][$id]), 'mine' => $mine,
            'active' => in_array($id, $hr['active'], true), 'locked' => $locked,
            // A locked rule may still be UNchecked by someone who voted for it — only new votes are refused.
            'canVote' => $isVoter && (!$locked || $mine),
            'canEditValue' => $isHost && $value !== null,
        ];
    }
    return ['private' => !empty($lobby->isPrivate), 'needed' => LobbyHouseRulesNeeded($lobby),
            'isHost' => $isHost, 'rows' => $rows];
}
