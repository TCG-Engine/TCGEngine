<?php
// Shadow mode (env SWUPGN_SHADOW=1, schema suite only): every test game is recorded, each WHEN action is one frame, and
// at the end the recorded events are folded and compared with a keyframe built from the live board. One JSON line per
// test goes to $SWUPGN_SHADOW_OUT (default <tmp>/swupgn-shadow.jsonl); DevTools/swupgn-shadow-report.php sums them up.
// Never on in production.

function SwuPgnShadowOn(): bool {
    return getenv('SWUPGN_SHADOW') === '1';
}

function _SwuPgnShadowKeyframeEvent(string $seq, string $t, int $round): ?array {
    global $gameName;
    $state = SwuPgnStateRead(strval($gameName));
    $pos = _SwuPgnRecPos($state, SwuPgnSnapshot());
    $kf = SwuPgnKeyframe(SwuPgnSnapshot(), $state['ledger'] ?? SwuPgnLedgerInit([]),
        ['round' => $round, 'phase' => SWUPGN_PHASE_NAMES[$pos['phase']] ?? 'action', 'initiativeTaken' => !empty($state['initiativeTaken'])]);
    return $kf === null ? null : ['seq' => $seq, 't' => $t, 'round' => $round, 'keyframe' => $kf];
}

// After the test game is set up: start recording it, and keep an opening keyframe (in memory — the recording itself, which
// the game's own finish may write files from, stays an ordinary one) so the final comparison exempts nothing.
function SwuPgnShadowBegin(string $label): void {
    global $gameName;
    $GLOBALS['SWU_PGN_SHADOW'] = ['label' => $label, 'actions' => 0, 'ns' => 0, 'error' => null, 'start' => null, 'skipped' => null];
    // A test game that ends writes a health line: into a scratch file, never the real log.
    $GLOBALS['SWUPGN_HEALTH_FILE'] = sys_get_temp_dir() . '/swupgn-shadow-health-' . getmypid() . '.jsonl';
    if (is_file($GLOBALS['SWUPGN_HEALTH_FILE'])) unlink($GLOBALS['SWUPGN_HEALTH_FILE']);
    // The writer records two-seat PvP only (a 3-4 seat Twin Suns game has no SWU-PGN form): nothing to shadow.
    if (SeatCountForGame() > 2) { $GLOBALS['SWU_PGN_SHADOW']['skipped'] = 'multi-seat'; return; }
    try {
        SwuPgnRecForceForTest(strval($gameName));
        $GLOBALS['SWU_PGN_SHADOW']['start'] = _SwuPgnShadowKeyframeEvent('R0.S.shadow-start', 'ROUND_START', intval(GetTurnNumber()));
    } catch (Throwable $e) {
        $GLOBALS['SWU_PGN_SHADOW']['error'] = 'begin: ' . $e->getMessage();
    }
}

// One WHEN action as one recorded frame; only the recorder's own time is counted.
function SwuPgnShadowAction(callable $action): void {
    $t0 = hrtime(true);
    SwuPgnRecFrameBegin();
    $spent = hrtime(true) - $t0;
    try {
        $action();
    } finally {
        $t1 = hrtime(true);
        SwuPgnRecFrameCommit();
        $spent += hrtime(true) - $t1;
        if (isset($GLOBALS['SWU_PGN_SHADOW'])) { $GLOBALS['SWU_PGN_SHADOW']['actions']++; $GLOBALS['SWU_PGN_SHADOW']['ns'] += $spent; }
    }
}

