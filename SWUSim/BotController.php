<?php
// SWUSim's implementation of the shared Core/BotController.php contract, mirroring
// GrandArchiveSim/BotController.php. Once the four functions below exist, the existing
// client-side bot-step polling (mode 10017 in Core/EngineActionRunner.php, the JS loop in
// Core/jsInclude.js) drives SWUSim with NO client changes.

require_once __DIR__ . '/BotLegalActions.php';
require_once __DIR__ . '/BotHeuristic.php';

// A gamestate hash for no-op detection, EXCLUDING bookkeeping that mutates on EVERY write
// regardless of whether the attempted action had any real effect. A raw text hash is useless
// here: each of the blocks below independently defeats it and strands the bot in an infinite
// loop re-choosing the same rejected action.
//
//   updateNumber          — incremented unconditionally by Core/EngineActionRunner.php whenever
//                           $result['writeGamestate'] is true, which is the default.
//   FlashMessage          — a rejected action still calls SetFlashMessage() with a reason.
//   Versions              — SWUSim's UNDO STACK (Schemas/SWUSim/GameSchema.txt:15). It snapshots
//                           every zone on every write and is by far the largest block; a real
//                           mid-game gamestate is ~742KB of which ~722KB is this.
//   MatchReplayCommands   — every ATTEMPTED action is appended for replay fidelity, on purpose.
//   MatchReplayInitialState — added after the Task 8 self-play harness caught it: on the FIRST
//                           action of a game, Core/MatchReplay.php's MatchReplayBeginPotentialAction()
//                           lazily snapshots the whole starting gamestate into this field
//                           (`if (MatchReplayIsEmptyStoredValue($previousInitial))`), and that runs
//                           before the action is validated — so a rejected first action fills a
//                           previously-empty block and moves the hash. GrandArchiveSim's equivalent
//                           avoids this by dropping every line beginning with 'MR1:', which covers
//                           both replay fields; excluding the block by name is the same fix and
//                           keeps this list the single description of what is masked.
//   GameLog               — a rejected action can still append an entry.
// Keys INSIDE the DecisionQueueVariables block that move on an action that changed nothing. Found by
// the Task 8 self-play harness, not guessed:
//
//   SWU_ACTION_ID — the action-close ledger's open counter. _SWUOpenAction()
//                   (Custom/GameLogic.php:19569) increments it from SaveUndoVersion(), and
//                   Custom/CustomInput.php calls SaveUndoVersion() BEFORE the verb it is about to
//                   attempt ("myLeader" :171, "myBase" :186, "myResources" :197) — so a refused
//                   leader deploy, base Epic Action or Smuggle still bumps it. That single counter
//                   was enough to make every rejected action look like a real state change:
//                   ProcessBotControllerStep()'s $noOp stayed false, its exclude-and-retry loop
//                   never excluded the rejected candidate, and 'first-legal' re-chose the same
//                   illegal DeployLeader 2,996 times in a row (harness run 3 of Task 8).
function SWUBotVolatileDecisionVariables() {
    return ['SWU_ACTION_ID'];
}

function SWUBotExcludedHashBlocks() {
    return ['FlashMessage', 'Versions', 'MatchReplayCommands', 'MatchReplayInitialState', 'GameLog'];
}

