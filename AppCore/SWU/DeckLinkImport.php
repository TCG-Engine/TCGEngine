<?php
/**
 * Deck-builder link import, shared by SWUDeck (CreateDeck / RefreshImport) and SWUSim (DeckImport).
 *
 * Every JSON source here returns the same standard deck shape:
 *   {"metadata":{"name":..,"author":..}, "leader":{"id":"SEC_010","count":1}, "secondleader":{..}|null,
 *    "base":{"id":..,"count":1}, "deck":[{"id":..,"count":..}], "sideboard":[..]}
 * with SET_NNN ids. melee.gg has no API, so its decklist page is scraped into that shape.
 *
 * Dictionary-agnostic: melee.gg name resolution goes through AppCore/SWU/CardNameMatch.php, which
 * reads the CALLER's already-loaded card dictionary. Never include a GeneratedCardDictionaries.php
 * from here (SWUDeck's and SWUSim's cannot share a process).
 *
 * The source number is persisted as ownership.assetSource and drives SWUDeck's Refresh button, so
 * existing numbers must never be renumbered — only appended.
 */

include_once __DIR__ . '/CardNameMatch.php';

const SWU_DECK_SOURCE_SWUDB         = 0;
const SWU_DECK_SOURCE_SWUSTATS      = 1;
const SWU_DECK_SOURCE_MELEE         = 2;
const SWU_DECK_SOURCE_SWUBASE       = 3;
const SWU_DECK_SOURCE_PROTECTTHEPOD = 4;
const SWU_DECK_SOURCE_SWUCARDHUB    = 5;
const SWU_DECK_SOURCE_SWUFORGE      = 6;
const SWU_DECK_SOURCE_SWUMETASTATS  = 7;
const SWU_DECK_SOURCE_SWUNLIMITEDDB = 8;

// ownership.assetSourceID is varchar(32).
const SWU_DECK_SOURCE_ID_MAX = 32;

/**
 * Source table, keyed by assetSource. Order matters for link matching only when one host is a
 * substring of another (none are today).
 *   host     substring that identifies a link from this source
 *   idRegex  first capture group = the source's deck id
 *   api      URL template; {id} is replaced with the url-encoded deck id
 *   notFound message for a 404 (and any extra status codes in 'status')
 */
function SWUDeckLinkSources() {
    return [
        SWU_DECK_SOURCE_SWUDB => [
            'name' => 'swudb.com', 'host' => 'swudb.com',
            'idRegex' => '#swudb\.com/deck/(?:view/)?([A-Za-z0-9_-]+)#i',
            'api' => 'https://swudb.com/api/getDeckJson/{id}',
            'notFound' => 'Deck not found. Make sure the deck exists and is not Private on swudb.com.',
        ],
        SWU_DECK_SOURCE_SWUSTATS => [
            'name' => 'swustats.net', 'host' => 'swustats.net',
            'idRegex' => '#[?&]gameName=([A-Za-z0-9]+)#',
            'api' => 'https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID={id}&format=json&setId=true',
            'notFound' => 'Deck not found. Make sure the deck exists on swustats.net.',
        ],
        SWU_DECK_SOURCE_MELEE => [
            'name' => 'melee.gg', 'host' => 'melee.gg',
            'idRegex' => '#melee\.gg/Decklist/View/([A-Za-z0-9-]+)#i',
            'api' => 'https://melee.gg/Decklist/View/{id}',
            'html' => true,
            'notFound' => 'Deck not found. Make sure the decklist exists on melee.gg.',
        ],
        SWU_DECK_SOURCE_SWUBASE => [
            'name' => 'swubase.com', 'host' => 'swubase.com',
            'idRegex' => '#swubase\.com/decks/([A-Za-z0-9-]+)#i',
            'api' => 'https://swubase.com/api/deck/{id}/json',
            'notFound' => 'Deck not found. Make sure the deck is set to Public on swubase.com.',
        ],
        SWU_DECK_SOURCE_PROTECTTHEPOD => [
            'name' => 'protectthepod.com', 'host' => 'protectthepod.com',
            'idRegex' => '#protectthepod\.com/pool/([A-Za-z0-9_-]+)#i',
            // The apex host redirects to www.; go straight there.
            'api' => 'https://www.protectthepod.com/api/pools/{id}/deck.json',
            'notFound' => 'Pool not found on protectthepod.com.',
            'status' => [400 => 'No deck has been built for this pool yet. Build a deck on protectthepod.com first, then share the link.'],
        ],
        SWU_DECK_SOURCE_SWUCARDHUB => [
            'name' => 'swucardhub.fr', 'host' => 'swucardhub.fr',
            'idRegex' => '#swucardhub\.fr/(?:Karabast/|deckview\?deckId=)(\d+)#i',
            'api' => 'https://swucardhub.fr/Karabast/{id}',
            'notFound' => 'Deck not found. Make sure it is set to Published on swucardhub.fr.',
        ],
        SWU_DECK_SOURCE_SWUFORGE => [
            'name' => 'swuforge.com', 'host' => 'swuforge.com',
            'idRegex' => '#swuforge\.com/decks/([A-Za-z0-9_-]+)#i',
            'api' => 'https://swuforge.com/api/decks/{id}/json',
            'notFound' => 'Deck not found. Make sure the deck exists on swuforge.com.',
        ],
        SWU_DECK_SOURCE_SWUMETASTATS => [
            'name' => 'swumetastats.com', 'host' => 'swumetastats.com',
            'idRegex' => '#swumetastats\.com/decklists/([A-Za-z0-9_-]+)#i',
            'api' => 'https://www.swumetastats.com/api/decklists/{id}/json',
            'notFound' => 'Decklist not found on swumetastats.com.',
        ],
        SWU_DECK_SOURCE_SWUNLIMITEDDB => [
            'name' => 'sw-unlimited-db.com', 'host' => 'sw-unlimited-db.com',
            'idRegex' => '#sw-unlimited-db\.com/decks/(\d+)#i',
            'api' => 'https://sw-unlimited-db.com/umbraco/api/deckapi/get?id={id}',
            'notFound' => 'Deck not found. Make sure it is set to Published on sw-unlimited-db.com.',
        ],
    ];
}

