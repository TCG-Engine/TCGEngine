<?php
// 4P Always Two — the partner vote ("the bag"). docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "## 4P Always Two" →
// "The partner vote"; plan docs/superpowers/plans/2026-11-21-swusim-always-two-4p.md Task 2.
//
// In a 4P Always Two game (SWUAlwaysTwoVoteOn) every MAIN action one teammate takes as the team's turn action is PROPOSED, not run:
// it is stored in a game variable and the partner is asked "<name> wants to play <card>. Accept?" (an ordinary YESNO decision, so it
// shows on every board, desktop and phone, through the existing prompt UI). Accept replays the stored input AS THE PROPOSER through
// the same entry it came in by (ActionMap or CustomWidgetInput); Decline / Pass drops it, so nothing ever needs undoing — the gate
// sits in front of the action, before the engine changes any state.
//
// Main actions (spec list) — the gate is called from exactly these sites, right after their own turn checks:
//   ActionMap:          hand play, attack declaration
//   CustomWidgetInput:  Pass, TakeInitiative, a unit's / a hand card's Action, LeaderAbility, DeployLeader, base EpicAction, Smuggle,
//                       PlayFromDiscard, PlayFromOpponentDiscard
// Nothing INSIDE an action is voted: targets, play mode, payment, "may" choices, and plays that happen inside another action (those
// call the play functions directly, never these entries).
//
// One pending proposal per team: while it is pending, anything else either teammate does is refused with "A proposal is pending."
// The proposal lives in the gamestate (SWU vars), so an undo that rewinds past it removes it together with the partner's prompt.

// The game variable holding a team's pending proposal: team 1 = Red (boards 1, 3), team 0 = Blue (boards 2, 4) — SWUTeamOf's parity.
function _SWUVoteKey(int $team): string { return 'SWU_PRIVATE_A2_PROPOSAL_T' . $team; }   // SWU_PRIVATE_: never sent to any screen

function SWUVotePending(int $team): ?array {
    $raw = GetSWUVar(_SWUVoteKey($team), '');
    if ($raw === '') return null;
    $p = json_decode($raw, true);
    return is_array($p) ? $p : null;
}

// The teammate who answers $board's proposals: the other board of its team.
function _SWUVotePartner(int $board): int {
    foreach (SWUTeamBoards($board) as $b) if (intval($b) !== $board) return intval($b);
    return 0;
}

// Team-only log line: a proposal can name a hand card, which the opponents must never learn from the log.
function _SWUVoteLog(int $board, string $text): void {
    SWULogSeats(array_map('intval', SWUTeamBoards($board)), 'VOTE', $text);
}

function _SWUVoteName(int $board): string {
    global $gameName;
    return function_exists('SWUClockSeatName') ? SWUClockSeatName(strval($gameName ?? ''), $board) : ('P' . $board);
}

function _SWUVoteCardName(?object $obj): string {
    return ($obj !== null && !empty($obj->CardID)) ? SWUPromptCardName(strval($obj->CardID)) : 'a card';
}

// What the partner reads (spec copy). $actionCard / $action are the raw inputs, resolved from $board's own frame (my… = $board).
function SWUVotePromptText(int $board, string $entry, string $actionCard, string $action = ''): string {
    $verb = _SWUVoteVerb($board, $entry, $actionCard, $action);
    return _SWUVoteName($board) . ' wants to ' . $verb . '. Accept?';
}

function _SWUVoteVerb(int $board, string $entry, string $actionCard, string $action): string {
    $parts = explode('-', $actionCard);
    $zone  = $parts[0];
    $obj   = fn(string $mz) => GetZoneObject($mz);
    if ($entry === 'map') {
        if ($zone === 'myHand') {
            $card = $obj($actionCard);
            $name = _SWUVoteCardName($card);
            // An upgrade with exactly one legal host names it, like a single-target attack (spec [call]).
            if ($card !== null && CardType(strval($card->CardID)) === 'Upgrade' && function_exists('SWUGetUpgradeValidTargets')) {
                $hosts = SWUGetUpgradeValidTargets($board, strval($card->CardID));
                if (count($hosts) === 1) return 'play ' . $name . ' on ' . _SWUVoteCardName($obj(strval($hosts[0])));
            }
            return 'play ' . $name;
        }
        // Attack declaration: one legal target → named; several → the proposer picks after Accept (no second vote).
        $attacker = $obj($actionCard);
        $verb = 'attack with ' . _SWUVoteCardName($attacker);
        if ($attacker !== null) {
            $targets = SWUGetAllValidAttackTargets($board, $attacker, strval($attacker->Location));
            if (count($targets) === 1) $verb .= ' into ' . _SWUVoteCardName($obj(strval($targets[0])));
        }
        return $verb;
    }
    switch ($zone) {
        case 'myHealth':          return 'pass';
        case 'InitiativeCounter': return 'take the initiative';
        case 'myLeader':
            return str_starts_with($action, 'DeployLeader')
                ? 'deploy ' . _SWUVoteCardName($obj($actionCard))
                : 'use ' . _SWUVoteCardName($obj($actionCard)) . "'s ability";
        case 'myResources':       return 'play ' . _SWUVoteCardName($obj($actionCard)) . ' with Smuggle';
        case 'PlayFromDiscard':   return 'play ' . _SWUVoteCardName($obj('myDiscard-' . intval($parts[1] ?? 0))) . ' from the discard pile';
        case 'PlayFromOpponentDiscard':
            $mz = (count($parts) > 2 && $parts[2] !== '') ? ('p' . intval($parts[1]) . 'Discard-' . intval($parts[2]))
                                                          : ('theirDiscard-' . intval($parts[1] ?? 0));
            return 'play ' . _SWUVoteCardName($obj($mz)) . " from an opponent's discard pile";
        default:                  // a unit's, a hand card's, the base's Action
            return 'use ' . _SWUVoteCardName($obj($actionCard)) . "'s ability";
    }
}

