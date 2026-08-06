<?php
// Loads AppCore/SWU/CardMocks.php and merges those entries into a generator card array.
// Shared by zzCardCodeGenerator.php (objects) and Data/ProcessKeywordsSWU.php (arrays) so the
// merge rule — official data always wins — lives in exactly one place.
//
// Lives in AppCore/SWU/ (not inside one app) because BOTH SWUSim and SWUDeck need the same preview
// cards: SWUDeck's dictionary has to carry them for preview decks to be searchable and buildable.

function SWUMockCardsPath(): string {
    return __DIR__ . '/CardMocks.php';
}

// Sets whose card numbers are TWO digits ("TS26_09") rather than three ("SOR_014"). Canonical copy
// for the mock pipeline; keep in sync with zzCardCodeGenerator.php's CardIDDoubleDigitSets and
// SWUDeck/Custom/CardIdentifiers.php's $doubleDigitsSets.
function SWUDoubleDigitSets(): array {
    return ['TS26'];
}

// A CardID this pipeline accepts: SET_NNN (SET_NN for double-digit sets), or a SET_T## token id.
// Set-aware on purpose — a flat 3-digit rule rejects every legitimate TS26 card.
function SWUIsMockCardID(string $cardID): bool {
    if (!preg_match('/^([A-Z0-9]{2,5})_(T\d{2}|\d{2,3})$/', $cardID, $m)) return false;
    if (strpos($m[2], 'T') === 0) return true;                       // token ids are always T##
    $expected = in_array(strtoupper($m[1]), SWUDoubleDigitSets(), true) ? 2 : 3;
    return strlen($m[2]) === $expected;
}

// SET_NNN => flat mock definition. Missing/unreadable file is not an error: no mocks.
function SWULoadMockCards(string $path = ''): array {
    if ($path === '') $path = SWUMockCardsPath();
    if (!file_exists($path)) return [];
    $data = require $path;
    return is_array($data) ? $data : [];
}

// Wrap scalars as the {name: ...} relation shape GetPropertyValue()/SWURelAttrList() expect.
function _SWURelationList($value): array {
    if ($value === null || $value === '') return [];
    if (!is_array($value)) $value = array_map('trim', explode(',', (string)$value));
    $out = [];
    foreach ($value as $v) {
        if (trim((string)$v) === '') continue;
        $out[] = ['name' => trim((string)$v)];
    }
    return $out;
}

// Strapi v4 NESTED twin of _SWURelationList. SWUSim reads relations through SWURelAttrList (shape
// tolerant); SWUDeck's GetPropertyValue reads $card->arenas->data[$j]->attributes->name DIRECTLY,
// so a mock row must carry this shape too or the SWUDeck generator fatals on count(null).
function _SWUNestedList($value): array {
    $out = [];
    foreach (_SWURelationList($value) as $item) {
        $out[] = ['attributes' => ['name' => $item['name']]];
    }
    return ['data' => $out];
}

// SWUDeck stores rarity as a single character ('C','U','R','L','S'); SWUSim stores the full word.
// Verified against both live dictionaries 2026-08-04.
function _SWURarityCharacter(string $rarityName): string {
    $map = ['Common' => 'C', 'Uncommon' => 'U', 'Rare' => 'R', 'Legendary' => 'L', 'Special' => 'S'];
    return $map[$rarityName] ?? '';
}

