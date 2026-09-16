<?php
// Fills dictionary fields the upstream card API omits, from tracked source, at generation time.
//
// The official API publishes no traits for bases (legacy or new), and it has no fields for a
// deployed leader side that prints its own name/traits (HMW_004 Grand Moff Tarkin -> The Death
// Star). AppCore/SWU/CardDataSupplement.php supplies those values; each entry names its source:
//   swudb  — written by SWUSim/DevTools/backfill-card-data.php; refreshed by that tool
//   manual — hand-maintained; tooling never modifies it
//
// FILL-BLANKS ONLY: an entry applies solely when the dictionary value is blank. Official (or mock)
// data always wins, so an entry goes inert on its own once the API publishes the value. The
// supplement never creates a card: an entry for a CardID the dictionaries lack is reported.
//
// Shared by BOTH SWUSim and SWUDeck (same reason MockCardMerge.php lives in AppCore/SWU/). A field
// an app's dictionaries don't have (SWUDeck has no leaderUnit*) is skipped and reported.

const SWU_SUPPLEMENT_LIST_FIELDS = ['trait', 'leaderUnitTrait', 'aspect'];

function SWUCardDataSupplementPath(): string {
    return __DIR__ . '/CardDataSupplement.php';
}

function SWULoadCardDataSupplement(string $path = ''): array {
    if ($path === '') $path = SWUCardDataSupplementPath();
    if (!file_exists($path)) return [];
    $data = require $path;
    return is_array($data) ? $data : [];
}

function SWUIsBlankDictionaryValue($value): bool {
    if ($value === null) return true;
    if (is_array($value)) return count($value) === 0;
    return is_string($value) && trim($value) === '';
}

// Comma-joined, no spaces for list fields — matching how the generator stores $traitData.
function SWUNormalizeSupplementValue(string $field, $value): string {
    if (in_array($field, SWU_SUPPLEMENT_LIST_FIELDS, true)) {
        $parts = is_array($value) ? $value : explode(',', (string)$value);
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string)$p);
            if ($p !== '') $out[] = $p;
        }
        return implode(',', $out);
    }
    return trim((string)$value);
}

function SWUApplyCardDataSupplement(array &$associativeArrays, string $path = ''): array {
    $result = ['filled' => [], 'unknownCard' => [], 'unknownField' => []];
    foreach (SWULoadCardDataSupplement($path) as $cardID => $fields) {
        if (!is_array($fields)) continue;
        foreach ($fields as $field => $entry) {
            $key = $cardID . '.' . $field;
            if (!array_key_exists($field, $associativeArrays)) { $result['unknownField'][] = $key; continue; }
            if (!array_key_exists($cardID, $associativeArrays[$field])) { $result['unknownCard'][] = $key; continue; }
            if (!SWUIsBlankDictionaryValue($associativeArrays[$field][$cardID])) continue;
            $value = SWUNormalizeSupplementValue((string)$field, is_array($entry) ? ($entry['value'] ?? '') : '');
            if ($value === '') continue;
            $associativeArrays[$field][$cardID] = $value;
            $result['filled'][$cardID][$field] = (string)($entry['source'] ?? 'manual');
        }
    }
    return $result;
}

function SWUWriteCardDataSupplement(array $entries, string $path = ''): bool {
    if ($path === '') $path = SWUCardDataSupplementPath();
    ksort($entries);
    foreach ($entries as &$fields) {
        if (is_array($fields)) ksort($fields);
    }
    unset($fields);
    $header = <<<'PHP'
<?php
// Card data the upstream API OMITS, supplied from tracked source. Applied at generation time by
// AppCore/SWU/CardDataSupplementApply.php — FILL-BLANKS ONLY, official data always wins.
//
// CardID => field => ['value' => ..., 'source' => 'swudb' | 'manual']
//   swudb  — written/refreshed by: php SWUSim/DevTools/backfill-card-data.php [--set=X] [--dry]
//   manual — hand-maintained (e.g. HMW_004's deployed side); tooling never modifies it
// Field names are Schemas/SWUSim/ImportSchema.txt property names.
// Shared by BOTH SWUSim and SWUDeck.
//
// SCAFFOLD-IGNORE: pure DATA, not a card implementation. Tools that infer "is this card
// implemented?" by grepping quoted CardIDs must skip this file, or every card listed here looks
// implemented and gets no stub / is dropped from gap reports.
PHP;
    return file_put_contents($path, $header . "\nreturn " . var_export($entries, true) . ";\n") !== false;
}