// Would the board offer this action right now? The SAME sources the board lights its actions from: the hand glow (CanActivateCardForSelection)
// and SWUComputeActionsData's lists. A hand card's own Action and an any-player Action on an opponent's unit are not in those lists, so they
// stay permitted here and their own handlers refuse them as before.
function SWUVoteActionOffered(int $board, string $entry, string $actionCard, string $action = ''): bool {
    $parts = explode('-', $actionCard);
    $zone  = $parts[0];
    if ($entry === 'map' && $zone === 'myHand') {
        $obj = GetZoneObject($actionCard);
        return $obj !== null && CanActivateCardForSelection($board, $obj) && CanAffordActivationReserve($board, $obj);   // = the hand glow
    }
    $d = SWUComputeActionsData($board);
    if ($entry === 'map') return in_array($actionCard, $d['attackers'] ?? [], true);
    switch ($zone) {
        case 'myHealth':          return true;   // the Pass branch already refuses a pass it may not take
        case 'InitiativeCounter': return !str_ends_with(strval(GetInitiativeCounter()), '_CLAIMED');
        case 'myGroundArena':
        case 'mySpaceArena':      return in_array($actionCard, $d['unitActions'] ?? [], true);
        case 'myLeader':
            $i = intval($parts[1] ?? 0);
            return str_starts_with($action, 'DeployLeader') ? !empty(($d['leaderDeployByIndex'] ?? [])[$i])
                                                            : !empty(($d['leaderAbilityByIndex'] ?? [])[$i]);
        case 'myBase':            return !empty($d['baseEpic']);
        case 'myResources':       return in_array(intval($parts[1] ?? -1), array_map('intval', $d['smugglableResources'] ?? []), true);
        case 'PlayFromDiscard':
            foreach ($d['playableDiscards'] ?? [] as $e) if (intval($e['idx'] ?? -1) === intval($parts[1] ?? -2)) return true;
            return false;
        case 'PlayFromOpponentDiscard':
            [$owner, $idx] = count($parts) > 2 ? [intval($parts[1]), intval($parts[2])] : [0, intval($parts[1] ?? -1)];
            foreach ($d['opponentPlayableDiscards'] ?? [] as $e)
                if (intval($e['idx'] ?? -1) === $idx && ($owner === 0 || intval($e['owner'] ?? 0) === $owner)) return true;
            return false;
        default:                  return true;   // a hand card's Action, an opponent unit's any-player Action
    }
}

// Refuse any input from a teammate while the team's proposal is pending. Called at the TOP of ActionMap and CustomWidgetInput, before
// their own "decisions pending" early returns, so the refusal is SAID rather than silent. The partner's answer arrives as a decision
// answer (mode 100), never through these entries, so it is not blocked.
function SWUVoteRefuseIfPending(int $board): bool {
    if (!function_exists('SWUAlwaysTwoVoteOn') || !SWUAlwaysTwoVoteOn()) return false;
    if (!empty($GLOBALS['SWU_A2_REPLAY'])) return false;
    if (SWUVotePending(SWUTeamOf($board)) === null) return false;
    SetFlashMessage('A proposal is pending.');
    return true;
}

