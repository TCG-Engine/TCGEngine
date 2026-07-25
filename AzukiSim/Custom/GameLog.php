<?php

/**
 * Best-effort, browser-owned game logging for human AzukiSim matches.
 *
 * The server keeps only the current update's event frame in the normal cache.
 * Browsers append accepted frames to IndexedDB. There is intentionally no
 * acknowledgement or catch-up protocol: if the live game transport breaks,
 * the locally stored log may be incomplete.
 */

function GameLogEnableForHumanSession($sessionKind = 'human_pvp') {
    DecisionQueueController::StoreVariable('AzukiGameLogEnabled', '1');
    DecisionQueueController::StoreVariable('AzukiGameLogSessionKind', strval($sessionKind));
    DecisionQueueController::StoreVariable('AzukiGameLogStartedAt', gmdate('c'));
    DecisionQueueController::StoreVariable('AzukiGameLogNextSeq', '1');
    DecisionQueueController::StoreVariable('AzukiGameLogLastTurn', '0');
}

function AzukiGameLogIsRegressionSession() {
    global $gameName, $azukiGameLogRegressionSession;
    if(isset($azukiGameLogRegressionSession)) return boolval($azukiGameLogRegressionSession);

    $resolvedGameName = strval($gameName ?? '');
    $isRegression = false;
    if($resolvedGameName !== '' && function_exists('RegressionIsRecordingActive')) {
        $isRegression = RegressionIsRecordingActive('AzukiSim', $resolvedGameName);
    }
    if(!$isRegression && $resolvedGameName !== '' && function_exists('RegressionReadReplayState')) {
        $isRegression = is_array(RegressionReadReplayState('AzukiSim', $resolvedGameName));
    }

    $azukiGameLogRegressionSession = $isRegression;
    return $isRegression;
}

function GameLogIsEnabled() {
    if(DecisionQueueController::GetVariable('AzukiGameLogEnabled') !== '1') return false;
    if(!empty($GLOBALS['azukiGameLogAutomatedFrame'])) return false;
    if(function_exists('AzukiGameMode') && AzukiGameMode() === 'tutorial') return false;
    if(function_exists('MatchReplayIsPlaybackSession') && MatchReplayIsPlaybackSession()) return false;
    if(AzukiGameLogIsRegressionSession()) return false;
    return true;
}

function GameLogBeginFrame($action = null, $options = []) {
    global $azukiGameLogFrameEvents, $azukiGameLogFrameDepth;
    $depth = intval($azukiGameLogFrameDepth ?? 0);
    if($depth === 0) {
        $azukiGameLogFrameEvents = [];
        $GLOBALS['azukiGameLogAutomatedFrame'] = is_array($options) && !empty($options['disableRecording']);
        unset($GLOBALS['azukiGameLogRegressionSession']);
    }
    $azukiGameLogFrameDepth = $depth + 1;
}

function AzukiGameLogCardLabel($cardID) {
    $cardID = strval($cardID);
    if($cardID === '') return '-';
    $name = function_exists('CardName') ? trim(strval(CardName($cardID))) : '';
    return ($name !== '' && $name !== '-') ? ($name . ' (' . $cardID . ')') : $cardID;
}

function AzukiGameLogObjectLabel($obj) {
    if(!is_object($obj)) return '-';
    return AzukiGameLogCardLabel($obj->CardID ?? '');
}

function GameLogEvent($type, $fields = []) {
    global $azukiGameLogFrameEvents;
    if(!GameLogIsEnabled()) return;
    if(!is_array($azukiGameLogFrameEvents)) $azukiGameLogFrameEvents = [];

    $nextSeq = max(1, intval(DecisionQueueController::GetVariable('AzukiGameLogNextSeq') ?? 1));
    $event = ['seq' => $nextSeq, 'e' => strval($type)];
    if(is_array($fields)) {
        foreach($fields as $key => $value) {
            if($value === null || $value === '' || $value === []) continue;
            $event[strval($key)] = $value;
        }
    }
    $azukiGameLogFrameEvents[] = $event;
    DecisionQueueController::StoreVariable('AzukiGameLogNextSeq', strval($nextSeq + 1));
    if(strval($type) === 'concede') {
        DecisionQueueController::StoreVariable('AzukiGameLogEndReason', 'concede');
    }
}

