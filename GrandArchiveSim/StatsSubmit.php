<?php
// Assemble a full per-game telemetry payload (champion state + card stats + turn aggregates +
// attack-by-attack combat log) from GA's Telemetry.php accumulator and POST it to an external
// stats API, once, on final match completion. Mirrors SWUSim/StatsSubmit.php's shape/flow;
// field names differ to reflect GA's own mechanics (Materialize/Reserve/Champion level, not
// Play/Resource/Leader-Base) and there is no card-identifier translation step, since GA CardIDs
// are already the stable UUID the external deckbuilders use.

// Snapshot one seat's champion (cardId/name/element/classes/level/hp), or a zeroed shape if it
// couldn't be resolved (e.g. an early concede before any champion materialized).
function GASnapshotChampion($seat) {
    $out = ['championId' => '', 'championName' => '', 'element' => '', 'classes' => [], 'level' => 0, 'hp' => 0];
    if (!function_exists('FindChampionMZ') || !function_exists('GetZoneObject')) return $out;
    // FindChampionMZ resolves "myField" vs "theirField" relative to the ambient $playerID global,
    // NOT its own $player argument (GrandArchiveSim/Custom/CardDQHandlers.php) — same perspective
    // gotcha already worked around in BotHeuristic.php's GABotOpponentChampionHP. Without pinning
    // $playerID to this seat, whichever seat happens to equal whatever $playerID was last left at
    // (e.g. the player who submitted the match's final action) gets its OWN champion reported
    // correctly, while the OTHER seat silently gets the field OPPONENT's champion instead — every
    // real match submission was at risk of one seat's championId/element/classes/level/hp being
    // the wrong player's data.
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = intval($seat);
    // GetZoneObject() below resolves "myField"/"theirField" the same ambient-relative way, so
    // $playerID must stay pinned through that call too, not just FindChampionMZ.
    $mz = FindChampionMZ(intval($seat));
    if ($mz === null) { $playerID = $savedPlayerID; return $out; }
    $obj = GetZoneObject($mz);
    $playerID = $savedPlayerID;
    if ($obj === null) return $out;
    $out['championId'] = strval($obj->CardID ?? '');
    if (function_exists('CardName')) $out['championName'] = strval(CardName($out['championId']) ?? '');
    if (function_exists('EffectiveCardElement')) $out['element'] = strval(EffectiveCardElement($obj));
    if (function_exists('EffectiveCardClasses')) {
        $classes = EffectiveCardClasses($obj);
        $out['classes'] = is_array($classes) ? array_values($classes) : (is_string($classes) ? explode(',', $classes) : []);
    }
    if (function_exists('ObjectCurrentLevel')) $out['level'] = intval(ObjectCurrentLevel($obj));
    if (function_exists('ObjectCurrentHP'))    $out['hp']    = intval(ObjectCurrentHP($obj));
    return $out;
}

// Snapshot the just-finished game's gamestate (called from the after-action hook, where the
// gamestate is loaded and current). Returns the array stored into this game's Match.json 'detail'.
function GACaptureCurrentGameDetail() {
    $detail = ['firstPlayer' => 0, 'turns' => 0, 'champions' => ['1' => null, '2' => null], 'telemetry' => ['cards' => [], 'turns' => [], 'combatEvents' => []]];
    if (function_exists('GetFirstPlayer')) { $fp = &GetFirstPlayer(); $detail['firstPlayer'] = intval($fp); }
    if (function_exists('GetTurnNumber'))  { $tn = &GetTurnNumber();  $detail['turns'] = intval($tn); }
    // End-game combat bypasses EndPhase(), so flush the active partial turn before copying the
    // accumulator into Match.json. This is intentionally before the telemetry read below.
    if (function_exists('GATelemetryFlushPendingTurns')) GATelemetryFlushPendingTurns($detail['turns']);
    foreach ([1, 2] as $s) { $detail['champions'][strval($s)] = GASnapshotChampion($s); }
    if (function_exists('GATelemetryGet')) {
        $t = GATelemetryGet();
        $detail['telemetry'] = ['cards' => $t['cards'] ?? [], 'turns' => $t['turns'] ?? [], 'combatEvents' => $t['combatEvents'] ?? []];
    }
    return $detail;
}