/**
 * Identify a deck link. Returns ['source' => int, 'id' => string] — id is '' when the host is
 * recognised but no deck id could be read — or null when no source matches.
 */
function SWUDeckLinkParse($link) {
    $link = trim((string)$link);
    if ($link === '') return null;
    foreach (SWUDeckLinkSources() as $source => $cfg) {
        if (stripos($link, $cfg['host']) === false) continue;
        $id = preg_match($cfg['idRegex'], $link, $m) ? $m[1] : '';
        return ['source' => $source, 'id' => $id];
    }
    return null;
}

/**
 * The id to persist in ownership.assetSourceID (varchar 32). UUIDs are stored without dashes
 * (36 → 32 chars) and re-hyphenated on fetch; anything still too long can't be refreshed, so
 * null is returned rather than a truncated id that would fetch the wrong deck.
 */
function SWUDeckLinkStoredID($id) {
    $id = (string)$id;
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
        $id = str_replace('-', '', $id);
    }
    return ($id === '' || strlen($id) > SWU_DECK_SOURCE_ID_MAX) ? null : $id;
}

// Inverse of the dash-stripping above. Some sources (swumetastats) 404 on a dashless UUID.
function _SWUDeckLinkApiID($id) {
    if (preg_match('/^([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})$/i', $id, $m)) {
        return "$m[1]-$m[2]-$m[3]-$m[4]-$m[5]";
    }
    return $id;
}

/**
 * Fetch a deck from any supported link.
 *
 * @return array success bool, message string, source int|null, sourceID string|null (for
 *               ownership.assetSourceID), deck array|null (standard shape), unresolved string[]
 *               (melee.gg card names that matched nothing in the caller's dictionary)
 */
function SWUDeckLinkFetch($link) {
    $parsed = SWUDeckLinkParse($link);
    if ($parsed === null) return _SWUDeckLinkError(null, 'Unsupported deck link.');
    $name = SWUDeckLinkSources()[$parsed['source']]['name'];
    if ($parsed['id'] === '') return _SWUDeckLinkError($parsed['source'], "Could not read a deck id from the $name link.");
    return SWUDeckLinkFetchBySource($parsed['source'], $parsed['id']);
}

/**
 * Fetch by (assetSource, id) — the refresh path. $id may be the stored dashless form.
 */
function SWUDeckLinkFetchBySource($source, $id) {
    $sources = SWUDeckLinkSources();
    $source = intval($source);
    if (!isset($sources[$source])) return _SWUDeckLinkError(null, 'Unknown deck source.');
    $cfg = $sources[$source];
    $name = $cfg['name'];
    $id = (string)$id;
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) return _SWUDeckLinkError($source, "Invalid $name deck id.");

    $url = str_replace('{id}', rawurlencode(_SWUDeckLinkApiID($id)), $cfg['api']);
    [$status, $body, $curlError] = SWUDeckLinkHttpGet($url);

    if ($body === null) return _SWUDeckLinkError($source, "Could not reach $name" . ($curlError !== '' ? ": $curlError" : '.'));
    if (isset($cfg['status'][$status])) return _SWUDeckLinkError($source, $cfg['status'][$status]);
    if ($status === 404) return _SWUDeckLinkError($source, $cfg['notFound']);
    if ($status === 403) return _SWUDeckLinkError($source, "This deck is private on $name. Make it public or unlisted and try again.");
    if ($status < 200 || $status >= 300) return _SWUDeckLinkError($source, "$name returned an error (HTTP $status).");

    $unresolved = [];
    if (!empty($cfg['html'])) {
        $deck = SWUMeleeDeckFromHtml($body, $unresolved);
    } else {
        $deck = json_decode($body, true);
        if (is_string($deck)) $deck = json_decode($deck, true); // some APIs double-encode
        // Tolerate a {"success":..,"data":{deck}} envelope.
        if (is_array($deck) && !isset($deck['leader']) && isset($deck['data']['leader'])) $deck = $deck['data'];
    }
    if (!is_array($deck)) return _SWUDeckLinkError($source, "$name returned a deck we could not read.");
    if (empty($deck['leader']['id']) || empty($deck['base']['id'])) {
        return _SWUDeckLinkError($source, "The $name deck is missing a leader or a base.");
    }
    $deck['deck'] = $deck['deck'] ?? [];
    $deck['sideboard'] = $deck['sideboard'] ?? [];

    return [
        'success' => true, 'message' => '', 'source' => $source,
        'sourceID' => SWUDeckLinkStoredID($id), 'deck' => $deck, 'unresolved' => $unresolved,
    ];
}