function AzukiGameLogZoneCards($zone) {
    $cards = [];
    if(!is_array($zone)) return $cards;
    foreach($zone as $obj) {
        if(!is_object($obj) || !empty($obj->removed)) continue;
        $cards[] = AzukiGameLogObjectLabel($obj);
    }
    return $cards;
}

function AzukiGameLogZoneCount($zone) {
    return count(AzukiGameLogZoneCards($zone));
}

function AzukiGameLogFieldCards($zone, $player) {
    $cards = [];
    if(!is_array($zone)) return $cards;
    foreach($zone as $obj) {
        if(!is_object($obj) || !empty($obj->removed)) continue;
        $label = AzukiGameLogObjectLabel($obj);
        if(isset($obj->Status) && intval($obj->Status) === 1) $label .= ' [tapped]';
        if(isset($obj->Damage) && intval($obj->Damage) > 0) $label .= ' [damage:' . intval($obj->Damage) . ']';
        $cards[] = $label;
    }
    return $cards;
}

function AzukiGameLogIKZSummary($player) {
    $area = GetIKZArea($player);
    $total = 0;
    $tapped = 0;
    if(is_array($area)) {
        foreach($area as $obj) {
            if(!is_object($obj) || !empty($obj->removed)) continue;
            ++$total;
            if(isset($obj->Status) && intval($obj->Status) === 1) ++$tapped;
        }
    }
    return [
        'area' => $total,
        'tapped' => $tapped,
        'tokens' => intval(GetIKZToken($player)),
    ];
}

function AzukiGameLogSnapshot() {
    $snapshot = [
        'hp' => [
            'p1' => function_exists('LeaderCurrentHealth') ? intval(LeaderCurrentHealth(1)) : 0,
            'p2' => function_exists('LeaderCurrentHealth') ? intval(LeaderCurrentHealth(2)) : 0,
        ],
        'ikz' => [
            'p1' => AzukiGameLogIKZSummary(1),
            'p2' => AzukiGameLogIKZSummary(2),
        ],
        'hand' => [
            'p1' => AzukiGameLogZoneCards(GetHand(1)),
            'p2' => AzukiGameLogZoneCards(GetHand(2)),
        ],
        'garden' => [
            'p1' => AzukiGameLogFieldCards(GetGarden(1), 1),
            'p2' => AzukiGameLogFieldCards(GetGarden(2), 2),
        ],
        'alley' => [
            'p1' => AzukiGameLogFieldCards(GetAlley(1), 1),
            'p2' => AzukiGameLogFieldCards(GetAlley(2), 2),
        ],
        'discard' => [
            'p1' => AzukiGameLogZoneCount(GetDiscard(1)),
            'p2' => AzukiGameLogZoneCount(GetDiscard(2)),
        ],
        'deck' => [
            'p1' => AzukiGameLogZoneCount(GetDeck(1)),
            'p2' => AzukiGameLogZoneCount(GetDeck(2)),
        ],
    ];
    return $snapshot;
}

function AzukiGameLogRecordTurnStart($player = null) {
    if(!GameLogIsEnabled()) return;
    $turn = max(1, intval(GetTurnNumber()));
    $lastTurn = intval(DecisionQueueController::GetVariable('AzukiGameLogLastTurn') ?? 0);
    if($lastTurn >= $turn) return;
    $player = intval($player ?? GetTurnPlayer());
    GameLogEvent('turn_start', [
        'by' => 'p' . $player,
        'turn' => $turn,
        'own_turn' => intval(floor(($turn + ($player === 1 ? 1 : 0)) / 2)),
        'snap' => AzukiGameLogSnapshot(),
    ]);
    DecisionQueueController::StoreVariable('AzukiGameLogLastTurn', strval($turn));
}

