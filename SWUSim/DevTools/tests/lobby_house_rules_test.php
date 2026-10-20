<?php
// Twin Suns house rules — the waiting-room vote, as pure functions over a $lobby stdClass (no APCu, no HTTP).
// Same contract as lobby_host_vote_test.php. Spec: docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
//
// Public room: every HUMAN seat votes per rule; a rule is on at a strict majority of the humans
// (intdiv(h,2)+1 → 1/2/2/3 for 1/2/3/4 humans). Private room: the host alone decides. Take Credit and Take Restore
// share one exclusive group — whichever fills first wins, the other locks until it drops.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/HouseRules.php';
require_once $root . '/SWUSim/HouseRuleDefs.php';

function mkLobby($n, $private = false, $hostPlayerID = 1) {
    $l = new stdClass();
    $l->rootName = 'SWUSim';
    $l->format = 'twinsuns';
    $l->isPrivate = $private;
    $l->hostPlayerID = $hostPlayerID;
    $l->players = [];
    for ($i = 1; $i <= $n; $i++) {
        $p = new Player($i, 'deck' . $i, '', 100 + $i);
        $p->setReady(true);
        $l->players[] = $p;
    }
    $l->numPlayers = $n;
    return $l;
}
function addBot($lobby, $playerID) {
    $p = new Player($playerID, '', '', null);
    $p->setBotProfile('heuristic');
    $p->setReady(true);
    $lobby->players[] = $p;
    $lobby->numPlayers = count($lobby->players);
    return $p;
}
function seat($lobby, $playerID) {
    foreach ($lobby->players as $p) if ($p->getPlayerID() === $playerID) return $p;
    return null;
}
function row($lobby, $defs, $viewer, $id) {
    foreach (LobbyHouseRulesView($lobby, $defs, $viewer)['rows'] as $r) if ($r['id'] === $id) return $r;
    return null;
}

$defs = SWUHouseRuleDefs();

// ── Definitions ─────────────────────────────────────────────────────────────────────────────────
check(array_column($defs, 'id') === ['extraBaseHp', 'takeCredit', 'takeRestore', 'planScry'], 'four rules in display order');
$byId = array_column($defs, null, 'id');
check($byId['extraBaseHp']['value'] === ['default' => 10, 'min' => 0, 'max' => 30, 'step' => 5], 'Extra Base HP: default 10, 0-30 step 5');
check($byId['takeCredit']['value'] === null, 'Take Credit has no value');
check($byId['takeRestore']['value']['default'] === 1, 'Take Restore defaults to 1');
check($byId['planScry']['value']['default'] === 2, 'Plan Scry defaults to 2');
check($byId['takeCredit']['group'] === 'fourthAction' && $byId['takeRestore']['group'] === 'fourthAction', 'credit + restore share the fourthAction group');

// ── Threshold: strict majority of HUMANS ────────────────────────────────────────────────────────
check(LobbyHouseRulesNeeded(mkLobby(1)) === 1, '1 human needs 1');
check(LobbyHouseRulesNeeded(mkLobby(2)) === 2, '2 humans need 2 (unanimous)');
check(LobbyHouseRulesNeeded(mkLobby(3)) === 2, '3 humans need 2');
check(LobbyHouseRulesNeeded(mkLobby(4)) === 3, '4 humans need 3');
$l = mkLobby(2); addBot($l, 3); addBot($l, 4);
check(LobbyHouseRulesNeeded($l) === 2, '2 humans + 2 bots: bots do not count, still unanimous');
check(LobbyHouseRulesNeeded(mkLobby(4, true)) === 1, 'private room: the host alone decides');

