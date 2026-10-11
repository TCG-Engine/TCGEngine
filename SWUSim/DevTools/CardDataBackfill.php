<?php
// Plans a SWUDB backfill of AppCore/SWU/CardDataSupplement.php. Pure: the fetcher is injected so
// tests need no network. The CLI is backfill-card-data.php.
//
// Candidates are allow-listed fields whose OFFICIAL value (a mocks=0 snapshot) is blank, plus fields
// already filled by a swudb entry (so they refresh). manual entries are never fetched or changed.

// field => SWUDB getPrintingInfo record -> normalized dictionary value. Extend one line at a time.
function SWUBackfillFieldMap(): array {
    return [
        'trait' => function (array $rec): string {
            return SWUNormalizeSupplementValue('trait', $rec['traits'] ?? []);
        },
    ];
}

function _SWUBackfillTitleKey(string $title): string {
    return strtolower(trim(str_replace(["\u{2019}", "\u{2018}"], "'", $title)));
}

function SWUPlanCardDataBackfill(array $snapshot, array $supplement, callable $fetch, array $fieldMap, array $opts = []): array {
    $dicts = $snapshot['dictionaries'] ?? [];
    $prov  = $snapshot['provenance'] ?? [];
    $set   = strtoupper((string)($opts['set'] ?? ''));
    $type  = (string)($opts['type'] ?? '');
    $counts = ['added' => 0, 'changed' => 0, 'unchanged' => 0, 'sourceEmpty' => 0, 'failed' => 0, 'titleMismatch' => 0, 'tokenSkipped' => 0];
    $rows = [];

    $candidates = [];   // CardID => [field, ...]
    foreach (array_keys($fieldMap) as $field) {
        foreach (($dicts[$field] ?? []) as $cardID => $value) {
            $cardID = (string)$cardID;
            if ($set !== '' && strtoupper(explode('_', $cardID)[0]) !== $set) continue;
            if ($type !== '' && strpos((string)($dicts['type'][$cardID] ?? ''), $type) === false) continue;
            $existing = $supplement[$cardID][$field] ?? null;
            if ($existing !== null && ($existing['source'] ?? '') !== 'swudb') continue;
            $isSwudbFill = ($prov[$cardID][$field] ?? '') === 'supplement:swudb';
            if (!SWUIsBlankDictionaryValue($value) && !$isSwudbFill) continue;
            $candidates[$cardID][] = $field;
        }
    }
    ksort($candidates);

    foreach ($candidates as $cardID => $fields) {
        $row = fn(string $field, string $status, string $value = '', ?string $prior = null)
            => ['cardID' => $cardID, 'field' => $field, 'status' => $status, 'value' => $value, 'prior' => $prior];
        if (preg_match('/_T\d{2}$/', $cardID)) {
            foreach ($fields as $f) $rows[] = $row($f, 'token-skipped');
            $counts['tokenSkipped']++;
            continue;
        }
        [$setCode, $number] = explode('_', $cardID, 2);
        $rec = $fetch($setCode, $number);
        if (!is_array($rec) || trim((string)($rec['cardName'] ?? '')) === '') {
            foreach ($fields as $f) $rows[] = $row($f, 'fetch-failed');
            $counts['failed']++;
            continue;
        }
        $localTitle = (string)($dicts['title'][$cardID] ?? '');
        if ($localTitle !== '' && _SWUBackfillTitleKey($localTitle) !== _SWUBackfillTitleKey((string)$rec['cardName'])) {
            foreach ($fields as $f) $rows[] = $row($f, 'title-mismatch', (string)$rec['cardName']);
            $counts['titleMismatch']++;
            continue;
        }
        foreach ($fields as $field) {
            $value = ($fieldMap[$field])($rec);
            $prior = $supplement[$cardID][$field]['value'] ?? null;
            if ($value === '') { $rows[] = $row($field, 'source-empty', '', $prior); $counts['sourceEmpty']++; continue; }
            if ($prior === null)        { $status = 'added';     $counts['added']++; }
            else if ($prior !== $value) { $status = 'changed';   $counts['changed']++; }
            else                        { $status = 'unchanged'; $counts['unchanged']++; }
            $supplement[$cardID][$field] = ['value' => $value, 'source' => 'swudb'];
            $rows[] = $row($field, $status, $value, $prior);
        }
    }
    return ['supplement' => $supplement, 'rows' => $rows, 'counts' => $counts];
}