function AzukiGameLogFrameMetadata($gameName, $updateNumber) {
    $leader = [];
    $gate = [];
    for($player = 1; $player <= 2; ++$player) {
        $leaderObj = null;
        $garden = GetGarden($player);
        if(is_array($garden)) {
            foreach($garden as $obj) {
                if(!is_object($obj) || !empty($obj->removed)) continue;
                if(function_exists('CardType') && CardType($obj->CardID ?? '') === 'LEADER') {
                    $leaderObj = $obj;
                    break;
                }
            }
        }
        $gateZone = GetGate($player);
        $gateObj = is_array($gateZone) && isset($gateZone[0]) ? $gateZone[0] : null;
        $leader['p' . $player] = AzukiGameLogObjectLabel($leaderObj);
        $gate['p' . $player] = AzukiGameLogObjectLabel($gateObj);
    }
    $winner = function_exists('AzukiGameOverWinner') ? intval(AzukiGameOverWinner()) : 0;
    $metadata = [
        'schema' => 'azuki-gamelog@1.0.0',
        'game_id' => strval($gameName),
        'update' => intval($updateNumber),
        'session_kind' => strval(DecisionQueueController::GetVariable('AzukiGameLogSessionKind') ?? 'human_pvp'),
        'started_at' => strval(DecisionQueueController::GetVariable('AzukiGameLogStartedAt') ?? ''),
        'turn' => intval(GetTurnNumber()),
        'leader' => $leader,
        'gate' => $gate,
        'winner' => $winner,
    ];
    if($winner > 0) $metadata['final_snap'] = AzukiGameLogSnapshot();
    return $metadata;
}

function GameLogCommitFrame($gameName, $updateNumber, $action = null, $result = null) {
    global $azukiGameLogFrameEvents, $azukiGameLogFrameDepth;
    $depth = max(0, intval($azukiGameLogFrameDepth ?? 1) - 1);
    $azukiGameLogFrameDepth = $depth;
    if($depth > 0) return;
    if(!GameLogIsEnabled()) return;

    if(strval(GetCurrentPhase()) === 'MAIN') AzukiGameLogRecordTurnStart(GetTurnPlayer());

    $payload = AzukiGameLogFrameMetadata($gameName, $updateNumber);
    $payload['events'] = is_array($azukiGameLogFrameEvents) ? array_values($azukiGameLogFrameEvents) : [];
    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if($encoded === false) $encoded = '{}';
    if(function_exists('WriteCache')) WriteCache(strval($gameName) . '_azuki_gamelog', $encoded);
    $azukiGameLogFrameEvents = [];
}

function AzukiGameLogFilterSnapshotForViewer($snapshot, $viewerPlayer) {
    if(!is_array($snapshot)) return $snapshot;
    $viewerPlayer = intval($viewerPlayer);
    if(isset($snapshot['hand']) && is_array($snapshot['hand'])) {
        for($player = 1; $player <= 2; ++$player) {
            $key = 'p' . $player;
            if($player === $viewerPlayer) continue;
            $hand = $snapshot['hand'][$key] ?? [];
            $snapshot['hand'][$key] = is_array($hand) ? count($hand) : intval($hand);
        }
    }
    return $snapshot;
}

function AzukiGameLogFilterEventForViewer($event, $viewerPlayer) {
    if(!is_array($event)) return null;
    $viewerPlayer = intval($viewerPlayer);
    if(isset($event['hidden']) && is_array($event['hidden'])) {
        $for = $event['hidden']['for'] ?? null;
        $allowed = is_array($for) ? in_array('p' . $viewerPlayer, $for, true) : strval($for) === ('p' . $viewerPlayer);
        if(!$allowed) unset($event['hidden']);
    }
    if(isset($event['snap'])) $event['snap'] = AzukiGameLogFilterSnapshotForViewer($event['snap'], $viewerPlayer);
    return $event;
}

function GameLogClientPayload($viewerPlayer, $gameName) {
    $empty = [
        'enabled' => GameLogIsEnabled(),
        'game_id' => strval($gameName),
        'events' => [],
    ];
    if(!$empty['enabled'] || !function_exists('ReadCache')) return $empty;
    $decoded = json_decode(strval(ReadCache(strval($gameName) . '_azuki_gamelog')), true);
    if(!is_array($decoded)) return $empty;
    $decoded['enabled'] = true;
    $filtered = [];
    foreach(($decoded['events'] ?? []) as $event) {
        $visible = AzukiGameLogFilterEventForViewer($event, $viewerPlayer);
        if(is_array($visible)) $filtered[] = $visible;
    }
    $decoded['events'] = $filtered;
    if(isset($decoded['final_snap'])) {
        $decoded['final_snap'] = AzukiGameLogFilterSnapshotForViewer($decoded['final_snap'], $viewerPlayer);
    }
    return $decoded;
}