// ── Voting activates at the threshold; a vote unreadies only the voter ──────────────────────────
$l = mkLobby(3);
check(LobbyHouseRulesSetVote($l, $defs, 1, 'extraBaseHp', true) === null, 'seat 1 votes Extra Base HP');
check(LobbyHouseRulesActive($l, $defs) === [], '1 of 2 needed: not active');
check(seat($l, 1)->getReady() === false, 'the voter is unreadied');
check(seat($l, 2)->getReady() === true && seat($l, 3)->getReady() === true, 'the other seats stay ready');
LobbyHouseRulesSetVote($l, $defs, 2, 'extraBaseHp', true);
check(LobbyHouseRulesActive($l, $defs) === ['extraBaseHp' => 10], '2 of 2 needed: active at its default value');
check(row($l, $defs, 3, 'extraBaseHp')['active'] === true && row($l, $defs, 3, 'extraBaseHp')['votes'] === 2, 'the view shows the tally and the green light');
check(row($l, $defs, 1, 'extraBaseHp')['mine'] === true && row($l, $defs, 3, 'extraBaseHp')['mine'] === false, 'the view marks the viewer\'s own vote');
LobbyHouseRulesSetVote($l, $defs, 2, 'extraBaseHp', false);
check(LobbyHouseRulesActive($l, $defs) === [], 'unchecking below the threshold turns it off');

$l = mkLobby(3); addBot($l, 4);
check(LobbyHouseRulesSetVote($l, $defs, 4, 'extraBaseHp', true) !== null, 'a bot cannot vote');
check(row($l, $defs, 1, 'extraBaseHp')['votes'] === 0, 'the refused bot vote was not recorded');
check(LobbyHouseRulesSetVote($l, $defs, 1, 'nope', true) !== null, 'an unknown rule id is refused');

// ── Values: host only, clamped and snapped; a change unreadies every other human ────────────────
$l = mkLobby(3);
check(LobbyHouseRulesSetValue($l, $defs, 2, 'extraBaseHp', 20) !== null, 'a non-host cannot change a value');
check(LobbyHouseRulesSetValue($l, $defs, 1, 'extraBaseHp', 17) === null, 'the host changes Extra Base HP');
check(row($l, $defs, 2, 'extraBaseHp')['value']['cur'] === 15, '17 snaps to the 5-step: 15');
LobbyHouseRulesSetValue($l, $defs, 1, 'extraBaseHp', 99);
check(row($l, $defs, 2, 'extraBaseHp')['value']['cur'] === 30, '99 clamps to the max: 30');
check(seat($l, 2)->getReady() === false && seat($l, 3)->getReady() === false, 'a host value change unreadies every other human');
check(LobbyHouseRulesSetValue($l, $defs, 1, 'takeCredit', 3) !== null, 'a rule without a value refuses a value');
check(row($l, $defs, 1, 'extraBaseHp')['canEditValue'] === true && row($l, $defs, 2, 'extraBaseHp')['canEditValue'] === false, 'only the host may edit values');
LobbyHouseRulesSetVote($l, $defs, 1, 'extraBaseHp', true);
LobbyHouseRulesSetVote($l, $defs, 2, 'extraBaseHp', true);
check(LobbyHouseRulesActive($l, $defs) === ['extraBaseHp' => 30], 'an active rule carries the host\'s value');

// ── Exclusive group: whichever fills first wins; the other locks ────────────────────────────────
$l = mkLobby(3);
LobbyHouseRulesSetVote($l, $defs, 1, 'takeCredit', true);
LobbyHouseRulesSetVote($l, $defs, 2, 'takeCredit', true);
check(LobbyHouseRulesActive($l, $defs) === ['takeCredit' => true], 'Take Credit fills first and is active');
check(LobbyHouseRulesSetVote($l, $defs, 3, 'takeRestore', true) !== null, 'a vote for the locked Take Restore is refused');
$r = row($l, $defs, 3, 'takeRestore');
check($r['locked'] === true && $r['canVote'] === false, 'Take Restore shows locked and not votable');
check(LobbyHouseRulesSetVote($l, $defs, 3, 'takeRestore', false) === null, 'withdrawing a vote from a locked rule is still allowed');
LobbyHouseRulesSetVote($l, $defs, 1, 'takeCredit', false);
check(LobbyHouseRulesActive($l, $defs) === [], 'Take Credit drops below the threshold');
check(row($l, $defs, 3, 'takeRestore')['locked'] === false, 'Take Restore unlocks');

