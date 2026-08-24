<?php
// GA's implementation of the shared Core/BotController.php contract (4 functions: see that file's
// header comment), mirroring AzukiSim/Custom/GameLogic.php:830-852 and its execution block at
// 797-803. Once these 4 functions exist, the existing client-side bot-step polling (mode 10017 in
// Core/EngineActionRunner.php, the JS loop in Core/jsInclude.js) works for GA with no client
// changes — this file is the entire GA-specific surface of that integration.

// A gamestate hash for no-op detection (see ProcessBotControllerStep below), EXCLUDING bookkeeping
// that mutates on EVERY write regardless of whether the attempted action had any real effect.
// RegressionCurrentGamestateHash() alone can't be reused here — three fields were found (via
// GrandArchiveSim/Tests/BotFixtures self-play) to change on a rejected/no-op action, each one
// independently defeating a raw-text hash comparison and stranding the bot in an infinite loop:
//   - updateNumber (2nd line of every serialized gamestate — RegressionConsumeGamestateLayout's own
//     comment: "Every generated gamestate begins with currentPlayer and updateNumber"): incremented
//     unconditionally by Core/EngineActionRunner.php whenever `$result['writeGamestate']` is true,
//     which is the default for every call regardless of outcome.
//   - FlashMessage (Schemas/GrandArchiveSim/GameSchema.txt): a rejected action still typically calls
//     SetFlashMessage() with a reason ("Cannot pass while effects are pending resolution.", etc.).
//   - the MR1: match-replay recording line: every ATTEMPTED action is appended as a frame for replay
//     fidelity, on purpose, independent of whether it changed the game.
function GABotComparableGamestateHash($gameName) {
    if (!function_exists('RegressionCurrentGamestateText') || !function_exists('RegressionNormalizeNewlines')
        || !function_exists('RegressionGamestateSchemaLayout') || !function_exists('RegressionConsumeGamestateLayout')) {
        return null;
    }
    $text = RegressionCurrentGamestateText('GrandArchiveSim', $gameName);
    if (!is_string($text)) return null;
    $lines = explode("\n", RegressionNormalizeNewlines($text));
    if (count($lines) > 1) unset($lines[1]); // drop updateNumber

    $layout = RegressionGamestateSchemaLayout('GrandArchiveSim');
    $blocks = [];
    RegressionConsumeGamestateLayout($layout, $lines, $blocks);
    foreach ($blocks as $block) {
        if ($block['name'] !== 'FlashMessage') continue;
        for ($i = $block['start']; $i < $block['start'] + $block['length']; $i++) unset($lines[$i]);
    }
    foreach ($lines as $i => $line) {
        if (strpos($line, 'MR1:') === 0) unset($lines[$i]);
    }
    return hash('sha256', implode("\n", $lines));
}

function SetGABotPlayers($players) {
    DecisionQueueController::StoreVariable("BotPlayers", NormalizeGoldfishPlayers($players));
}

function GetGABotPlayers() {
    return NormalizeGoldfishPlayers(DecisionQueueController::GetVariable("BotPlayers"));
}

function GameBotControllerMode() {
    return GAGameMode() === 'bot' ? 'bot' : '';
}

function GetBotControllerPlayers() {
    if (GAGameMode() !== 'bot') return [];
    return GetGABotPlayers();
}

// Compact, deterministic context for an Effect Stack recovery failure. Keep this independent of
// object indexes/unique IDs so a regression fixture can compare it across fresh game runs.
function GABotIdleEffectStackSignature() {
    $stack = function_exists('GetLiveEffectStackEntries') ? GetLiveEffectStackEntries() : [];
    $top = !empty($stack) ? $stack[count($stack) - 1] : null;
    $queues = [];
    if (function_exists('GetDecisionQueue')) {
        foreach ([1, 2] as $seat) {
            foreach (GetDecisionQueue($seat) as $entry) {
                if ($entry === null || !empty($entry->removed)) continue;
                $queues[] = [
                    'seat' => $seat,
                    'type' => strval($entry->Type ?? ''),
                    'param' => strval($entry->Param ?? ''),
                ];
            }
        }
    }
    return [
        'cardId' => strval($top->CardID ?? ''),
        'controller' => intval($top->Controller ?? 0),
        'triggerType' => strval($top->TriggerType ?? ''),
        'turnEffects' => array_values($top->TurnEffects ?? []),
        'turnPlayer' => function_exists('GetTurnPlayer') ? intval(GetTurnPlayer()) : 0,
        'phase' => function_exists('GetCurrentPhase') ? strval(GetCurrentPhase()) : '',
        'queues' => $queues,
    ];
}