function _SWUDeckLinkError($source, $message) {
    return ['success' => false, 'message' => $message, 'source' => $source, 'sourceID' => null, 'deck' => null, 'unresolved' => []];
}

/**
 * GET with one retry on a 5xx or a network failure. Returns [status, body|null, curlError].
 * A browser User-Agent is sent because melee.gg serves nothing useful without one.
 */
function SWUDeckLinkHttpGet($url) {
    $status = 0; $body = null; $err = '';
    for ($attempt = 1; $attempt <= 2; ++$attempt) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json, text/html;q=0.9']);
        $raw = curl_exec($ch);
        $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $err = curl_error($ch);
        curl_close($ch);
        $body = ($raw === false) ? null : (string)$raw;
        if ($body !== null && ($status < 500 || $status >= 600)) break;
    }
    return [$status, $body, $err];
}

/**
 * Scrape a melee.gg decklist page into the standard deck shape, or null when the page has no
 * decklist. Card names are "Title | Subtitle"; unmatched names are appended to $unresolved.
 *
 * Leader and base come from the "Leader (n)" / "Base (n)" categories first — their names carry
 * the subtitle, so the printing is exact — and only fall back to the page title
 * ("<Leader>, <Subtitle> - <Base>") when a category is missing or unresolvable.
 */
function SWUMeleeDeckFromHtml($html, &$unresolved = []) {
    if (!is_string($html) || $html === '') return null;
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $cls = fn($c) => "contains(concat(' ', normalize-space(@class), ' '), ' $c ')";

    $deck = ['metadata' => ['name' => ''], 'deck' => [], 'sideboard' => []];
    $leaders = [];
    $base = null;

    $categories = $xpath->query("//div[" . $cls('decklist-category') . "]");
    if ($categories->length === 0) return null;

    foreach ($categories as $category) {
        $titleNode = $xpath->query(".//div[" . $cls('decklist-category-title') . "]", $category)->item(0);
        $categoryTitle = $titleNode ? trim($titleNode->nodeValue) : '';
        $isLeader = preg_match('/^Leaders?\s*\(\d+\)$/i', $categoryTitle) === 1;
        $isBase = preg_match('/^Bases?\s*\(\d+\)$/i', $categoryTitle) === 1;
        $isSideboard = stripos($categoryTitle, 'Sideboard') !== false;

        foreach ($xpath->query(".//div[" . $cls('decklist-record') . "]", $category) as $record) {
            $nameNode = $xpath->query(".//*[" . $cls('decklist-record-name') . "]", $record)->item(0);
            $qtyNode = $xpath->query(".//*[" . $cls('decklist-record-quantity') . "]", $record)->item(0);
            if (!$nameNode) continue;
            $cardName = trim($nameNode->nodeValue);
            $cardID = SWUFindCardIdByName($cardName);
            if ($cardID === null) {
                $unresolved[] = $cardName;
                error_log("melee.gg import: card not found: $cardName in category: $categoryTitle");
                continue;
            }
            if ($isLeader) { $leaders[] = $cardID; continue; }
            if ($isBase) { if ($base === null) $base = $cardID; continue; }
            $count = $qtyNode ? intval(trim($qtyNode->nodeValue)) : 0;
            if ($count <= 0) continue;
            $deck[$isSideboard ? 'sideboard' : 'deck'][] = ['id' => $cardID, 'count' => $count];
        }
    }

    $titleNode = $xpath->query("//div[" . $cls('decklist-title') . "]")->item(0);
    if ($titleNode) {
        $deck['metadata']['name'] = trim($titleNode->nodeValue);
        $parts = explode(' - ', $deck['metadata']['name']);
        if (count($parts) >= 2) {
            if (empty($leaders)) {
                $id = SWUFindCardIdByName($parts[0]);
                if ($id !== null) $leaders[] = $id;
            }
            if ($base === null) $base = SWUFindCardIdByName($parts[1]);
        }
    }

    if (!empty($leaders)) $deck['leader'] = ['id' => $leaders[0], 'count' => 1];
    if (count($leaders) > 1) $deck['secondleader'] = ['id' => $leaders[1], 'count' => 1];
    if ($base !== null) $deck['base'] = ['id' => $base, 'count' => 1];
    return $deck;
}