// Build the default stats-API payload for one game record (with detail attached).
function GABuildGameResultPayload($match, $game) {
    $d = $game['detail'] ?? [];
    $winner = intval($game['winner'] ?? 0);
    $tel = $d['telemetry'] ?? ['cards' => [], 'turns' => [], 'combatEvents' => []];
    $champions = $d['champions'] ?? ['1' => null, '2' => null];
    $buildPlayer = function($seat) use ($tel, $champions, $match) {
        $s = strval($seat);
        $champ = $champions[$s] ?? ['championId' => '', 'championName' => '', 'element' => '', 'classes' => [], 'level' => 0, 'hp' => 0];
        $cardStats = [];
        foreach (($tel['cards'][$s] ?? []) as $cid => $c) {
            $cardStats[strval($cid)] = [
                'drawn' => intval($c['drawn'] ?? 0), 'drawnToMemory' => intval($c['drawnToMemory'] ?? 0),
                'materialized' => intval($c['materialized'] ?? 0), 'reserved' => intval($c['reserved'] ?? 0),
                'discarded' => intval($c['discarded'] ?? 0), 'activated' => intval($c['activated'] ?? 0),
            ];
        }
        $turnStats = [];
        foreach (($tel['turns'] ?? []) as $tr) {
            if (intval($tr['seat'] ?? 0) !== intval($seat)) continue;
            $turnStats[] = [
                'turn' => intval($tr['turn'] ?? 0), 'cardsPlayed' => intval($tr['cardsPlayed'] ?? 0),
                'memorySpent' => intval($tr['memorySpent'] ?? 0), 'reserveSpent' => intval($tr['reserveSpent'] ?? 0),
                'damageDealt' => intval($tr['damageDealt'] ?? 0), 'damageTaken' => intval($tr['damageTaken'] ?? 0),
                'healed' => intval($tr['healed'] ?? 0), 'level' => intval($tr['level'] ?? 0), 'hp' => intval($tr['hp'] ?? 0),
            ];
        }
        return [
            'deckLink' => strval($match['players'][$s]['deckLink'] ?? ''),
            'championId' => $champ['championId'], 'championName' => strval($champ['championName'] ?? ''),
            'element' => $champ['element'], 'classes' => $champ['classes'],
            'endLevel' => intval($champ['level']), 'endHp' => intval($champ['hp']),
            // Cast so an empty map serializes as `{}` rather than PHP's ambiguous empty `[]`.
            'cardStats' => (object)$cardStats, 'turnStats' => $turnStats,
        ];
    };
    $matchId = strval($match['matchId'] ?? '');
    $gameNumber = intval($game['gameNumber'] ?? 1);
    $createdAt = intval($match['createdAt'] ?? 0);
    return [
        'schemaVersion' => 1,
        'submissionId' => $matchId . ':' . $gameNumber,
        // Match creation time is stable across retries, unlike the wall clock at delivery time.
        'submittedAt' => gmdate('c', $createdAt > 0 ? $createdAt : 0),
        'source' => [
            'application' => 'TCGEngine',
            'game' => 'GrandArchiveSim',
            'version' => strval($GLOBALS['grandArchiveStatsSourceVersion'] ?? 'unknown'),
        ],
        'matchId' => $matchId, 'format' => strval($match['format'] ?? ''),
        'bestOf' => intval($match['bestOf'] ?? 1),
        'gameName' => strval($game['gameName'] ?? ''), 'gameNumber' => $gameNumber,
        'winner' => $winner, 'firstPlayer' => intval($d['firstPlayer'] ?? 0), 'turns' => intval($d['turns'] ?? 0),
        // Overall series result — known at submit time since GASubmitMatchResults only runs once
        // the whole match is decided, so every game's payload can carry the final series outcome
        // without the receiving API needing to correlate multiple payloads by matchId.
        'matchWinner' => intval($match['winner'] ?? 0),
        'matchWins' => ['1' => intval($match['wins']['1'] ?? 0), '2' => intval($match['wins']['2'] ?? 0)],
        'players' => ['1' => $buildPlayer(1), '2' => $buildPlayer(2)],
        'combatEvents' => $tel['combatEvents'] ?? [],
    ];
}