// Both qualify at once after a leaver lowers the threshold: definition order (Take Credit) wins.
$l = mkLobby(4);
LobbyHouseRulesSetVote($l, $defs, 1, 'takeCredit', true);
LobbyHouseRulesSetVote($l, $defs, 2, 'takeCredit', true);
LobbyHouseRulesSetVote($l, $defs, 3, 'takeRestore', true);
LobbyHouseRulesSetVote($l, $defs, 1, 'takeRestore', true);
check(LobbyHouseRulesActive($l, $defs) === [], '4 humans, 2 votes each: neither is active');
array_splice($l->players, 3, 1);   // seat 4 leaves
$l->numPlayers = 3;
check(LobbyHouseRulesResolve($l, $defs) === true, 'resolve reports the change');
check(LobbyHouseRulesActive($l, $defs) === ['takeCredit' => true], 'both qualify at 2-of-3: exactly one wins, Take Credit by definition order');

// An incumbent keeps its place even when the other rule ALSO qualifies later.
$l = mkLobby(4);
LobbyHouseRulesSetVote($l, $defs, 2, 'takeRestore', true);
LobbyHouseRulesSetVote($l, $defs, 3, 'takeRestore', true);
LobbyHouseRulesSetVote($l, $defs, 4, 'takeRestore', true);
check(LobbyHouseRulesActive($l, $defs) === ['takeRestore' => 1], 'Take Restore fills first');
check(LobbyHouseRulesSetVote($l, $defs, 1, 'takeCredit', true) !== null, 'Take Credit is locked while Take Restore is active');
check(row($l, $defs, 1, 'takeCredit')['votes'] === 0, 'the locked Take Credit took no vote');

// ── Leavers: their votes are pruned ─────────────────────────────────────────────────────────────
$l = mkLobby(4);
LobbyHouseRulesSetVote($l, $defs, 4, 'planScry', true);
LobbyHouseRulesSetVote($l, $defs, 3, 'planScry', true);
check(row($l, $defs, 1, 'planScry')['votes'] === 2, 'precondition: 2 votes');
array_splice($l->players, 3, 1);   // seat 4 leaves
LobbyHouseRulesResolve($l, $defs);
check(row($l, $defs, 1, 'planScry')['votes'] === 1, 'the leaver\'s vote is gone');
check(LobbyHouseRulesResolve($l, $defs) === false, 'a second resolve with nothing new reports no change');

// ── Private room: the host decides, guests are read-only, any host change unreadies the guests ──
$l = mkLobby(3, true);
check(LobbyHouseRulesSetVote($l, $defs, 2, 'planScry', true) !== null, 'a guest cannot toggle a rule');
check(LobbyHouseRulesSetVote($l, $defs, 1, 'planScry', true) === null, 'the host toggles Plan Scry');
check(LobbyHouseRulesActive($l, $defs) === ['planScry' => 2], 'active immediately, no vote');
check(seat($l, 2)->getReady() === false && seat($l, 3)->getReady() === false, 'the host\'s toggle unreadies every guest');
$v = LobbyHouseRulesView($l, $defs, 2);
check($v['private'] === true && $v['isHost'] === false, 'the guest view says private, not host');
check(row($l, $defs, 2, 'planScry')['canVote'] === false && row($l, $defs, 2, 'planScry')['active'] === true, 'the guest sees it read-only, lit');