// Which seat currently needs a bot move, or 0 if none. A pending DecisionQueue response always
// takes priority (matches GABotPendingDecisionPlayer's own precedence); otherwise, if the current
// free-play turn player is bot-controlled, that seat is pending.
function BotControllerPendingPlayerForClient() {
    if (GAGameMode() !== 'bot') return 0;
    $botPlayers = GetGABotPlayers();
    if (empty($botPlayers)) return 0;

    $decisionPlayer = function_exists('GABotPendingDecisionPlayer') ? GABotPendingDecisionPlayer() : 0;
    if ($decisionPlayer !== 0) {
        return in_array($decisionPlayer, $botPlayers, true) ? $decisionPlayer : 0;
    }

    if (function_exists('GetCurrentPhase') && GetCurrentPhase() !== 'MAIN') return 0;
    $turnPlayer = function_exists('GetTurnPlayer') ? intval(GetTurnPlayer()) : 0;
    return in_array($turnPlayer, $botPlayers, true) ? $turnPlayer : 0;
}

function ProcessBotControllerStep($requestingPlayer = 0, $folderPath = '', $gameNameOverride = '') {
    if ($folderPath !== '' && $folderPath !== 'GrandArchiveSim') {
        return ['success' => false, 'message' => 'Bot controller does not handle this game.', 'applied' => false];
    }
    if (GAGameMode() !== 'bot') {
        return ['success' => true, 'message' => '', 'applied' => false, 'retryable' => false];
    }

    global $gameName;
    $activeGameName = $gameNameOverride !== '' ? $gameNameOverride : strval($gameName ?? '');
    if ($activeGameName === '') {
        return ['success' => false, 'message' => 'Bot controller game is not loaded.', 'applied' => false, 'retryable' => false];
    }

    $pendingPlayer = BotControllerPendingPlayerForClient();
    // A card can be left on the Effect Stack after engine-owned static queue work completes.
    // In that state there is deliberately no player decision yet, but normal MAIN actions (most
    // visibly Pass) are illegal until the stack-priority window is restored. The standard GA
    // recovery helper queues/resolves that window; persist this outer mode-10017 action and let
    // the next poll answer any newly-created player decision.
    $idleStack = function_exists('GetLiveEffectStackEntries') ? GetLiveEffectStackEntries() : [];
    if (function_exists('GABotPendingDecisionPlayer') && GABotPendingDecisionPlayer() === 0 && !empty($idleStack)) {
        $beforeSignature = GABotIdleEffectStackSignature();
        $resumed = function_exists('ResumeIdleEffectStackIfNeeded') && ResumeIdleEffectStackIfNeeded();
        $afterSignature = GABotIdleEffectStackSignature();
        // ResumeIdleEffectStackIfNeeded mutates the loaded in-memory state. The outer mode-10017
        // action performs the first write, so a disk-backed gamestate hash is intentionally still
        // unchanged here. Compare the semantic live stack/queue signatures instead.
        if ($resumed && $beforeSignature !== $afterSignature) {
            return [
                'success' => true,
                'message' => 'Resumed pending Effect Stack resolution.',
                'writeGamestate' => true,
                'updateCache' => true,
                'applied' => true,
                'retryable' => true,
            ];
        }
        $failure = ['code' => 'idle_effect_stack_no_progress', 'before' => $beforeSignature,
            'after' => $afterSignature, 'resumeAttempted' => $resumed];
        error_log('GABot: ' . json_encode($failure));
        return [
            'success' => false,
            'message' => json_encode($failure),
            'writeGamestate' => false,
            'updateCache' => false,
            'applied' => false,
            'retryable' => false,
        ];
    }
    $pendingPlayer = BotControllerPendingPlayerForClient();
    if ($pendingPlayer === 0) {
        return ['success' => true, 'message' => 'No bot action is currently pending.', 'applied' => false, 'retryable' => false];
    }

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $pendingPlayer;

    $legal = GABotLegalActions($activeGameName, $pendingPlayer);
    $actingPlayer = intval($legal['playerID'] ?? 0);
    if ($actingPlayer !== $pendingPlayer) {
        $playerID = $savedPlayerID;
        return ['success' => true, 'message' => "No bot action is pending for player $pendingPlayer.", 'applied' => false];
    }

    $actions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
    if (empty($actions)) {
        $playerID = $savedPlayerID;
        return ['success' => true, 'message' => 'No legal bot actions are available.', 'applied' => false];
    }

    // The enumerator's pre-filters are cheap and can't fully replicate the engine's own legality
    // chain (BeginCombatPhase in particular IS that chain — see BotLegalActions.php's comment), so
    // the chooser's top pick can turn out to be illegal for reasons only discoverable by actually
    // attempting it. Retry with that candidate excluded rather than returning a single no-op and
    // trusting the caller to try something different next poll — a real poller (and the self-play
    // test harness) re-invokes the SAME chooser against the SAME unchanged legal-action set, so
    // without this loop a persistently-illegal-but-cheaply-legal-looking candidate (e.g. a stale
    // field index) gets re-chosen and re-rejected forever. Bounded by the action list itself: the
    // always-present end-turn Pass action is never excluded and is never a no-op (it always
    // advances something), so this loop is guaranteed to terminate with an applied action.
    $remaining = $actions;
    $result = null; $cleanAction = null; $noOp = true;
    while (!empty($remaining)) {
        $action = GABotChooseAction($remaining, $legal);
        if ($action === null) break;
        $cleanAction = [
            'playerID' => intval($action['playerID'] ?? $pendingPlayer),
            'mode' => intval($action['mode'] ?? 0),
            'cardID' => strval($action['cardID'] ?? ''),
            'buttonInput' => strval($action['buttonInput'] ?? ''),
            'chkInput' => $action['chkInput'] ?? [],
            'inputText' => strval($action['inputText'] ?? ''),
        ];
        // No-op detection (same primitive Azuki's bot uses, AzukiSim/Custom/GameLogic.php:796,811-819):
        // comparing a gamestate hash before/after is what actually distinguishes "the action did
        // something" from "it quietly no-op'd".
        $beforeHash = GABotComparableGamestateHash($activeGameName);
        $result = EngineExecuteLoadedAction($cleanAction, 'GrandArchiveSim', $activeGameName, ['updateCache' => true]);
        $afterHash = GABotComparableGamestateHash($activeGameName);
        $noOp = ($beforeHash !== null && $afterHash !== null && $beforeHash === $afterHash);
        if (!$noOp) break;
        error_log("GABot: action produced no gamestate change for seat $pendingPlayer (mode={$cleanAction['mode']}, cardID={$cleanAction['cardID']}) — excluding and retrying.");
        $remaining = array_values(array_filter($remaining, fn($a) => strval($a['cardID'] ?? '') !== $cleanAction['cardID']));
    }
    $playerID = $savedPlayerID;
    if ($cleanAction === null) {
        return ['success' => true, 'message' => 'Bot chooser returned no action.', 'applied' => false];
    }
    return [
        'success' => !empty($result['success']),
        'message' => strval($result['message'] ?? ''),
        'writeGamestate' => !empty($result['writeGamestate']),
        'updateCache' => !empty($result['updateCache']),
        'applied' => !$noOp,
        'retryable' => true,
    ];
}
