<?php
// Card refs (§6.1) and the copy-id ledger. Every physical card gets one copy id for the whole game ("SOR#108",
// "SOR#108:2", …), numbered in deck order per base ref and unique across both seats. The ledger tracks which
// copy sits in which seat's zone, and binds in-play copies to the engine UniqueID so a unit keeps its id when
// it changes controller. Ledger = ['issued' => [ref => n], 'zones' => [seat => [zone => copyId[]]], 'uids' => [uid => copyId]].

// SWUSim CardID → SWU-PGN card ref; SwuPgnMapCardId (SwuPgnCardMap.php) is its inverse.
function SwuPgnCardRef(string $cardID): string {
    $d = DecomposeCardID($cardID);
    if ($d === null) return $cardID;
    if (!$d['isToken']) return $d['set'] . '#' . sprintf('%03d', intval($d['number']));   // §6.1: zero-padded to 3+ digits
    $reserved = ['Credit Token' => 'credit'];
    $type = (string)(CardType($cardID) ?? '');
    $name = $reserved[$type] ?? SwuPgnTokenName((string)(CardTitle($cardID) ?? ''));
    $uuid = (string)(GetCardUUID($cardID) ?? '');
    return 'TOKEN:' . $name . ($uuid !== '' && ctype_digit($uuid) ? '#' . $uuid : '');
}

// §6.1 [W-40]: a token's name from its English printed title — NFKD, combining marks dropped, lower case, only a–z 0–9 and
// spaces kept, trimmed, each run of spaces one "-" ("X-Wing" → "xwing", "TIE Fighter" → "tie-fighter"). Without the intl
// extension a Latin-letter fold stands in for NFKD (token titles are ASCII today).
function SwuPgnTokenName(string $title): string {
    if (class_exists('Normalizer')) $title = (string)Normalizer::normalize($title, Normalizer::FORM_KD);
    else $title = strtr($title, ['À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
        'Ý'=>'Y','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i',
        'î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y']);
    $s = preg_replace('/[\x{0300}-\x{036F}]/u', '', $title) ?? $title;
    $s = preg_replace('/[^a-z0-9 ]/', '', strtolower($s));
    return preg_replace('/ +/', '-', trim($s));
}

function _SwuPgnLedgerIssue(array &$ledger, string $ref): string {
    $n = ($ledger['issued'][$ref] ?? 0) + 1;
    $ledger['issued'][$ref] = $n;
    return $n === 1 ? $ref : "$ref:$n";
}

function SwuPgnLedgerInit(array $deckOrders): array {
    $ledger = ['issued' => [], 'zones' => [], 'uids' => []];
    ksort($deckOrders);
    foreach ($deckOrders as $seat => $cards) {
        $ledger['zones'][$seat] = ['deck' => []];
        foreach ($cards as $cardID) $ledger['zones'][$seat]['deck'][] = _SwuPgnLedgerIssue($ledger, SwuPgnCardRef((string)$cardID));
    }
    return $ledger;
}

// Removes and returns a copy of $cardID from that seat's zone: the copy bound to $uid when given, else the
// lowest-numbered copy. Null when the zone holds none.
function SwuPgnLedgerTake(array &$ledger, int $seat, string $zone, string $cardID, ?int $uid = null): ?string {
    $list = $ledger['zones'][$seat][$zone] ?? [];
    $ref = SwuPgnCardRef($cardID);
    $pick = null; $pickN = PHP_INT_MAX;
    $bound = $uid === null ? null : ($ledger['uids'][$uid] ?? null);
    foreach ($list as $i => $copy) {
        if (SwuPgnBaseId($copy) !== $ref) continue;
        if ($bound !== null) { if ($copy === $bound) { $pick = $i; break; } continue; }
        $n = SwuPgnCopyNumber($copy) ?? 1;
        if ($n < $pickN) { $pick = $i; $pickN = $n; }
    }
    if ($pick === null) return null;
    $copy = $list[$pick];
    array_splice($list, $pick, 1);
    $ledger['zones'][$seat][$zone] = $list;
    foreach ($ledger['uids'] as $u => $c) if ($c === $copy) unset($ledger['uids'][$u]);
    return $copy;
}

function SwuPgnLedgerPut(array &$ledger, int $seat, string $zone, string $copyId, ?int $uid = null): void {
    $ledger['zones'][$seat][$zone][] = $copyId;
    if ($uid !== null) $ledger['uids'][$uid] = $copyId;
}

function SwuPgnLedgerByUid(array $ledger, int $uid): ?string {
    return $ledger['uids'][$uid] ?? null;
}

function SwuPgnLedgerNewToken(array &$ledger, string $cardID): string {
    return _SwuPgnLedgerIssue($ledger, SwuPgnCardRef($cardID));
}