function SWUBotComparableGamestateHash($gameName) {
    if (!function_exists('RegressionCurrentGamestateText') || !function_exists('RegressionNormalizeNewlines')
        || !function_exists('RegressionGamestateSchemaLayout') || !function_exists('RegressionConsumeGamestateLayout')) {
        return null;
    }
    $text = RegressionCurrentGamestateText('SWUSim', $gameName);
    if (!is_string($text)) return null;

    $lines = explode("\n", RegressionNormalizeNewlines($text));
    // Every generated gamestate begins with currentPlayer then updateNumber.
    if (count($lines) > 1) unset($lines[1]);

    $layout = RegressionGamestateSchemaLayout('SWUSim');
    $blocks = [];
    // ⚠ SWUSim serializes FOUR copies of every per-player zone (p1..p4 — Twin Suns), even in a
    // two-player game. RegressionConsumeGamestateLayout()'s default is two, which is right for
    // GrandArchiveSim/AzukiSim and silently wrong here: with two copies the cursor stops partway
    // through p3Deck and EVERY block name from that point on is attached to the wrong lines. The
    // exclusion loop below then deleted lines that had nothing to do with FlashMessage / Versions /
    // MatchReplayCommands / GameLog, leaving the real MatchReplayCommands line — which grows by one
    // entry on EVERY attempted action, rejected ones included — inside the hash. That made the hash
    // move on every write, so ProcessBotControllerStep()'s $noOp was permanently false, its
    // exclude-and-retry loop never excluded anything, and a repeatedly-rejected candidate was
    // re-chosen forever (observed: 2,994 consecutive DeployLeader attempts in one self-play run).
    // RegressionGamestateSeatCount() mirrors zzGameCodeGenerator.php:43, which is the authority.
    $seatCopies = function_exists('RegressionGamestateSeatCount') ? RegressionGamestateSeatCount('SWUSim') : 4;
    $consumed = RegressionConsumeGamestateLayout($layout, $lines, $blocks, $seatCopies);

    // Self-check, because the failure above is silent by construction. CurrentPhase is a global,
    // single-value block that sits AFTER every per-player zone, so its content is only correct when
    // the per-player copy count was correct — comparing it against the live parsed value is a direct
    // test that the block map lines up with this gamestate. Returning null rather than a
    // wrong-but-plausible hash keeps a future misalignment loud (the caller treats null as "no
    // no-op information", never as "nothing changed").
    if ($consumed > count($lines) + 1) return null;
    if (function_exists('GetCurrentPhase')) {
        $phaseLine = null;
        foreach ($blocks as $block) {
            if ($block['name'] !== 'CurrentPhase' || $block['length'] !== 1) continue;
            $phaseLine = $lines[$block['start']] ?? null;
            break;
        }
        if ($phaseLine !== null && trim(strval($phaseLine)) !== trim(strval(GetCurrentPhase()))) {
            error_log('SWUBot: comparable-hash block layout is misaligned for SWUSim (CurrentPhase block '
                . "read '" . strval($phaseLine) . "' but the game is in '" . strval(GetCurrentPhase())
                . "') — no-op detection is disabled for this poll.");
            return null;
        }
    }

    $excluded = SWUBotExcludedHashBlocks();
    foreach ($blocks as $block) {
        if (!in_array($block['name'], $excluded, true)) continue;
        for ($i = $block['start']; $i < $block['start'] + $block['length']; $i++) unset($lines[$i]);
    }

    // DecisionQueueVariables cannot be excluded wholesale — it carries real game state
    // (GAMEOVER_WINNER, PASS, the combat/attacker vars). Only the per-key bookkeeping inside it is
    // masked. See SWUBotVolatileDecisionVariables() for why each key is here.
    foreach ($blocks as $block) {
        if ($block['name'] !== 'DecisionQueueVariables' || $block['length'] !== 1) continue;
        if (!array_key_exists($block['start'], $lines)) continue;
        $decoded = json_decode(strval($lines[$block['start']]), true);
        if (!is_array($decoded)) continue;
        foreach (SWUBotVolatileDecisionVariables() as $key) unset($decoded[$key]);
        ksort($decoded);
        $lines[$block['start']] = empty($decoded)
            ? '-'
            : json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return hash('sha256', implode("\n", $lines));
}

function GameBotControllerMode() {
    return SWUGameMode() === 'botpractice' ? 'bot' : '';
}

function GetBotControllerPlayers() {
    if (SWUGameMode() !== 'botpractice') return [];
    return GetSWUBotPlayers();
}

// Which seat needs a bot move, or 0. A pending DecisionQueue response always takes priority;
// otherwise, if the free-play turn player is bot-controlled, that seat is pending.
function BotControllerPendingPlayerForClient() {
    if (SWUGameMode() !== 'botpractice') return 0;
    // The game is over: the bot owes nothing, so the client stops polling and a stray poll does nothing. Without
    // this the winning action's close passed the turn to the bot seat and the bot played on after the win (owner
    // report 2026-09-14, game 183227). SWUSim/DevTools/tests/bot_practice_game_over_test.php.
    if (function_exists('SWUGetGameWinner') && SWUGetGameWinner() !== 0) return 0;
    $botPlayers = GetSWUBotPlayers();
    if (empty($botPlayers)) return 0;

    $decisionSeat = SWUBotPendingDecisionSeat();
    if ($decisionSeat !== 0) {
        return in_array($decisionSeat, $botPlayers, true) ? $decisionSeat : 0;
    }

    // Mirrors GrandArchiveSim/BotController.php's own guard: off-MAIN with no pending decision,
    // SWUBotFreePlayActions() itself returns playerID => 0 (BotLegalActions.php's turn/phase
    // gate), which would otherwise make ProcessBotControllerStep()'s $legal['playerID'] mismatch
    // check fire on every poll while the game sits in a non-MAIN phase.
    if (function_exists('GetCurrentPhase') && GetCurrentPhase() !== 'MAIN') return 0;
    $turnPlayer = function_exists('GetTurnPlayer') ? intval(GetTurnPlayer()) : 0;
    return in_array($turnPlayer, $botPlayers, true) ? $turnPlayer : 0;
}

function ProcessBotControllerStep($requestingPlayer = 0, $folderPath = '', $gameNameOverride = '') {
    if ($folderPath !== '' && $folderPath !== 'SWUSim') {
        return ['success' => false, 'message' => 'Bot controller does not handle this game.', 'applied' => false];
    }
    if (SWUGameMode() !== 'botpractice') {
        return ['success' => true, 'message' => '', 'applied' => false, 'retryable' => false];
    }

    global $gameName;
    $activeGameName = $gameNameOverride !== '' ? $gameNameOverride : strval($gameName ?? '');
    if ($activeGameName === '') {
        return ['success' => false, 'message' => 'Bot controller game is not loaded.', 'applied' => false, 'retryable' => false];
    }

    $pendingPlayer = BotControllerPendingPlayerForClient();
    if ($pendingPlayer === 0) {
        return ['success' => true, 'message' => 'No bot action is currently pending.', 'applied' => false, 'retryable' => false];
    }

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $pendingPlayer;

    $legal = SWUBotLegalActions($activeGameName, $pendingPlayer);
    if (intval($legal['playerID'] ?? 0) !== $pendingPlayer) {
        $playerID = $savedPlayerID;
        // Explicit retryable => false rather than letting the key default (true, per mode
        // 10017's own reading of this shape): a bare mismatch here means BotControllerPending-
        // PlayerForClient() and SWUBotLegalActions() disagree about who's up right now (e.g. a
        // phase boundary crossed between the two calls), which will not resolve itself on a tight
        // 5s poll — NextTurn.php's own per-render MaybeRunBotControllerStep() call is what should
        // give this another look, not the client's backoff timer re-hammering the same mismatch.
        return [
            'success'   => true,
            'message'   => "No bot action is pending for player $pendingPlayer.",
            'applied'   => false,
            'retryable' => false,
        ];
    }
    $actions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
    if (empty($actions)) {
        $playerID = $savedPlayerID;
        return ['success' => true, 'message' => 'No legal bot actions are available.', 'applied' => false];
    }

    // The enumerator's pre-filters cannot fully replicate the engine's own legality chain, so the
    // chooser's top pick can turn out to be illegal for reasons only discoverable by attempting
    // it. Retry with that candidate excluded rather than returning a no-op: a real poller
    // re-invokes the SAME chooser against the SAME unchanged action set, so without this loop a
    // persistently-illegal candidate is re-chosen and re-rejected forever.
    //
    // Exclusion is by EXACT cardID string equality, not a prefix match — verified against
    // BotLegalActions.php's actual action shapes before relying on it. Every action's cardID
    // already encodes its full identity, including the verb: free-play emits
    // "myLeader-0!CustomInput!DeployLeader:Unit" vs "myLeader-0!CustomInput!LeaderAbility" for the
    // SAME leader slot (two different full strings), and the decision bridge's answers
    // (BridgeEnumerateDecisionActions) are themselves the complete candidate identity (a YES/NO
    // token, a de-duplicated MZCHOOSE choice, a whole MZMULTICHOOSE/MZSPLITASSIGN/MZREARRANGE
    // result string). So an exact-match filter removes precisely the one candidate that was tried
    // — never a sibling verb on the same card/zone slot — and $cleanAction['cardID'] is always
    // derived from the very $action the filter is asked to remove, so that candidate is
    // guaranteed to match and be dropped. That means $remaining strictly shrinks by at least one
    // entry on every failed iteration, which alone bounds this loop at count($actions) iterations
    // regardless of the "Pass is always legal" argument below — and Pass (built directly into
    // free-play's action list and never excluded by another action's identity) additionally
    // guarantees that bound is reached with a SUCCESSFUL action, not just an empty list.
    //
    // Belt-and-braces cap: the guarantee above rests on 'first-legal' always choosing an element
    // of $remaining (SWUBotChooseAction($remaining, ...) does, by construction: $actions[0]).
    // Phase 2 registers additional choosers under new profile names via SWUBotRegisterChooser();
    // nothing in that contract stops a future chooser from returning an action absent from
    // $remaining (e.g. a normalization bug), which would make the filter remove nothing and spin
    // forever. A generous, logged cap converts that into one bounded, diagnosable request instead
    // of a hung PHP process — it must never trip under normal play, so a trip is treated as a bug
    // report, not a silent fallback.
    $remaining = $actions;
    $result = null; $cleanAction = null; $noOp = true;
    $maxIterations = max(50, count($actions) * 2);
    $iterations = 0;
    $capTripped = false;
    while (!empty($remaining)) {
        if (++$iterations > $maxIterations) {
            $capTripped = true;
            error_log("SWUBot: retry loop exceeded $maxIterations iterations for seat $pendingPlayer "
                . "(started with " . count($actions) . " candidates, " . count($remaining) . " remaining) — "
                . "aborting this poll; a chooser is likely returning actions outside the candidate set.");
            $cleanAction = null;
            $result = null;
            $noOp = true;
            break;
        }
        $action = SWUBotChooseAction($remaining, $legal);
        if ($action === null) break;
        $cleanAction = [
            // Always the seat this whole step is acting for, never the candidate's own
            // 'playerID' field. SWUBotPendingDecisionSeat() skips removed DecisionQueue entries
            // but DecisionQueueController::NextDecision() (Core/DecisionQueueController.php:80-86)
            // returns $playerQueue[0] with no such check — so in principle the two could disagree
            // about which seat is actually up, and trusting the action's own field could submit a
            // mode-100 answer as the human's seat instead of the bot's. Hardening; no Phase 1
            // repro is known to require this.
            'playerID'    => $pendingPlayer,
            'mode'        => intval($action['mode'] ?? 0),
            'cardID'      => strval($action['cardID'] ?? ''),
            'buttonInput' => strval($action['buttonInput'] ?? ''),
            'chkInput'    => $action['chkInput'] ?? [],
            'inputText'   => strval($action['inputText'] ?? ''),
        ];
        $beforeHash = SWUBotComparableGamestateHash($activeGameName);
        $result = EngineExecuteLoadedAction($cleanAction, 'SWUSim', $activeGameName, ['updateCache' => true]);
        $afterHash = SWUBotComparableGamestateHash($activeGameName);
        $noOp = ($beforeHash !== null && $afterHash !== null && $beforeHash === $afterHash);
        if (!$noOp) break;
        error_log("SWUBot: no gamestate change for seat $pendingPlayer (mode={$cleanAction['mode']}, cardID={$cleanAction['cardID']}) — excluding and retrying.");
        $remaining = array_values(array_filter(
            $remaining,
            fn($a) => strval($a['cardID'] ?? '') !== $cleanAction['cardID']
        ));
    }
    $playerID = $savedPlayerID;

    if ($capTripped) {
        // Deliberately success => false and retryable => false: this is a "stop and look at the
        // log" signal, not a transient condition a retry timer should paper over. A correctly
        // behaving chooser (including 'first-legal') can never reach this branch — see the loop
        // comment above.
        return [
            'success'   => false,
            'message'   => "Bot retry loop exceeded $maxIterations iterations without a legal action; see error_log.",
            'applied'   => false,
            'retryable' => false,
        ];
    }
    // $remaining drained naturally: EVERY offered candidate (the enumerator applies no
    // availability pre-filter, so this is routinely most of the list) turned out to be a no-op,
    // and none of them was Pass itself resolving successfully.
    //
    // With BotControllerPendingPlayerForClient()'s MAIN/turn-player gate in place (fix round 1),
    // this branch should be UNREACHABLE by construction: SWUBotFreePlayActions() only emits any
    // candidates — Pass included — once it has confirmed this seat is the turn player in the
    // MAIN phase, and Pass's own gate (CustomInput.php's "myHealth" case) checks exactly those
    // same two conditions, so Pass cannot itself be a no-op here. Reaching this branch anyway
    // means the enumerator offered a Pass that didn't work, or omitted Pass altogether — an
    // enumerator defect, not a normal idle poll — so this is a bug-surfacing diagnostic, not a
    // routine "nothing to do" result, and success => false (matching the cap-trip branch) is the
    // honest encoding of that, not just an expedient one.
    //
    // success => false also happens to be load-bearing for a client-side reason, confirmed by
    // tracing Core/jsInclude.js's mode-10017 poller (~1083-1097): botStepRetryable is only read
    // inside the `response.success !== true` branch. The `else` arm (taken when success === true)
    // reschedules purely on `(window.BotController || {}).pendingPlayer` truthiness, which stays
    // nonzero here since the bot genuinely still owes a move — so a success => true response
    // would keep the 5s-ceiling poll going regardless of retryable. Only success => false routes
    // through the branch that actually honors retryable => false and suppresses that poll.
    // NextTurn.php's own per-render MaybeRunBotControllerStep() call still gives a
    // genuinely-recoverable position another look on the next state change.
    if (!$capTripped && $cleanAction !== null && $noOp) {
        error_log("SWUBot: all " . count($actions) . " candidate action(s) were no-ops for seat "
            . "$pendingPlayer, including what should have been an always-legal Pass — this "
            . "indicates an enumerator defect (a missing or broken Pass candidate), not a normal "
            . "idle poll; suppressing immediate retry.");
        return [
            'success'   => false,
            'message'   => 'Bot enumerator offered only no-op actions (Pass should always be legal here); see error_log.',
            'applied'   => false,
            'retryable' => false,
        ];
    }
    if ($cleanAction === null) {
        return ['success' => true, 'message' => 'Bot chooser returned no action.', 'applied' => false];
    }
    return [
        'success'        => !empty($result['success']),
        'message'        => strval($result['message'] ?? ''),
        'writeGamestate' => !empty($result['writeGamestate']),
        'updateCache'    => !empty($result['updateCache']),
        'applied'        => !$noOp,
        'retryable'      => true,
    ];
}
