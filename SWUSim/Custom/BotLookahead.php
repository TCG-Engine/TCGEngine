<?php
// SWUBotLookahead — Phase 1a SPIKE (docs/superpowers/plans/2026-09-13-swusim-rl-bots-phase1a.md, Task 1).
// Applies ONE bot action to the LOADED game in memory, reads the board through $read, then restores the
// game byte-identically. Layer-2 rules 4 ("break lethal") and 5 ("Control wipes that stabilise") are
// built on the verdict below — see the RL bots spec, "The one-move lookahead". Nothing in Phase 1a
// calls this.
//
// WHY NOT EngineExecuteLoadedAction(): Core/EngineActionRunner.php:391 dispatches the action and then,
// unconditionally for every gameplay mode, runs the write block at :1063-1101 — ++$updateNumber,
// GameLogCommitFrame, WriteGamestate(), the Bo3 match hook, the animation cache. There is no option to
// skip it ($options carries only updateCache / disableRecording), and adding one would mean editing Core/,
// which the RL bots spec forbids. So this dispatches through the same SWUSim entry points the engine's
// mode switch reaches, exactly as SWUSim/Tests/Framework/GameTestAdapter.php drives the engine in-process:
//   mode 10002 "<mz>!FSM!"            -> ActionMap($mz)                      (GameTestAdapter::declareAttack/playCardFromHand)
//   mode 10001 "<mz>!CustomInput!<v>"  -> CustomWidgetInput($seat, $mz, $v)   (EngineActionRunner.php:520-527)
//   mode 100   "<answer>"              -> PopDecision + ExecuteStaticMethods  (GameTestAdapter::answerDecision)
// then drains the queue and runs SWUFlushDeferredReplacements() (GameTestAdapter::_drainDQ /
// _mirrorProductionPostAction), so the board read afterwards is the one production would reach.
//
// RESTORE: the same primitives SWULoadBookmark() uses (GameLogic.php ~:20682): the serialized-zones payload
// plus $gRandomCounter, and the per-seat undo-block flags that LoadVersion would otherwise roll back.
// ⚠ PLUS THE VERSIONS ZONES, which that payload DELIBERATELY EXCLUDES (SWUSim/Custom/UndoStack.php's
// header): seat 1's Versions zone IS the undo log and seat 2's holds the undo cursor and the bookmarks.
// Every user action runs SaveUndoVersion() -> PushUndoSnapshot(), which appends to the log and moves the
// cursor, so a lookahead restored from the payload alone left a phantom undo entry behind — found by
// bot_lookahead_test.php's undo-state check (red for mode 10002 and 10001 before this). A bookmark load
// does not need this because it WANTS its own undo entry ("load"); a lookahead must leave no trace. On
// top of that, every NON-serialized continuation global that GameTestAdapter::simulateRequestBoundary()
// lists is saved and put back, along with $playerID, $updateNumber, $frameAnimations and $gFlashMessage —
// a dispatch can set any of them, and none of them is inside the payload.
//
// VERDICT (2026-09-13): ADOPT. SWUSim/DevTools/tests/bot_lookahead_test.php —
//   • isolation: byte-identical serialized zones + RNG counter, identical Versions zones (undo log, cursor,
//     bookmarks), undo-block flags and in-memory globals, for all three wire forms (10002, 10001, 100);
//     each restore step mutation-proven load-bearing (payload restore removed → 4 red; globals restore
//     removed → 1 red; Versions restore absent → 2 red, which is how the undo-log leak was found);
//   • cost: median 0.48 ms per lookahead (min 0.43, max 0.69) over 20 runs — two orders of magnitude
//     under the plan's 50 ms bar.
// ⚠ DESIGN INPUT FOR PHASE 1b: ONE move stops at the first decision it raises. Playing a removal event
// pays for it and leaves its target prompt pending — the removal has NOT happened yet. Rules 4 and 5 must
// therefore look ahead a SEQUENCE (the play, then its answers) inside ONE save/restore; a sequence form is
// the obvious extension, since mode-100 answers already dispatch here.
// → Built as SWUBotLookaheadBest() below (Phase 1b, part 1).

const SWU_BOT_LOOKAHEAD_VERDICT = 'ADOPT';