// ── A rule that lights or goes dark with nobody clicking unreadies every human ─────────────────
// (final review I-1) A join/leave moves the threshold; readied players must not Start into rules they never saw.
$l = mkLobby(4);
LobbyHouseRulesSetVote($l, $defs, 1, 'extraBaseHp', true);
LobbyHouseRulesSetVote($l, $defs, 2, 'extraBaseHp', true);
foreach ($l->players as $p) $p->setReady(true);
check(LobbyHouseRulesActive($l, $defs) === [], 'precondition: 2 of 3 needed, dark, everyone ready');
array_splice($l->players, 3, 1);   // seat 4 leaves → 2 of 2 needed
LobbyHouseRulesResolve($l, $defs);
check(LobbyHouseRulesActive($l, $defs) === ['extraBaseHp' => 10], 'the leaver lowered the threshold: the rule lit');
check(seat($l, 1)->getReady() === false && seat($l, 2)->getReady() === false && seat($l, 3)->getReady() === false,
      'the rule set changed under them: every human is unreadied');
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';
check((bool)array_filter(SWURoomStartBlockers($l), fn($b) => str_contains($b, 'not ready')), 'so Start is blocked');
$l = mkLobby(3);
LobbyHouseRulesSetVote($l, $defs, 1, 'planScry', true);
foreach ($l->players as $p) $p->setReady(true);
LobbyHouseRulesSetVote($l, $defs, 2, 'planScry', true);
check(seat($l, 3)->getReady() === false, 'a vote that LIGHTS a rule unreadies the others too (the set they readied for changed)');
$l = mkLobby(3);
foreach ($l->players as $p) $p->setReady(true);
LobbyHouseRulesResolve($l, $defs);
check(seat($l, 1)->getReady() && seat($l, 2)->getReady() && seat($l, 3)->getReady(), 'a resolve that changes nothing unreadies nobody');

// ── Private room: the rules survive a host change ───────────────────────────────────────────────
// (final review I-2) The host is the only voter, so pruning "non-voters" used to wipe every rule the moment the host
// migrated (away, left, kicked). The room's choice belongs to the room: the new host inherits it.
$l = mkLobby(3, true);
LobbyHouseRulesSetVote($l, $defs, 1, 'planScry', true);
foreach ($l->players as $p) $p->setReady(true);
array_splice($l->players, 0, 1);  // the host leaves
$l->hostPlayerID = 2;              // SWUMigrateHostIfNeeded's job
LobbyHouseRulesResolve($l, $defs);
check(LobbyHouseRulesActive($l, $defs) === ['planScry' => 2], 'the private room keeps Plan Scry after the host changes');
check(row($l, $defs, 2, 'planScry')['mine'] === true && row($l, $defs, 2, 'planScry')['canVote'] === true, 'the new host now owns the toggle');
check(seat($l, 2)->getReady() && seat($l, 3)->getReady(), 'nothing changed, nobody is unreadied');

// ── Minor fixes (2026-10-19 follow-up) ─────────────────────────────────────────────────────────
// M1: StartRoom renumbers seats 1..N. The votes must move WITH their players, so a Start that then fails (the room
// rolls back to 'open' keeping the new numbers) still attributes every vote to the right person.
$l = mkLobby(4);
array_splice($l->players, 1, 1);   // seat 2 left: ids are 1, 3, 4
LobbyHouseRulesSetVote($l, $defs, 3, 'planScry', true);
LobbyHouseRulesSetVote($l, $defs, 4, 'planScry', true);
LobbyHouseRulesRemapPlayerIDs($l, [1 => 1, 3 => 2, 4 => 3]);
foreach ($l->players as $i => $p) $p->setPlayerID($i + 1);   // what StartRoom's renumber loop does
check(row($l, $defs, 2, 'planScry')['mine'] === true && row($l, $defs, 3, 'planScry')['mine'] === true, 'votes follow their players to the new seat ids');
check(row($l, $defs, 1, 'planScry')['mine'] === false, 'seat 1 did not inherit a vote');
check(LobbyHouseRulesActive($l, $defs) === ['planScry' => 2], 'the rule is still lit after the remap');
check(LobbyHouseRulesResolve($l, $defs) === false, 'and a resolve after it prunes nothing');

// M2: once a Start is under way the set is frozen — a vote or a value change then must be refused, not "succeed".
foreach (['starting' => fn($l) => $l->state = 'starting', 'started' => fn($l) => $l->gameName = '123'] as $why => $set) {
    $l = mkLobby(3);
    $set($l);
    check(LobbyHouseRulesSetVote($l, $defs, 1, 'planScry', true) !== null, "a vote is refused once the room is {$why}");
    check(LobbyHouseRulesSetValue($l, $defs, 1, 'planScry', 3) !== null, "a value change is refused once the room is {$why}");
    check(row($l, $defs, 1, 'planScry')['votes'] === 0 && row($l, $defs, 1, 'planScry')['value']['cur'] === 2, "nothing changed ({$why})");
}

echo "PASS\n";