// The gate. Call it at a main-action site right AFTER the site's own turn checks pass. True = stop (stored as a proposal, or refused
// because one is pending); false = run the action as today (vote off, or this is the accepted replay).
function SWUVoteGate(int $board, string $entry, string $actionCard, string $action = ''): bool {
    if (!function_exists('SWUAlwaysTwoVoteOn') || !SWUAlwaysTwoVoteOn()) return false;
    if (!empty($GLOBALS['SWU_A2_REPLAY'])) { $GLOBALS['SWU_A2_REPLAY'] = false; return false; }   // one pass for the accepted action
    $team = SWUTeamOf($board);
    if (SWUVotePending($team) !== null) { SetFlashMessage('A proposal is pending.'); return true; }
    // Only an action the board would offer can be proposed (owner, 2026-11-21: "impossible actions ideally would be guarded against
    // from the UI") — the partner is never asked to approve something that cannot happen.
    if (!SWUVoteActionOffered($board, $entry, $actionCard, $action)) { SetFlashMessage('That action is not available right now.'); return true; }
    $partner = _SWUVotePartner($board);
    if ($partner <= 0) return false;
    $text = SWUVotePromptText($board, $entry, $actionCard, $action);
    SetSWUVar(_SWUVoteKey($team), json_encode(['board' => $board, 'entry' => $entry, 'actionCard' => $actionCard, 'action' => $action]));
    _SWUVoteLog($board, substr($text, 0, -strlen(' Accept?')));
    DecisionQueueController::AddDecision($partner, 'YESNO', '-', 1, tooltip: $text);
    // dontSkipOnPass: a Pass on the prompt is a Decline, and must still reach the handler to clear the proposal.
    DecisionQueueController::AddDecision($partner, 'CUSTOM', 'AlwaysTwoVote|' . $team, 1, dontSkipOnPass: 1);
    return true;
}

// Withdraw $team's pending proposal: clear it and take the partner's prompt (its YESNO and the AlwaysTwoVote continuation right
// after it) off the partner's queue. Used by Undo (the proposal took no undo point, so a plain Undo would rewind the PREVIOUS action —
// possibly the other team's — final review 2026-11-21) and by the end of the game (no prompt may outlive it). False if none pending.
function SWUVoteWithdraw(int $team): bool {
    $p = SWUVotePending($team);
    if ($p === null) return false;
    SetSWUVar(_SWUVoteKey($team), '');
    $partner = _SWUVotePartner(intval($p['board'] ?? 0));
    // SPLICED out, not flagged ->removed: DecisionQueueController::AllQueuesEmpty() counts every entry, so a flagged one would keep
    // blocking the team's next action.
    $q = &GetDecisionQueue($partner);
    $lastYesNo = -1;
    foreach (array_keys($q) as $i) {
        $d = $q[$i];
        if (!empty($d->removed)) continue;
        if (strval($d->Type ?? '') === 'YESNO') { $lastYesNo = $i; continue; }
        if (strval($d->Type ?? '') === 'CUSTOM' && str_starts_with(strval($d->Param ?? ''), 'AlwaysTwoVote|')) {
            unset($q[$i]);
            if ($lastYesNo >= 0) unset($q[$lastYesNo]);
            break;
        }
        $lastYesNo = -1;
    }
    $q = array_values($q);
    return true;
}

// Undo by either teammate while the team's proposal waits = withdraw it (nothing else is rewound). Called first thing in SWUDoUndo.
function SWUVoteWithdrawOnUndo(int $board): bool {
    if (!function_exists('SWUAlwaysTwoVoteOn') || !SWUAlwaysTwoVoteOn()) return false;
    $team = SWUTeamOf($board);
    $p = SWUVotePending($team);
    if ($p === null || !SWUVoteWithdraw($team)) return false;
    _SWUVoteLog(intval($p['board'] ?? $board), _SWUVoteName($board) . ' withdrew the proposal.');
    return true;
}

// The partner's answer. YES → replay the stored input as the proposer, through the entry it came in by. Anything else → drop it.
$customDQHandlers['AlwaysTwoVote'] = function ($player, $parts, $lastDecision) {
    $team = intval($parts[0] ?? -1);
    $p = SWUVotePending($team);
    SetSWUVar(_SWUVoteKey($team), '');
    if ($p === null) return;
    if (function_exists('SWUGetGameWinner') && SWUGetGameWinner() !== 0) return;   // the game ended while it waited: never play into it
    $proposer = intval($p['board'] ?? 0);
    if ($lastDecision !== 'YES') { _SWUVoteLog($proposer, _SWUVoteName(intval($player)) . ' declined.'); return; }
    _SWUVoteLog($proposer, _SWUVoteName(intval($player)) . ' accepted.');
    global $playerID;
    $saved = $playerID;
    $playerID = $proposer;
    $GLOBALS['SWU_A2_REPLAY'] = true;
    if (($p['entry'] ?? '') === 'map') ActionMap(strval($p['actionCard'] ?? ''));
    else CustomWidgetInput($proposer, strval($p['actionCard'] ?? ''), strval($p['action'] ?? ''));
    $GLOBALS['SWU_A2_REPLAY'] = false;   // a refused replay (no longer legal) never reached the gate — never leak the pass-through
    $playerID = $saved;
};
