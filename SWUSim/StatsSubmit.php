<?php
// Assemble the SWUDeck/SubmitGameResult payload from per-game telemetry and submit it,
// once, on final match completion.
include_once __DIR__ . '/Match.php';
include_once __DIR__ . '/SWUStatsLink.php';   // owner tokens (spec 2026-10-10 §3)

// Map a SWUSim CardID (SET_NNN) to the stats/SWUDeck card identifier (the FFG UID / documentId that
// the SWUDeck stats tables are keyed on). GetCardUUID reads $cardUUIDData from the generated dict
// (loaded in the game runtime alongside this file). Falls back to the raw CardID for a token/unknown
// so we never drop a value; empty input stays empty.
function SWUCardToStatsId($cardID) {
    $cid = strval($cardID);
    if ($cid === '') return '';
    if (function_exists('GetCardUUID')) {
        $uuid = GetCardUUID($cid);
        if ($uuid !== null && strval($uuid) !== '') return strval($uuid);
    }
    return $cid;
}

// Snapshot the just-finished game's gamestate (called from the after-action hook, where the
// gamestate is loaded and current).
function SWUCaptureCurrentGameDetail() {
    $detail = ['firstPlayer'=>0,'turns'=>0,'leader'=>['1'=>'','2'=>''],'base'=>['1'=>'','2'=>''],
               'baseHpLeft'=>['1'=>0,'2'=>0],'telemetry'=>['cards'=>[],'turns'=>[]]];
    if (function_exists('GetFirstPlayer')) { $fp=&GetFirstPlayer(); $detail['firstPlayer']=intval($fp); }
    if (function_exists('GetTurnNumber'))  { $tn=&GetTurnNumber();  $detail['turns']=intval($tn); }
    // Every seat in the game, not just 1 and 2 — Twin Suns runs four, and seats 3/4 were coming out
    // of the end-game panel with no leader/base/HP at all. Seats 1 and 2 are always present, so the
    // 2-player shape (and the SWUStats payload built from it) is byte-identical to before.
    $seats = function_exists('GetSeatOrderArray') ? GetSeatOrderArray() : [1, 2];
    foreach ($seats as $s) {
        if (function_exists('GetLeader')) { $l=GetLeader($s); $detail['leader'][strval($s)] = !empty($l)?strval($l[0]->CardID ?? ''):''; }
        if (function_exists('GetBase')) {
            $b=GetBase($s);
            if (!empty($b)) {
                $bid=strval($b[0]->CardID ?? ''); $detail['base'][strval($s)]=$bid;
                $hp=function_exists('CardHp')?intval(CardHp($bid)):0;
                $detail['baseHpLeft'][strval($s)] = max(0, $hp - intval($b[0]->Damage ?? 0));
            }
        }
    }
    if (function_exists('SWUTelemetryGet')) {
        $t=SWUTelemetryGet();
        $detail['telemetry']=['cards'=>$t['cards'] ?? [],'turns'=>$t['turns'] ?? []];
    }
    // Meta Premier (docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §4.2-4.3). Additive keys —
    // SWUBuildGameResultPayload reads named keys only, so the swustats.net payload is unchanged.
    if (class_exists('DecisionQueueController')) {
        $detail['pregameDone'] = function_exists('SWUPregameDone') ? SWUPregameDone() : true;
        $reason = DecisionQueueController::GetVariable('GAMEOVER_REASON');
        $detail['endReason'] = in_array($reason, ['concede', 'abandon'], true) ? $reason : 'win';
    }
    return $detail;
}

