<?php
// Every melee.gg request the tournament importer makes goes through SWUMeleeFetch().
// melee's load balancer answers 403 to the DigitalOcean droplets (2026-10-22), so a moderator records a
// tournament on a machine melee does answer (the swustats-melee-package skill, DevTools/melee-package.php)
// and swustats.net replays that recording (APIs/ImportMeleeTournamentPackage.php).
//   live   (default) — run $live, exactly as before
//   record — run $live and keep its answer
//   replay — answer from the loaded package; $live never runs, and a request the package lacks throws
// $live returns ['status' => int, 'body' => string|false, 'error' => ?string]; so does SWUMeleeFetch().

function _SWUMeleeFetchState(): array {
    if (!isset($GLOBALS['SWU_MELEE_FETCH'])) $GLOBALS['SWU_MELEE_FETCH'] = ['mode' => 'live', 'responses' => []];
    return $GLOBALS['SWU_MELEE_FETCH'];
}

function _SWUMeleeFetchKey(string $method, string $url, ?string $body): string {
    return strtoupper($method) . ' ' . $url . ' ' . sha1((string)$body);
}

function SWUMeleeFetchLive(): void {
    $GLOBALS['SWU_MELEE_FETCH'] = ['mode' => 'live', 'responses' => []];
}

function SWUMeleeFetchRecord(): void {
    $GLOBALS['SWU_MELEE_FETCH'] = ['mode' => 'record', 'responses' => []];
}

// $entries is a package's `responses` list. A later entry for the same request wins, matching record.
function SWUMeleeFetchReplay(array $entries): void {
    $responses = [];
    foreach ($entries as $e) {
        if (!is_array($e) || !isset($e['method'], $e['url'])) continue;
        $responses[_SWUMeleeFetchKey($e['method'], $e['url'], $e['body'] ?? null)] = $e;
    }
    $GLOBALS['SWU_MELEE_FETCH'] = ['mode' => 'replay', 'responses' => $responses];
}

function SWUMeleeFetchMode(): string {
    return _SWUMeleeFetchState()['mode'];
}

// The recorded answers, in first-request order, ready for a package's `responses`.
function SWUMeleeFetchRecorded(): array {
    $state = _SWUMeleeFetchState();
    return $state['mode'] === 'record' ? array_values($state['responses']) : [];
}

function SWUMeleeFetch(string $method, string $url, ?string $body, callable $live): array {
    $state = _SWUMeleeFetchState();
    $key = _SWUMeleeFetchKey($method, $url, $body);
    if ($state['mode'] === 'replay') {
        if (!isset($state['responses'][$key])) {
            throw new RuntimeException("The tournament package has no recorded answer for $method $url");
        }
        $e = $state['responses'][$key];
        $payload = $e['payload'] ?? null;
        return ['status' => (int)($e['status'] ?? 0), 'body' => is_string($payload) ? $payload : false, 'error' => $e['error'] ?? null];
    }
    $result = $live();
    $result = [
        'status' => (int)($result['status'] ?? 0),
        'body' => (isset($result['body']) && is_string($result['body'])) ? $result['body'] : false,
        'error' => $result['error'] ?? null,
    ];
    if ($state['mode'] === 'record') {
        $GLOBALS['SWU_MELEE_FETCH']['responses'][$key] = [
            'method' => strtoupper($method), 'url' => $url, 'body' => $body,
            'status' => $result['status'], 'error' => $result['error'],
            'payload' => $result['body'] === false ? null : $result['body'],
        ];
    }
    return $result;
}
