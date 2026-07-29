<?php
// Loads SWUSim/Custom/CardMocks.php and merges those entries into a generator card array.
// Shared by zzCardCodeGenerator.php (objects) and Data/ProcessKeywordsSWU.php (arrays) so the
// merge rule — official data always wins — lives in exactly one place.

function SWUSimMockCardsPath(): string {
    return __DIR__ . '/../Custom/CardMocks.php';
}

// Sets whose card numbers are TWO digits ("TS26_09") rather than three ("SOR_014"). Canonical copy
// for the mock pipeline; keep in sync with zzCardCodeGenerator.php's CardIDDoubleDigitSets and
// SWUDeck/Custom/CardIdentifiers.php's $doubleDigitsSets.
function SWUSimDoubleDigitSets(): array {
    return ['TS26'];
}

// A CardID this pipeline accepts: SET_NNN (SET_NN for double-digit sets), or a SET_T## token id.
// Set-aware on purpose — a flat 3-digit rule rejects every legitimate TS26 card.
function SWUSimIsMockCardID(string $cardID): bool {
    if (!preg_match('/^([A-Z0-9]{2,5})_(T\d{2}|\d{2,3})$/', $cardID, $m)) return false;
    if (strpos($m[2], 'T') === 0) return true;                       // token ids are always T##
    $expected = in_array(strtoupper($m[1]), SWUSimDoubleDigitSets(), true) ? 2 : 3;
    return strlen($m[2]) === $expected;
}

// SET_NNN => flat mock definition. Missing/unreadable file is not an error: no mocks.
function SWUSimLoadMockCards(string $path = ''): array {
    if ($path === '') $path = SWUSimMockCardsPath();
    if (!file_exists($path)) return [];
    $data = require $path;
    return is_array($data) ? $data : [];
}

// Wrap scalars as the {name: ...} relation shape GetPropertyValue()/SWURelAttrList() expect.
function _SWUSimRelationList($value): array {
    if ($value === null || $value === '') return [];
    if (!is_array($value)) $value = array_map('trim', explode(',', (string)$value));
    $out = [];
    foreach ($value as $v) {
        if (trim((string)$v) === '') continue;
        $out[] = ['name' => trim((string)$v)];
    }
    return $out;
}

// Flat mock -> the API-shaped row the generator's GetPropertyValue() reads.
function SWUSimMockToImportRow(string $cardID, array $m): array {
    $numPart = substr($cardID, strpos($cardID, '_') + 1);
    return [
        'id'           => $cardID,
        'title'        => (string)($m['title'] ?? ''),
        'subtitle'     => (string)($m['subtitle'] ?? ''),
        'type'         => ['name' => (string)($m['type'] ?? '')],
        'arenas'       => _SWUSimRelationList($m['arena'] ?? ''),
        'traits'       => _SWUSimRelationList($m['trait'] ?? ''),
        'aspects'      => _SWUSimRelationList($m['aspect'] ?? ''),
        'rarity'       => ['name' => (string)($m['rarity'] ?? '')],
        'set'          => ['abbreviation' => (string)($m['set'] ?? explode('_', $cardID)[0])],
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
function SWUSimMockIsSuperseded(string $cardID): bool {
    static $cache = null;
    if ($cache === null) {
        $path = __DIR__ . '/../GeneratedCode/cardArrayCache.json';
        $cache = file_exists($path) ? (string)file_get_contents($path) : '';
    }
    if ($cache === '') return false;
    return strpos($cache, '"id":"' . $cardID . '"') !== false;
}

// Append every mock whose CardID is absent from $cardArray. Present == official data exists,
// which always wins; those are reported as superseded so the caller can log them.
// $asObjects: true for zzCardCodeGenerator (objects), false for ProcessKeywordsSWU (arrays).
function SWUSimMergeMockCards(array &$cardArray, bool $asObjects, string $path = ''): array {
    $mocks = SWUSimLoadMockCards($path);
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
        $row = SWUSimMockToImportRow($cardID, $mock);
        $cardArray[] = $asObjects ? json_decode(json_encode($row)) : $row;
        $seen[$cardID] = true;
        $added[] = $cardID;
    }
    return ['added' => $added, 'superseded' => $superseded];
}