// Build the exact SubmitGameResult payload for one game record (with detail attached).
function SWUBuildGameResultPayload($match, $game) {
    $d = $game['detail'] ?? [];
    $winner = intval($game['winner'] ?? 0);
    $loser  = ($winner===1)?2:(($winner===2)?1:0);
    $tel = $d['telemetry'] ?? ['cards'=>[],'turns'=>[]];
    $buildPlayer = function($seat) use ($d, $tel) {
        $s=strval($seat); $opp=strval(($seat===1)?2:1);
        $cardResults=[];
        foreach (($tel['cards'][$s] ?? []) as $cid=>$c) {
            $cardResults[]=['cardId'=>SWUCardToStatsId($cid),'played'=>intval($c['played']??0),
                'resourced'=>intval($c['resourced']??0),'activated'=>intval($c['activated']??0),
                'drawn'=>intval($c['drawn']??0),'discarded'=>intval($c['discarded']??0)];
        }
        $turnResults=[];
        foreach (($tel['turns'] ?? []) as $tr) {
            if (intval($tr['seat']??0)!==$seat) continue;
            $turnResults[]=['cardsUsed'=>intval($tr['cardsUsed']??0),'resourcesUsed'=>intval($tr['resourcesUsed']??0),
                'resourcesLeft'=>intval($tr['resourcesLeft']??0),'cardsLeft'=>intval($tr['cardsLeft']??0),
                'damageDealt'=>intval($tr['damageDealt']??0),'damageTaken'=>intval($tr['damageTaken']??0),
                'restored'=>intval($tr['restored']??0)]; // restored = EXTRA (not in SWUDeck contract)
        }
        return ['leader'=>SWUCardToStatsId($d['leader'][$s] ?? ''),'base'=>SWUCardToStatsId($d['base'][$s] ?? ''),
                'opposingHero'=>SWUCardToStatsId($d['leader'][$opp] ?? ''),'cardResults'=>$cardResults,'turnResults'=>$turnResults];
    };
    return [
        'winner'=>$winner, 'firstPlayer'=>intval($d['firstPlayer'] ?? 0),
        'winHero'=>SWUCardToStatsId($d['leader'][strval($winner)] ?? ''),
        'loseHero'=>SWUCardToStatsId($d['leader'][strval($loser)] ?? ''),
        'round'=>intval($d['turns'] ?? 0),
        'winnerHealth'=>intval($d['baseHpLeft'][strval($winner)] ?? 0),
        // A rated Meta Premier game is a Premier game to swustats.net (external contract — SWUStatsFormatFor).
        'format'=>SWUStatsFormatFor(strval($match['format'] ?? 'premier')),
        'gameName'=>strval($game['gameName'] ?? ''),
        'sequenceNumber'=>intval($game['gameNumber'] ?? 1),
        'player1'=>json_encode($buildPlayer(1)),
        'player2'=>json_encode($buildPlayer(2)),
        // Source deck links (from match creation) — SubmitGameResult uses these to record deck-level
        // stats (SaveDeckStats). Empty for a non-swustats/SWUDeck source, which it skips.
        'p1DeckLink'=>strval($match['players']['1']['deckLink'] ?? ''),
        'p2DeckLink'=>strval($match['players']['2']['deckLink'] ?? ''),
    ];
}

// Owner credit (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §3): a seat whose
// Petranaki account is linked to SWUStats sends its token. SubmitGameResult credits the deck's OWNER only
// when the token's user owns the deck in pXDeckLink; any other deck still lands in community stats.
// A seat whose token cannot be made valid is left out rather than sent a stale one — a bad token
// rejects the whole game. Returns the seats that got a token.
function SWUAttachOwnerTokens(array &$payload, array $match): array {
    $seats = [];
    foreach (['1', '2'] as $s) {
        $uid = intval($match['players'][$s]['userId'] ?? 0);
        if ($uid <= 0) continue;
        // This runs inside the player's last game action: a DB or refresh failure costs this seat its owner
        // credit, never the action request.
        try { $t = SWUStatsAccessToken($uid); }
        catch (Throwable $e) { error_log('SWU stats submit: owner token lookup failed seat=' . $s . ': ' . $e->getMessage()); continue; }
        if ($t['status'] === 'ok' && $t['token']) {
            $payload['p' . $s . 'SWUStatsToken'] = $t['token'];
            $seats[] = $s;
        }
    }
    return $seats;
}

// One SubmitGameResult POST. ok = 2xx and not {"success": false}.
function SWUPostGameResult(string $url, array $payload): array {
    $r = SWUStatsHttp('POST', $url, json_encode($payload), ['Content-Type: application/json'], 10);
    $ok = $r['status'] >= 200 && $r['status'] < 300;
    if ($ok && is_array($r['body']) && array_key_exists('success', $r['body']) && $r['body']['success'] === false) $ok = false;
    return ['ok' => $ok, 'code' => (int)$r['status'], 'raw' => (string)($r['raw'] ?? ''), 'error' => (string)($r['error'] ?? '')];
}