// Continuation globals that live only in memory between an action and its decisions. Kept in lockstep with
// GameTestAdapter::simulateRequestBoundary()'s global list — a new one added there belongs here too.
function _SWUBotLookaheadTransientGlobalNames(): array {
    return [
        'gShootFirstPending', 'gDeferredReplacements', 'gSec035DefeatSnapshot', 'gAsh195DefeatSnapshot',
        'gCombatDefeatByMz', 'gPlayGrantedExploit', 'gScryState', 'gPendingEntryEffects',
        'gExploitDeferTriggers', 'gExploitDeferredBag', 'gLastPlayResourcesPaid', 'gWDPowerSnapshot',
        'gLastIndirectUnitUIDs', 'gLastIndirectBaseDmg', 'gSec035AttackPower', 'gLastExploitedPowers',
        'gCloneCopyCardID', 'gGrantedBountySnapshot', 'gPlayGrantTurnEffect', 'gPlayGrantExp',
        'gPlayGrantShield', 'gPlayGrantPrevent2', 'gEntryPlayGrantTE', 'gForceEnterReady', 'gInCombatDamage',
        // Not in the request-boundary list (they are not continuation state) but a dispatch mutates them:
        'playerID', 'updateNumber', 'frameAnimations', 'gFlashMessage',
    ];
}

// Dispatch one bot action in memory. Returns false for a wire form this does not understand.
function _SWUBotLookaheadDispatch(int $seat, array $action): bool {
    global $playerID;
    $playerID = $seat;
    $mode = intval($action['mode'] ?? 0);
    $cardID = strval($action['cardID'] ?? '');
    $dq = new DecisionQueueController();
    if ($mode === 10002) {
        $parts = explode('!', $cardID);
        if (($parts[1] ?? '') !== 'FSM') return false;
        ActionMap($parts[0]);
        $dq->ExecuteStaticMethods($seat, '-');
    } elseif ($mode === 10001) {
        $parts = explode('!', $cardID);
        if (($parts[1] ?? '') !== 'CustomInput') return false;
        CustomWidgetInput($seat, $parts[0], $parts[2] ?? '');
        $dq->ExecuteStaticMethods($seat, '-');
    } elseif ($mode === 100) {
        if (function_exists('GameOnDecisionAnswered')) GameOnDecisionAnswered($seat, $cardID);
        $dq->PopDecision($seat);
        $dq->ExecuteStaticMethods($seat, $cardID);
    } else {
        return false;
    }
    if (function_exists('SWUFlushDeferredReplacements')) SWUFlushDeferredReplacements();
    return true;
}

// Apply $action for $seat, return $read()'s array, restore the game. null if the action's wire form is not
// understood or $read does not return an array. Output (flash text, echoed warnings) is swallowed.
function SWUBotLookahead(int $seat, array $action, callable $read): ?array {
    global $gRandomCounter;
    $GLOBALS['SWUBotLookaheadCalls'] = intval($GLOBALS['SWUBotLookaheadCalls'] ?? 0) + 1;   // cost tests + profiling
    $payload = Versions::GetSerializedZones() . '<v0>' . $gRandomCounter;
    $blocked = _SWUCaptureUndoBlocks();
    $versions = [];
    for ($p = 1; $p <= 4; $p++) {
        $versions[$p] = array_map(fn($e) => $e === null ? null : clone $e, GetVersions($p));
    }
    $saved = [];
    foreach (_SWUBotLookaheadTransientGlobalNames() as $name) {
        $saved[$name] = array_key_exists($name, $GLOBALS) ? $GLOBALS[$name] : null;
    }
    $out = null;
    ob_start();
    try {
        if (_SWUBotLookaheadDispatch($seat, $action)) $out = $read();
    } finally {
        ob_end_clean();
        _SWURestoreSerializedPayload($payload);
        _SWUReapplyUndoBlocks($blocked);
        for ($p = 1; $p <= 4; $p++) {          // AFTER the payload restore, which uses seat 1's zone as scratch
            $z = &GetVersions($p);
            $z = $versions[$p];
            unset($z);
        }
        foreach ($saved as $name => $value) $GLOBALS[$name] = $value;
    }
    return is_array($out) ? $out : null;
}