function GAPostGameResult($apiUrl, $apiKey, $payload) {
    $body = json_encode($payload);
    if ($body === false) return ['ok' => false, 'retryable' => false, 'httpCode' => 0, 'error' => 'json_encode_failed'];
    $last = ['ok' => false, 'retryable' => true, 'httpCode' => 0, 'error' => 'not_attempted'];
    for ($attempt = 1; $attempt <= 3; ++$attempt) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        ]);
        $resp = curl_exec($ch);
        $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $curlErr = curl_error($ch);
        $ok = ($resp !== false && $code >= 200 && $code < 300);
        if ($ok) {
            $decoded = json_decode($resp, true);
            if (is_array($decoded) && array_key_exists('success', $decoded) && $decoded['success'] === false) $ok = false;
        }
        if ($ok) return ['ok' => true, 'retryable' => false, 'httpCode' => $code, 'error' => ''];
        $retryable = ($resp === false || $code === 408 || $code === 429 || $code >= 500);
        $last = ['ok' => false, 'retryable' => $retryable, 'httpCode' => $code,
            'error' => $curlErr !== '' ? $curlErr : substr((string)$resp, 0, 200)];
        if (!$retryable || $attempt === 3) break;
        usleep($attempt * 200000);
    }
    return $last;
}

// Matches created before the preference existed keep the requested default-on behavior.
function GAShouldShareAnonymizedGameplayData($match) {
    return !is_array($match)
        || !array_key_exists('shareAnonymizedGameplayData', $match)
        || !empty($match['shareAnonymizedGameplayData']);
}

// Submit one result per decided game when the series completes. Successful games are sealed
// individually; failed games remain retryable. The receiver deduplicates matchId:gameNumber.
function GASubmitMatchResults($matchId) {
    $m = MatchRead('GrandArchiveSim', $matchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'complete' || !empty($m['statsSubmitted'])) return;
    if (!GAShouldShareAnonymizedGameplayData($m)) {
        MatchWithLock('GrandArchiveSim', $matchId, function (&$mm) {
            $mm['statsSubmitted'] = true;
            $mm['statsStatus'] = 'skipped_opt_out';
        });
        return;
    }
    if (count($m['players'] ?? []) > 2) {
        // GA is a 2-seat game; this guard exists only so an unexpected N-seat match can't crash here.
        MatchWithLock('GrandArchiveSim', $matchId, function (&$mm) { $mm['statsSubmitted'] = true; $mm['statsStatus'] = 'skipped_multiplayer'; });
        return;
    }
    $apiUrl = $GLOBALS['grandArchiveStatsApiUrl'] ?? '';
    $apiKey = $GLOBALS['grandArchiveStatsApiKey'] ?? '';
    if ($apiUrl === '' || $apiKey === '') {
        MatchWithLock('GrandArchiveSim', $matchId, function (&$mm) { $mm['statsStatus'] = 'skipped_unconfigured'; });
        return;
    }
    $attempted = 0; $succeeded = 0; $failed = 0;
    foreach (($m['games'] ?? []) as $gameIndex => $g) {
        if (($g['winner'] ?? null) === null) continue;
        if (($g['statsDelivery']['status'] ?? '') === 'success') { $succeeded++; continue; }
        // Don't record a game that ended before Round 2 (an early concede/abandon).
        if (intval($g['detail']['turns'] ?? 0) < 2) continue;
        $attempted++;
        $result = GAPostGameResult($apiUrl, $apiKey, GABuildGameResultPayload($m, $g));
        $deliveryStatus = !empty($result['ok']) ? 'success' : (!empty($result['retryable']) ? 'retryable_failure' : 'rejected');
        MatchWithLock('GrandArchiveSim', $matchId, function (&$mm) use ($gameIndex, $deliveryStatus, $result) {
            if (!isset($mm['games'][$gameIndex])) return;
            $previousAttempts = intval($mm['games'][$gameIndex]['statsDelivery']['attempts'] ?? 0);
            $mm['games'][$gameIndex]['statsDelivery'] = [
                'status' => $deliveryStatus,
                'attempts' => $previousAttempts + 1,
                'lastAttemptAt' => time(),
                'httpCode' => intval($result['httpCode'] ?? 0),
            ];
        });
        if (!empty($result['ok'])) {
            $succeeded++;
        } else {
            $failed++;
            error_log('GA stats submit FAILED game=' . strval($g['gameName'] ?? '?')
                . ' http=' . intval($result['httpCode'] ?? 0) . ' error=' . strval($result['error'] ?? ''));
        }
    }
    $status = ($attempted === 0 && $succeeded === 0) ? 'skipped_early' : (($failed > 0) ? 'failed' : 'success');
    MatchWithLock('GrandArchiveSim', $matchId, function (&$mm) use ($status) {
        $mm['statsStatus'] = $status;
        $mm['statsSubmitted'] = ($status === 'success' || $status === 'skipped_early');
    });
}