// Submit one decided game: 'ok' | 'ok_without_owner' | 'failed'. SubmitGameResult answers 401 for the
// WHOLE game when any token is bad, so a rejected owner token is retried once without tokens — the game
// still reaches community stats.
function SWUSubmitOneGame(array $m, array $g, string $statsUrl, string $apiKey): string {
    $payload = SWUBuildGameResultPayload($m, $g);
    $payload['apiKey'] = $apiKey;
    $owned = SWUAttachOwnerTokens($payload, $m);
    $r = SWUPostGameResult($statsUrl, $payload);
    if (!$r['ok'] && $r['code'] === 401 && $owned) {
        error_log('SWU stats submit: owner token rejected game=' . strval($g['gameName'] ?? '?')
            . ' seats=' . implode(',', $owned) . ' — retrying without tokens');
        foreach ($owned as $s) unset($payload['p' . $s . 'SWUStatsToken']);
        $r = SWUPostGameResult($statsUrl, $payload);
        if ($r['ok']) return 'ok_without_owner';
    }
    // Log WHY on failure. Without this the only trace of a rejected submission is statsStatus
    // ='failed' on the match — no status code, no error body — so "stats aren't publishing"
    // arrives with nothing to diagnose from and every theory has to be tested against prod.
    // The response body carries the endpoint's own reason (bad identifier, maintenance 503,
    // rejected key), which is exactly what distinguishes those cases. apiKey is NOT logged.
    if (!$r['ok']) {
        error_log('SWU stats submit FAILED game=' . strval($g['gameName'] ?? '?')
            . ' http=' . $r['code']
            . ($r['error'] !== '' ? ' curl=' . $r['error'] : '')
            . ' format=' . strval($payload['format'] ?? '?')
            . ' resp=' . substr($r['raw'], 0, 400));
    }
    return $r['ok'] ? 'ok' : 'failed';
}

// Outcome for the end-game "sent to SWUStats" banner.
function SWUStatsSubmitStatus(int $attempted, int $failed, int $withoutOwner): string {
    if ($attempted === 0) return 'skipped_early';   // every decided game ended before Round 2
    if ($failed > 0) return 'failed';
    return $withoutOwner > 0 ? 'submitted_without_owner' : 'success';
}

// Submit one result per decided game, ONCE, on final match completion (convert-to-Bo3 re-opens a
// "complete" Bo1, so submission must only fire when the match truly ends — guarded by statsSubmitted).
function SWUSubmitMatchResults($matchId) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || ($m['state'] ?? '')!=='complete' || !empty($m['statsSubmitted'])) return;
    // SWUStats' SubmitGameResult is a strictly 2-player contract (one winner, one opponent deck,
    // pairwise matchup rows) and is consumed externally, so a 4-seat Twin Suns result has nowhere
    // to go — it would land as a bogus 1v1. Skip submission entirely, matching the same guard in
    // SWURecordDeckStatsForGame. (This became reachable only once Twin Suns matches started
    // completing at all; before the winner-storage fix they never reached state=complete.)
    if (count($m['players'] ?? []) > 2) {
        SWUWithMatchLock($matchId, function(&$mm){ $mm['statsSubmitted']=true; $mm['statsStatus']='skipped_multiplayer'; });
        return;
    }
    SWUWithMatchLock($matchId, function(&$mm){ $mm['statsSubmitted']=true; });
    $m = SWUReadMatch($matchId);
    $apiKey = $GLOBALS['petranakiAPIKey'] ?? ($GLOBALS['karabastAPIKey'] ?? '');
    // Post to the SWUStats stats site. In prod that's swustats.net; locally it's the SWUDeck
    // container the user reaches at localhost:3100 — but this curl runs INSIDE the game container, where
    // "localhost" is that container, so use the Docker host gateway (host.docker.internal:3100) to hit
    // the host's :3100 mapping. DEVENV is set only in the local docker-compose override.
    $statsBase = (getenv('DEVENV') === 'true') ? 'http://host.docker.internal:3100' : 'https://swustats.net';
    $statsUrl = $statsBase . '/TCGEngine/APIs/SubmitGameResult.php';
    $attempted = 0; $failed = 0; $withoutOwner = 0;
    foreach (($m['games'] ?? []) as $g) {
        if (($g['winner'] ?? null) === null) continue;
        // Don't record a game that ended before Round 2 (an early concede/abandon). GetTurnNumber is
        // the round counter; a game conceded during Round 1 has turns < 2 and must not pollute stats.
        if (intval($g['detail']['turns'] ?? 0) < 2) continue;
        $attempted++;
        $r = SWUSubmitOneGame($m, $g, $statsUrl, $apiKey);
        if ($r === 'failed') $failed++;
        elseif ($r === 'ok_without_owner') $withoutOwner++;
    }
    $status = SWUStatsSubmitStatus($attempted, $failed, $withoutOwner);
    SWUWithMatchLock($matchId, function(&$mm) use ($status) { $mm['statsStatus'] = $status; });
}

