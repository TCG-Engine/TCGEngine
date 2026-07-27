<?php
// Dev-time header + filename generation for the card-file splitter.
// Reads the generated card dictionaries (must be included by the caller first).
//
// Filenames are reused verbatim from the existing Tests/Cases/<set>/*.md files so a
// card's implementation file and its test file always share a name. A clean CamelCase
// fallback covers cards that have no test file yet.

// CardID => test-file basename. A test file is named <Title>[_<Subtitle>] for the
// card under test, but its `#//` header may MENTION other cards first (e.g. a Kylo
// test that describes capturing IG-11). So we map the filename to the referenced
// CardID whose title actually matches the filename's first segment; only if none
// matches do we fall back to the first referenced CardID.
function splitter_build_testname_map(string $casesRoot): array {
    global $titleData;
    $map = [];
    foreach (glob("$casesRoot/*/*.md") as $f) {
        $base = basename($f, '.md');
        $seg0 = explode('_', $base)[0];                 // the Title portion (camel)
        $head = file_get_contents($f, false, null, 0, 800);
        if ($head === false || !preg_match_all('/\b([A-Z0-9]{2,4}_\d+)\b/', $head, $m)) continue;
        $ids = array_values(array_unique($m[1]));
        $chosen = null;
        foreach ($ids as $cid) {
            $t = $titleData[$cid] ?? '';
            if ($t !== '' && splitter_camel($t) === $seg0) { $chosen = $cid; break; }
        }
        if ($chosen === null) $chosen = $ids[0];         // fallback: first referenced
        if (!isset($map[$chosen])) $map[$chosen] = $base;
    }
    return $map;
}

// Basename derived purely from the card's own title/subtitle (clean CamelCase).
// For convention-following cards this already equals the test filename, so impl
// and test files share a name; it can never misattribute one card's name to
// another. ($testMap is accepted for signature compatibility but unused — the
// #//-header heuristic proved too fragile; see the collision guard in the driver
// for the rare distinct-cards-same-title tie.)
function splitter_card_basename(string $cardID, array $testMap = []): string {
    global $titleData, $subtitleData;
    $base = splitter_camel($titleData[$cardID] ?? $cardID);
    $sub  = $subtitleData[$cardID] ?? '';
    return $sub !== '' ? $base . '_' . splitter_camel($sub) : $base;
}

// CamelCase a display string (fallback only; clean, not quirk-matching).
function splitter_camel(string $s): string {
    $s = str_replace(["'", "\u{2019}", '"'], '', $s);
    $parts = preg_split('/[^A-Za-z0-9]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    foreach ($parts as $p) {
        // Leading-digit token lowercased (e.g. "21B" -> "21b"); else UpperCamel each word.
        $out .= ctype_digit($p[0]) ? strtolower($p) : ucfirst(strtolower($p));
    }
    return $out !== '' ? $out : preg_replace('/[^A-Za-z0-9]/', '', $s);
}

function splitter_card_header(string $cardID, array $reprintIDs): string {
    global $titleData, $subtitleData, $costData, $powerData, $hpData,
           $upgradePowerData, $upgradeHpData, $aspectData, $textData, $deployTextData;

    $title   = $titleData[$cardID]        ?? $cardID;
    $sub     = $subtitleData[$cardID]     ?? '';
    $cost    = $costData[$cardID]         ?? null;
    $aspects = $aspectData[$cardID]       ?? '';
    $power   = $powerData[$cardID]        ?? null;
    $hp      = $hpData[$cardID]           ?? null;
    $upP     = $upgradePowerData[$cardID] ?? null;
    $upH     = $upgradeHpData[$cardID]    ?? null;

    // Split "Epic Action:" out of the main text (leaders embed it inline).
    $rawText = $textData[$cardID] ?? '';
    $epic = '';
    if (($pos = strpos($rawText, 'Epic Action:')) !== false) {
        $epic    = trim(substr($rawText, $pos));
        $rawText = trim(substr($rawText, 0, $pos));
    }
    $deploy = $deployTextData[$cardID] ?? '';

    // Card text/deploy/epic can be multi-line (keywords on their own lines). A `//`
    // comment is single-line, so collapse newlines to " / " to keep the header valid.
    $flat = fn($s) => trim(preg_replace('/\s*[\r\n]+\s*/', ' / ', (string)$s));
    $rawText = $flat($rawText);
    $deploy  = $flat($deploy);
    $epic    = $flat($epic);

    $aspStr = $aspects !== '' ? '[' . $aspects . ']' : '';

    // Line 1: identity + reprints
    $line1 = "// $cardID";
    if ($reprintIDs) $line1 .= '  |  Reprints: ' . implode(', ', $reprintIDs);

    // Line 2: cost - title - subtitle - aspects - power - hp - upgrade power/hp (only-if-set)
    $parts = [];
    if ($cost !== null)  $parts[] = "Cost $cost";
    $parts[] = $title;
    if ($sub !== '')     $parts[] = $sub;
    if ($aspStr !== '')  $parts[] = $aspStr;
    if ($power !== null) $parts[] = "Power $power";
    if ($hp !== null)    $parts[] = "HP $hp";
    if ($upP !== null)   $parts[] = "Upgrade Power $upP";
    if ($upH !== null)   $parts[] = "Upgrade HP $upH";
    $line2 = '// ' . implode(' - ', $parts);

    $lines = [$line1, $line2, "// Text: $rawText"];
    if ($deploy !== '') $lines[] = "// DeployText: $deploy";
    if ($epic !== '')   $lines[] = "// $epic";  // already begins "Epic Action:"
    return implode("\n", $lines) . "\n";
}
