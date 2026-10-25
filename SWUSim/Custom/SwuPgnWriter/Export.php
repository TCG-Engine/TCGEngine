<?php
// "Export SWUPGN": a seat's own-view file of a finished game (out/p<seat>.swupgn). The seat is the one the caller's
// seat key authenticated (SwuPgnExport.php); nothing here ever hands out out/all.swupgn.

function SwuPgnExportFilename(array $doc, int $seat): string {
    $h = $doc['headers'] ?? [];
    $slug = function (string $ref): string {
        $cardID = SwuPgnMapCardId($ref);
        $title = $cardID !== null ? strval(CardTitle($cardID) ?? '') : '';
        $s = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title !== '' ? $title : $ref)), '-');
        return $s !== '' ? $s : 'leader';
    };
    $mine = $slug(strval($h["P{$seat}Leader"] ?? ''));
    $theirs = $slug(strval($h['P' . ($seat === 1 ? 2 : 1) . 'Leader'] ?? ''));
    $date = preg_match('/^\d{4}-\d\d-\d\d/', strval($h['Date'] ?? ''), $m) ? $m[0] : gmdate('Y-m-d');
    return "petranaki_{$mine}_vs_{$theirs}_{$date}.swupgn";
}

// ['ok' => true, 'filename', 'pgn', 'summary'] or ['ok' => false, 'status', 'message'].
function SwuPgnExportFor(string $g, int $seat): array {
    $unavailable = ['ok' => false, 'status' => 404, 'message' => 'Replay file unavailable.'];
    if (($seat !== 1 && $seat !== 2) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $g)) return $unavailable;
    $meta = SwuPgnMetaRead($g);
    if ($meta === null || empty($meta['finished'])) return $unavailable;
    $path = SwuPgnRecDir($g) . "/out/p$seat.swupgn";
    clearstatcache(true, $path);
    if (!is_file($path)) return $unavailable;
    $text = (string)file_get_contents($path);
    $parsed = SwuPgnTryParse($text);
    if ($parsed['doc'] === null) return $unavailable;
    return ['ok' => true, 'filename' => SwuPgnExportFilename($parsed['doc'], $seat), 'pgn' => $text,
        'summary' => _SwuPgnViewerSummary($parsed['doc'])];
}

// The endpoint's whole decision (SwuPgnExport.php): the seat is the one the seat key authenticates for the requested
// playerID — a spectator, a seat that is not 1/2, or a key that does not open it gets 403.
function SwuPgnExportRequest(array $get, array $cookie = []): array {
    $gameName = strval($get['gameName'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $gameName)) return ['ok' => false, 'status' => 400, 'message' => 'Invalid game.'];
    $viewerInfo = NormalizeViewerIdentity(strval($get['playerID'] ?? ''), 2);
    $authKey = trim(strval($get['authKey'] ?? ''));
    if (!$viewerInfo['isSpectator'] && $authKey === '' && isset($cookie['lastAuthKey'])) $authKey = trim(strval($cookie['lastAuthKey']));
    $forbidden = ['ok' => false, 'status' => 403, 'message' => 'Only a player of this game can export their replay.'];
    $seat = intval($viewerInfo['viewerSeat'] ?? 0);
    if (!empty($viewerInfo['isSpectator']) || ($seat !== 1 && $seat !== 2)) return $forbidden;
    if (!SimGameValidateViewerAuth('SWUSim', $gameName, $viewerInfo, $authKey)) return $forbidden;
    return SwuPgnExportFor($gameName, $seat);
}