// Render a finished game's telemetry as a compact HTML block for the end-game menu.
function SWUBuildStatsHtml($match, $game, $viewerSeat = null) {
    $esc = fn($s) => htmlspecialchars(strval($s), ENT_QUOTES);
    // "Card Title - Subtitle" for a card id (falls back to the raw id).
    $cardLabel = function($cid) use ($esc) {
        $title = function_exists('CardTitle') ? CardTitle($cid) : '';
        if ($title === '' || $title === null) return $esc($cid);
        $sub = function_exists('CardSubtitle') ? CardSubtitle($cid) : '';
        return $esc($sub !== '' && $sub !== null ? "$title - $sub" : $title);
    };
    $tel = $game['detail']['telemetry'] ?? ['cards'=>[], 'turns'=>[]];
    // Only show the viewing player's own stats; spectators (no seat) see every seat.
    // Any seat is a viewer — the old 1|2 test made Twin Suns seats 3 and 4 fall through to the
    // spectator branch, so they were shown P1's and P2's card tables instead of their own.
    $vs = max(0, intval($viewerSeat));
    $seats = $vs ? [strval($vs)] : array_map('strval', array_keys($tel['cards'] ?? []));
    if (!$vs && empty($seats)) $seats = ['1','2'];
    // Bordered, sectioned-off tables.
    $tableCss = 'width:100%;border-collapse:collapse;font-size:13px;border:1px solid #5a6b7a;margin-bottom:6px;';
    $thC = 'color:#f0e6c8;border:1px solid #5a6b7a;padding:3px 6px;';
    $thL = 'text-align:left;color:#f0e6c8;border:1px solid #5a6b7a;padding:3px 6px;';
    $tdC = 'text-align:center;color:#f0e6c8;border:1px solid #5a6b7a;padding:3px 6px;';
    $tdL = 'text-align:left;color:#f0e6c8;border:1px solid #5a6b7a;padding:3px 6px;';
    $h = '';
    if (intval($match['bestOf'] ?? 1) > 1) {
        $h .= '<div style="font-weight:bold;margin-bottom:8px;">Match score: '
            . intval($match['wins']['1'] ?? 0) . ' – ' . intval($match['wins']['2'] ?? 0)
            . ' (game ' . intval($game['gameNumber'] ?? 1) . ')</div>';
    }
    foreach ($seats as $seat) {
        $cards = $tel['cards'][$seat] ?? [];
        if (empty($cards)) continue;
        $label = ($vs ? 'Your' : 'Player ' . $seat) . ' cards';
        $h .= '<div style="margin-top:10px;font-weight:bold;">' . $label . '</div>';
        $h .= '<table style="' . $tableCss . '"><tr>'
            . '<th style="' . $thL . '">Card</th><th style="' . $thC . '">Played</th><th style="' . $thC . '">Drawn</th><th style="' . $thC . '">Resourced</th><th style="' . $thC . '">Discarded</th><th style="' . $thC . '">Activated</th></tr>';
        foreach ($cards as $cid => $c) {
            $h .= '<tr><td style="' . $tdL . '">' . $cardLabel($cid) . '</td>'
                . '<td style="' . $tdC . '">' . intval($c['played'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($c['drawn'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($c['resourced'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($c['discarded'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($c['activated'] ?? 0) . '</td></tr>';
        }
        $h .= '</table>';
    }
    if (!empty($tel['turns'])) {
        $h .= '<div style="margin-top:10px;font-weight:bold;">Per-round</div>';
        $h .= '<table style="' . $tableCss . '"><tr>'
            . '<th style="' . $thC . '">Cards</th><th style="' . $thC . '">Res used</th><th style="' . $thC . '">Res left</th><th style="' . $thC . '">Hand</th><th style="' . $thC . '">Dmg dealt</th><th style="' . $thC . '">Dmg taken</th><th style="' . $thC . '">Healed</th></tr>';
        foreach ($tel['turns'] as $t) {
            if ($vs && intval($t['seat'] ?? 0) !== $vs) continue;
            $h .= '<tr><td style="' . $tdC . '">' . intval($t['cardsUsed'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($t['resourcesUsed'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($t['resourcesLeft'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($t['cardsLeft'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($t['damageDealt'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($t['damageTaken'] ?? 0) . '</td>'
                . '<td style="' . $tdC . '">' . intval($t['restored'] ?? 0) . '</td></tr>';
        }
        $h .= '</table>';
    }
    return $h;
}
