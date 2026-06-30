<?php
// Assemble the SWUDeck/SubmitGameResult payload from per-game telemetry and submit it,
// once, on final match completion.
include_once __DIR__ . '/Match.php';

// Map a SWUSim CardID to the stats/SWUDeck card identifier. PASSTHROUGH for now — swap for a
// SET_NNN -> FFG-UID mapping once the live SWUDeck API field format is confirmed.
function SWUCardToStatsId($cardID) { return strval($cardID); }

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
    foreach (($m['games'] ?? []) as $g) {
        if (($g['winner'] ?? null) === null) continue;
        $payload = SWUBuildGameResultPayload($m, $g);
        $payload['apiKey'] = $apiKey;
        $ch=curl_init('http://localhost/TCGEngine/APIs/SubmitGameResult.php');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>10,
            CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
        curl_exec($ch); curl_close($ch);
    }
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