function SwuPgnShadowEnd(): void {
    global $gameName;
    $sh = $GLOBALS['SWU_PGN_SHADOW'] ?? null;
    unset($GLOBALS['SWU_PGN_SHADOW']);
    if ($sh === null) return;
    $g = strval($gameName);
    $line = ['test' => $sh['label'], 'actions' => $sh['actions'], 'recorderMs' => round($sh['ns'] / 1e6, 3), 'error' => $sh['error']];
    if ($sh['skipped'] !== null) $line = ['test' => $sh['label'], 'skipped' => $sh['skipped']];
    else try {
        $meta = SwuPgnMetaRead($g) ?? [];
        $state = SwuPgnStateRead($g);
        $recorded = SwuPgnEventsRead($g);
        $events = array_merge($sh['start'] !== null ? [$sh['start']] : [], $recorded);
        $final = _SwuPgnShadowKeyframeEvent('R99.shadow-end', 'ROUND_END', intval(GetTurnNumber()));
        if ($final !== null) $events[] = $final;
        $headers = ['Game' => 'SWU-PGN/1.0', 'GameId' => 'shadow', 'Date' => '2026-01-01T00:00:00Z', 'CardPool' => 'SOR', 'Engine' => 'petranaki@shadow',
            'Seed' => 'shadow', 'P1Id' => 'sha256:' . str_repeat('1', 64), 'P2Id' => 'sha256:' . str_repeat('2', 64), 'P1' => 'Player 1', 'P2' => 'Player 2',
            'P1Leader' => 'SOR#1', 'P1Base' => 'SOR#19', 'P2Leader' => 'SOR#2', 'P2Base' => 'SOR#20', 'Result' => 'Incomplete', 'Reason' => 'Shadow', 'Rounds' => '1'];
        $doc = SwuPgnParse(SwuPgnWriteText(['headers' => $headers, 'story' => null, 'decks' => [], 'cards' => [], 'setup' => [], 'events' => $recorded]));
        // Record-level errors only: this events-only document has no DECKS / SETUP to validate.
        $recErrors = array_values(array_filter(SwuPgnValidate($doc)['errors'], fn($e) => ($e['seq'] ?? null) !== null));
        $line['validateErrors'] = array_slice(array_map(fn($e) => ($e['seq'] ?? '') . ' ' . ($e['message'] ?? json_encode($e)), $recErrors), 0, 5);
        $doc = SwuPgnParse(SwuPgnWriteText(['headers' => $headers, 'story' => null, 'decks' => [], 'cards' => [], 'setup' => [], 'events' => $events]));
        // The opening keyframe only seeds the fold (compared against an empty board it always differs); every keyframe
        // after it — the round boundaries the game crossed and the live board at the end — is the check.
        $mm = array_values(array_filter(SwuPgnCheckKeyframes($doc['events'])['mismatches'] ?? [], fn($m) => ($m['seq'] ?? '') !== 'R0.S.shadow-start'));
        if (getenv('SWUPGN_SHADOW_DUMP') === '1' && ($mm || $line['validateErrors'])) $line['dump'] = $recorded;   // debugging one test
        // The game's own finish checks files built from a recording that began mid-game (no DECKS, no opening keyframe):
        // they cannot check clean, and each such finish counted a recorder error. Those are not throws.
        $finishChecks = 0;
        foreach (is_file($GLOBALS['SWUPGN_HEALTH_FILE']) ? file($GLOBALS['SWUPGN_HEALTH_FILE'], FILE_IGNORE_NEW_LINES) : [] as $h) {
            $h = json_decode($h, true);
            if (($h['game'] ?? null) === $g && !empty($h['problems'])) $finishChecks++;
        }
        $line += ['events' => count($events), 'recorderErrors' => max(0, intval($meta['recorderErrors'] ?? 0) - $finishChecks), 'finishCheckFailures' => $finishChecks,
            'mismatches' => array_slice($mm, 0, 10), 'mismatchCount' => count($mm),
            'gaps' => intval($state['gaps'] ?? 0), 'gapKinds' => $state['gapKinds'] ?? [], 'gapCards' => $state['gapCards'] ?? []];
    } catch (Throwable $e) {
        $line['error'] = trim(($line['error'] ?? '') . ' end: ' . $e->getMessage());
    }
    $out = getenv('SWUPGN_SHADOW_OUT') ?: sys_get_temp_dir() . '/swupgn-shadow.jsonl';
    file_put_contents($out, json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    if ($g !== '') exec('rm -rf ' . escapeshellarg(SwuPgnRecDir($g)));
    if (is_file($GLOBALS['SWUPGN_HEALTH_FILE'] ?? '')) unlink($GLOBALS['SWUPGN_HEALTH_FILE']);
    unset($GLOBALS['SWUPGN_HEALTH_FILE']);
}
