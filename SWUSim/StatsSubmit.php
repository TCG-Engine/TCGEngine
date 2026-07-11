<?php
// Assemble the SWUDeck/SubmitGameResult payload from per-game telemetry and submit it,
// once, on final match completion.
include_once __DIR__ . '/Match.php';

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
    foreach ([1,2] as $s) {
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
        'format'=>strval($match['format'] ?? 'premier'),
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

// Submit one result per decided game, ONCE, on final match completion (convert-to-Bo3 re-opens a
// "complete" Bo1, so submission must only fire when the match truly ends — guarded by statsSubmitted).
function SWUSubmitMatchResults($matchId) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || ($m['state'] ?? '')!=='complete' || !empty($m['statsSubmitted'])) return;
    SWUWithMatchLock($matchId, function(&$mm){ $mm['statsSubmitted']=true; });
    $m = SWUReadMatch($matchId);
    $apiKey = $GLOBALS['petranakiAPIKey'] ?? ($GLOBALS['karabastAPIKey'] ?? '');
    // Post to the SWUStats stats site. In prod that's swustats.net; locally it's the SWUDeck
    // container the user reaches at localhost:3100 — but this curl runs INSIDE the game container, where
    // "localhost" is that container, so use the Docker host gateway (host.docker.internal:3100) to hit
    // the host's :3100 mapping. DEVENV is set only in the local docker-compose override.
    $statsBase = (getenv('DEVENV') === 'true') ? 'http://host.docker.internal:3100' : 'https://swustats.net';
    $statsUrl = $statsBase . '/TCGEngine/APIs/SubmitGameResult.php';
    $attempted = 0; $succeeded = 0; $failed = 0;
    foreach (($m['games'] ?? []) as $g) {
        if (($g['winner'] ?? null) === null) continue;
        // Don't record a game that ended before Round 2 (an early concede/abandon). GetTurnNumber is
        // the round counter; a game conceded during Round 1 has turns < 2 and must not pollute stats.
        if (intval($g['detail']['turns'] ?? 0) < 2) continue;
        $attempted++;
        $payload = SWUBuildGameResultPayload($m, $g);
        $payload['apiKey'] = $apiKey;
        $ch=curl_init($statsUrl);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>10,
            CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
        $resp = curl_exec($ch); $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)); curl_close($ch);
        $ok = ($resp !== false && $code >= 200 && $code < 300);
        if ($ok) { $j = json_decode($resp, true); if (is_array($j) && array_key_exists('success', $j) && $j['success'] === false) $ok = false; }
        $ok ? $succeeded++ : $failed++;
    }
    // Outcome for the end-game "sent to SWUStats" banner: skipped_early = every decided game ended
    // before Round 2 (nothing submitted); failed = at least one POST failed; success otherwise.
    $status = ($attempted === 0) ? 'skipped_early' : (($failed > 0) ? 'failed' : 'success');
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
    // Only show the viewing player's own stats; spectators (no seat) see both.
    $vs = ($viewerSeat === '1' || $viewerSeat === 1) ? 1
        : (($viewerSeat === '2' || $viewerSeat === 2) ? 2 : 0);
    $seats = $vs ? [strval($vs)] : ['1','2'];
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
