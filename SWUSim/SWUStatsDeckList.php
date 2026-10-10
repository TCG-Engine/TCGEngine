<?php
// The linked player's hearted SWUStats decks, shaped exactly like SWUSetupSavedDecks() rows so a picker
// can render either list (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2).
// Callers must load the card dictionaries at GLOBAL scope first (SWUStatsCardToSimId reads $cardUUIDData).
require_once __DIR__ . '/SWUStatsLink.php';

const SWUSTATS_DECKS_PAGE = 100;
const SWUSTATS_DECKS_MAX_PAGES = 20;
const SWUSTATS_DECKS_TTL = 60;

// SWUStats rows keep a card's FFG UID until that deck is re-saved; art and labels here are SET_NNN.
function SWUStatsCardToSimId(string $id): string {
    $id = trim($id);
    if ($id === '') return '';
    if (function_exists('IsSWUCardID') && IsSWUCardID($id)) return $id;
    static $byUuid = null;
    if ($byUuid === null) $byUuid = array_flip(array_map('strval', (array)($GLOBALS['cardUUIDData'] ?? [])));
    return (string)($byUuid[$id] ?? '');
}

// "Hearted" is a flag on the deck row, so favorites=true also returns team decks THEIR owner hearted.
// Only the player's own decks are "my hearted decks".
function SWUStatsDeckToPickerRow(array $row): ?array {
    $id = (int)($row['id'] ?? 0);
    if ($id <= 0 || empty($row['is_owner'])) return null;
    $leader = SWUStatsCardToSimId((string)($row['keyIndicator1'] ?? ''));
    $base   = SWUStatsCardToSimId((string)($row['keyIndicator2'] ?? ''));
    $label = function ($cid) { return function_exists('SWUSetupCardLabel') ? SWUSetupCardLabel($cid) : $cid; };
    $name = trim((string)($row['name'] ?? ''));
    if ($name === '') $name = $leader !== '' ? $label($leader) : 'Untitled deck';
    return [
        'key'      => 'ss' . $id,             // 'ss' cannot collide with a saved deck's sha1 hex key
        'name'     => $name,
        'leaders'  => $leader !== '' ? [$leader] : [],
        'base'     => $base,
        'count'    => 0,
        // The client builder shows this line, else raw card ids; the server picker writes the same labels.
        'subtitle' => implode(' · ', array_map($label, array_values(array_filter([$leader, $base])))),
        // A gameName= link: DeckImport resolves it, and SubmitGameResult reads gameName= for owner stats.
        'input'   => SWUStatsDeckLinkBase() . '/TCGEngine/NextTurn.php?gameName=' . $id . '&folderPath=SWUDeck',
    ];
}

// ['status' => ok|unlinked|relink|unavailable, 'decks' => rows]. SWUStats can drop a token before its
// expiry, so a 401 forces ONE refresh and retries the same page; a second 401 means reconnect.
function SWUStatsHeartedDecks(int $usersId, bool $refresh = false): array {
    if ($usersId <= 0) return ['status' => 'unlinked', 'decks' => []];
    $cacheKey = 'swustats_decks_' . $usersId;
    $apcu = function_exists('apcu_enabled') && apcu_enabled();
    if ($apcu && !$refresh) {
        $hit = apcu_fetch($cacheKey, $found);
        if ($found && is_array($hit)) return $hit;
    }
    $tok = SWUStatsAccessToken($usersId);
    if ($tok['status'] !== 'ok') return ['status' => $tok['status'], 'decks' => []];

    $decks = [];
    $retried = false;
    $offset = 0;
    for ($page = 0; $page < SWUSTATS_DECKS_MAX_PAGES; $page++) {
        $url = SWUStatsServerBase() . '/TCGEngine/APIs/UserAPIs/GetUserDecks.php?' . http_build_query([
            'favorites' => 'true', 'sort' => 'name', 'limit' => SWUSTATS_DECKS_PAGE, 'offset' => $offset]);
        $r = SWUStatsHttp('GET', $url, null, ['Authorization: Bearer ' . $tok['token']]);
        if ($r['status'] === 401 && !$retried) {
            $retried = true;
            $tok = SWUStatsAccessToken($usersId, true);
            if ($tok['status'] !== 'ok') return ['status' => $tok['status'], 'decks' => []];
            continue;   // same $offset: the retry re-fetches this page (it spends one of the page budget — harmless)
        }
        if ($r['status'] === 401) return ['status' => 'relink', 'decks' => []];
        if ($r['status'] !== 200 || !is_array($r['body']['decks'] ?? null)) return ['status' => 'unavailable', 'decks' => []];
        foreach ($r['body']['decks'] as $row) {
            $d = SWUStatsDeckToPickerRow((array)$row);
            if ($d) $decks[] = $d;
        }
        if (empty($r['body']['pagination']['has_more'])) break;
        $offset += SWUSTATS_DECKS_PAGE;
    }
    $out = ['status' => 'ok', 'decks' => $decks];
    if ($apcu) apcu_store($cacheKey, $out, SWUSTATS_DECKS_TTL);
    return $out;
}