// ── The SEQUENCE form (Phase 1b) ───────────────────────────────────────────────────────────────────────
// One move stops at the first decision it raises (see the DESIGN INPUT note above), so a removal event is
// paid for but has not yet removed anything. Rules 4 and 5 need the move's RESULT: SWUBotLookaheadBest()
// applies the move, then keeps answering the acting seat's OWN follow-up decisions — each answer in a nested
// SWUBotLookahead(), so every branch is restored before the next — and keeps the line $score rates highest.
// It stops at a decision another seat owes (it never answers for the opponent; the board is read as it
// stands, the conservative read), when nothing is pending, at the depth cap, or when its BUDGET is spent.
// Caps: SWU_BOT_LOOKAHEAD_DEPTH follow-up decisions per line; SWU_BOT_LOOKAHEAD_BRANCH answers tried per
// decision (the enumerator's first ones — a multi-select's combinations beyond the cap are not scored).
// ⚠ THE BUDGET IS THE BOUND THAT MATTERS. A lookahead that continues also enumerates the legal actions, ~7 ms
// together, and depth × branch alone allows 1 + 8 + 64 + 512 of them per candidate. Sweep run 3 (2026-09-13)
// timed out twice on exactly that: rule 4, facing lethal, abstained after 62 s at a Name-a-card prompt and 17 s
// at a 4-target attack prompt. A budget counts SWUBotLookahead() calls, the move included, and is shared out
// evenly: each answer gets an equal share of what is left, so one deep branch cannot starve its siblings.
// Deterministic — the same board always spends the same budget the same way.
const SWU_BOT_LOOKAHEAD_DEPTH  = 3;
const SWU_BOT_LOOKAHEAD_BRANCH = 8;
const SWU_BOT_LOOKAHEAD_BUDGET = 32;   // per rule decision, shared across its candidates (_SWUBotBestLine)

// $read(): array — the board read at a line's end. $score(array $read): float — higher is better. $budget —
// the most SWUBotLookahead() calls this line may make, the move included. Returns the best line's read plus
// '_score' and '_path' ([['tooltip' => …, 'answer' => cardID], …] after the move); null if the move could not
// be applied.
function SWUBotLookaheadBest(int $seat, array $action, callable $read, callable $score, int $depth = SWU_BOT_LOOKAHEAD_DEPTH, int $budget = SWU_BOT_LOOKAHEAD_BUDGET): ?array {
    if ($budget < 1) return null;
    return SWUBotLookahead($seat, $action, function () use ($seat, $read, $score, $depth, $budget) {
        $spent = 0;
        return _SWUBotLookaheadContinue($seat, $read, $score, $depth, $budget - 1, $spent);
    });
}

// Continue a line from the current (in-lookahead) board. $budget — lookaheads still allowed below here; $spent
// — incremented by the lookaheads this call makes.
function _SWUBotLookaheadContinue(int $seat, callable $read, callable $score, int $depth, int $budget, int &$spent): array {
    $leaf = function () use ($read, $score) {
        $r = $read();
        $r['_score'] = floatval($score($r));
        $r['_path'] = [];
        return $r;
    };
    if ($depth <= 0 || $budget <= 0 || !function_exists('SWUBotLegalActions') || SWUBotPendingDecisionSeat() !== $seat) return $leaf();
    $legal = SWUBotLegalActions(strval($GLOBALS['gameName'] ?? ''), $seat);
    $answers = array_slice((array)($legal['actions'] ?? []), 0, min(SWU_BOT_LOOKAHEAD_BRANCH, $budget));
    if (($legal['kind'] ?? '') !== 'decision' || empty($answers)) return $leaf();
    $tooltip = strval($legal['decisionTooltip'] ?? '');
    $best = null;
    $n = count($answers);
    foreach ($answers as $i => $a) {
        $share = intdiv($budget - $spent, $n - $i);   // ≥ 1: at most $budget answers, each spending ≤ its share
        $sub = 0;
        $r = SWUBotLookahead($seat, $a, function () use ($seat, $read, $score, $depth, $share, &$sub) {
            return _SWUBotLookaheadContinue($seat, $read, $score, $depth - 1, $share - 1, $sub);
        });
        $spent += 1 + $sub;
        if ($r === null) continue;
        if ($best === null || $r['_score'] > $best['_score']) {
            $r['_path'] = array_merge([['tooltip' => $tooltip, 'answer' => strval($a['cardID'] ?? '')]], $r['_path']);
            $best = $r;
        }
    }
    return $best ?? $leaf();
}
