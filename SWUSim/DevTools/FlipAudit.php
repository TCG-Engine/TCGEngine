<?php
// Flip audit: before deleting a set's mocks, compare each mock's own values (a mocks=prefer
// snapshot) with what the dictionaries hold without it (a mocks=0 snapshot: official + supplement).
// Pure; the CLI is flip-audit.php.

const SWU_FLIP_AUDIT_LIST_FIELDS = ['aspect', 'trait', 'leaderUnitTrait'];
const SWU_FLIP_AUDIT_TEXT_FIELDS = ['text', 'deployText'];

// Typography + whitespace insensitive; list fields order-insensitive. Reports keep raw values.
function SWUFlipAuditNormalize(string $field, $value): string {
    if (is_bool($value)) $value = $value ? '1' : '0';
    if (is_array($value)) $value = implode(',', $value);
    $s = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "\u{2013}", "\u{2014}", "\u{2011}", "\u{00A0}"],
                     ['"', '"', "'", "'", '-', '-', '-', ' '], (string)$value);
    $s = trim((string)preg_replace('/\s+/u', ' ', $s));
    if (in_array($field, SWU_FLIP_AUDIT_LIST_FIELDS, true)) {
        $parts = array_values(array_filter(array_map('trim', explode(',', $s)), 'strlen'));
        sort($parts);
        $s = implode(',', $parts);
    }
    return $s;
}

function SWUFlipAudit(array $preferSnapshot, array $officialSnapshot, array $mockIDs, string $set, array $acceptedGaps = []): array {
    $set = strtoupper($set);
    $inSet = fn(string $id) => strtoupper(explode('_', $id)[0]) === $set;
    $mockIDs = array_values(array_filter(array_map('strval', $mockIDs), $inSet));
    sort($mockIDs);
    $accepted = array_fill_keys($acceptedGaps, true);
    $mockDicts = $preferSnapshot['dictionaries'] ?? [];
    $mockProv  = $preferSnapshot['provenance'] ?? [];
    $offDicts  = $officialSnapshot['dictionaries'] ?? [];
    $out = ['blockNoOfficial' => [], 'blockGap' => [], 'review' => [], 'officialOnly' => [], 'acceptedGap' => [], 'new' => [], 'safeToDelete' => []];

    foreach ($mockIDs as $id) {
        if (!array_key_exists($id, $offDicts['title'] ?? [])) { $out['blockNoOfficial'][] = $id; continue; }
        $blocked = false;
        foreach (array_keys($mockDicts) as $field) {
            $m = $mockDicts[$field][$id] ?? null;
            if (strpos((string)($mockProv[$id][$field] ?? ''), 'supplement:') === 0) $m = null;
            $o = $offDicts[$field][$id] ?? null;
            $mBlank = SWUIsBlankDictionaryValue($m);
            $oBlank = SWUIsBlankDictionaryValue($o);
            if ($mBlank && $oBlank) continue;
            if ($oBlank) {
                if (isset($accepted[$id . '.' . $field])) {
                    $out['acceptedGap'][] = ['cardID' => $id, 'field' => $field, 'mock' => $m];
                } else {
                    $out['blockGap'][] = ['cardID' => $id, 'field' => $field, 'mock' => $m];
                    $blocked = true;
                }
                continue;
            }
            if ($mBlank) { $out['officialOnly'][] = ['cardID' => $id, 'field' => $field, 'official' => $o]; continue; }
            if (SWUFlipAuditNormalize($field, $m) !== SWUFlipAuditNormalize($field, $o)) {
                $out['review'][] = ['cardID' => $id, 'field' => $field, 'mock' => $m, 'official' => $o];
            }
        }
        if (!$blocked) $out['safeToDelete'][] = $id;
    }

    usort($out['review'], function ($a, $b) {
        $rank = fn($r) => in_array($r['field'], SWU_FLIP_AUDIT_TEXT_FIELDS, true) ? 0 : 1;
        return [$rank($a), $a['cardID'], $a['field']] <=> [$rank($b), $b['cardID'], $b['field']];
    });

    $mockSet = array_fill_keys($mockIDs, true);
    foreach (array_keys($offDicts['title'] ?? []) as $id) {
        $id = (string)$id;
        if ($inSet($id) && !isset($mockSet[$id])) $out['new'][] = $id;
    }
    sort($out['new']);
    return $out;
}