// Flat mock -> the API-shaped row the generator's GetPropertyValue() reads.
//
// The row carries BOTH Strapi shapes: SWUSim reads the flat v5 keys via SWURelAttr/SWURelAttrList,
// SWUDeck reads the nested v4 twins directly. Dropping either breaks one app — and a missing
// aspectDuplicates is a PHP 8 TypeError in SWUDeck, not merely a blank field.
function SWUMockToImportRow(string $cardID, array $m): array {
    $numPart = substr($cardID, strpos($cardID, '_') + 1);
    $rarityName = (string)($m['rarity'] ?? '');
    $setCode    = (string)($m['set'] ?? explode('_', $cardID)[0]);
    return [
        'id'           => $cardID,
        'title'        => (string)($m['title'] ?? ''),
        'subtitle'     => (string)($m['subtitle'] ?? ''),
        // Dual-shaped: flat v5 keys for SWUSim, nested v4 twins for SWUDeck.
        'type'         => ['name' => (string)($m['type'] ?? ''),
                           'data' => ['attributes' => ['name' => (string)($m['type'] ?? '')]]],
        'arenas'       => array_merge(_SWURelationList($m['arena'] ?? ''),  _SWUNestedList($m['arena'] ?? '')),
        'traits'       => array_merge(_SWURelationList($m['trait'] ?? ''),  _SWUNestedList($m['trait'] ?? '')),
        'aspects'      => array_merge(_SWURelationList($m['aspect'] ?? ''), _SWUNestedList($m['aspect'] ?? '')),
        // SWUDeck's aspect branch also iterates aspectDuplicates — count(null) is a PHP 8 TypeError,
        // so this must EXIST even when empty.
        'aspectDuplicates' => ['data' => []],
        'rarity'       => ['name' => $rarityName,
                           'data' => ['attributes' => ['character' => _SWURarityCharacter($rarityName)]]],
        'set'          => ['abbreviation' => $setCode],
        'expansion'    => ['data' => ['attributes' => ['code' => $setCode]]],
        // GetPropertyValue('text') concatenates text + epicAction; deployText maps to deployBox.
        'text'         => (string)($m['text'] ?? ''),
        'epicAction'   => (string)($m['epicAction'] ?? ''),
        'deployBox'    => (string)($m['deployText'] ?? ''),
        'cost'         => isset($m['cost']) ? intval($m['cost']) : null,
        'power'        => isset($m['power']) ? intval($m['power']) : null,
        'hp'           => isset($m['hp']) ? intval($m['hp']) : null,
        'upgradePower' => isset($m['upgradePower']) ? intval($m['upgradePower']) : null,
        'upgradeHp'    => isset($m['upgradeHp']) ? intval($m['upgradeHp']) : null,
        'cardNumber'   => intval(ltrim(str_replace('T', '', $numPart), '0') ?: '0'),
        'unique'       => (bool)($m['unique'] ?? false),
        'documentId'   => '',   // mocks have no upstream document id
        'leaderUnitTitle'    => (string)($m['leaderUnitTitle'] ?? ''),
        'leaderUnitSubtitle' => (string)($m['leaderUnitSubtitle'] ?? ''),
        'leaderUnitTrait'    => is_array($m['leaderUnitTrait'] ?? null)
                                  ? implode(',', $m['leaderUnitTrait'])
                                  : (string)($m['leaderUnitTrait'] ?? ''),
        'leaderUnitArena'    => (string)($m['leaderUnitArena'] ?? ''),
        'leaderUnitType'     => (string)($m['leaderUnitType'] ?? ''),
    ];
}

// True when OFFICIAL data now exists for this CardID, making its mock entry inert and removable.
// Checked against cardArrayCache.json — the cache is pure API data (mocks are never written into
// it), so it is the only source that can tell official from mock. IsSWUCardID() cannot: the
// generated dictionaries contain both. Memoized; the cache is several MB.
function SWUMockIsSuperseded(string $cardID): bool {
    static $cache = null;
    if ($cache === null) {
        // SWUSim's cache specifically: it is the SET_NNN-keyed one, and a mock's CardID is SET_NNN.
        // (SWUDeck's cache is UUID-keyed, so '"id":"HMW_004"' would never match there.) Was
        // __DIR__/../GeneratedCode when this file lived in SWUSim/DevTools/; the move to AppCore/SWU
        // made that path resolve to a directory that does not exist, which would have silently
        // returned false for every card and stopped superseded mocks being reported.
        $path = __DIR__ . '/../../SWUSim/GeneratedCode/cardArrayCache.json';
        $cache = file_exists($path) ? (string)file_get_contents($path) : '';
    }
    if ($cache === '') return false;
    return strpos($cache, '"id":"' . $cardID . '"') !== false;
}

// Append every mock whose CardID is absent from $cardArray. Present == official data exists,
// which always wins; those are reported as superseded so the caller can log them.
// $asObjects: true for zzCardCodeGenerator (objects), false for ProcessKeywordsSWU (arrays).
function SWUMergeMockCards(array &$cardArray, bool $asObjects, string $path = ''): array {
    $mocks = SWULoadMockCards($path);
    if (empty($mocks)) return ['added' => [], 'superseded' => []];

    $seen = [];
    foreach ($cardArray as $c) {
        $id = is_object($c) ? ($c->id ?? '') : ($c['id'] ?? '');
        if ($id !== '') $seen[(string)$id] = true;
    }

    $added = [];
    $superseded = [];
    foreach ($mocks as $cardID => $mock) {
        if (!is_array($mock)) continue;
        if (isset($seen[$cardID])) { $superseded[] = $cardID; continue; }
        $row = SWUMockToImportRow($cardID, $mock);
        $cardArray[] = $asObjects ? json_decode(json_encode($row)) : $row;
        $seen[$cardID] = true;
        $added[] = $cardID;
    }
    return ['added' => $added, 'superseded' => $superseded];
}
