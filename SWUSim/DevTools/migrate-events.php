<?php
// migrate-events.php <SET> [--dry]
// One-off migrator: extracts each event's case body from the OnPlayEvent switch in
// CardEffects.php (via token_get_all, so braces inside strings/comments/interpolation are handled
// correctly) and writes it as a $whenPlayedAbilities["CARDID:0"] registration into the card's
// per-card file under cards/<set>/. Idempotent: skips a CardID already registered anywhere in
// Custom/. Bodies containing $cardID are SKIPPED with a warning (they need a per-printing literal /
// shared helper — handle by hand). --dry prints planned actions + the generated block without writing.
//
// Safety net is unchanged: after running, verify with verify-event-migration.php <SET> + php -l +
// the full regression suite.

$SET = $argv[1] ?? '';
$DRY = in_array('--dry', $argv, true);
if ($SET === '') { fwrite(STDERR, "usage: migrate-events.php <SET> [--dry]\n"); exit(2); }

$root   = dirname(__DIR__);                 // SWUSim/
$custom = $root . '/Custom';
$ceFile = $custom . '/CardEffects.php';
$src    = file_get_contents($ceFile);

// ---- tokenize and index offsets ----
$tokens = token_get_all($src);
$flat = []; $off = 0;
foreach ($tokens as $t) {
    if (is_array($t)) { $flat[] = ['id' => $t[0], 'text' => $t[1], 'off' => $off]; $off += strlen($t[1]); }
    else              { $flat[] = ['id' => null, 'text' => $t,    'off' => $off]; $off += strlen($t); }
}
$n = count($flat);

// ---- locate OnPlayEvent -> its switch -> switch-body brace depth ----
$i = 0;
for (; $i < $n; $i++) {
    if ($flat[$i]['id'] === T_FUNCTION) {
        // next T_STRING is the function name
        for ($j = $i + 1; $j < $n; $j++) {
            if ($flat[$j]['id'] === T_STRING) { if ($flat[$j]['text'] === 'OnPlayEvent') { $i = $j; break 2; } break; }
        }
    }
}
if ($i >= $n) { fwrite(STDERR, "OnPlayEvent not found\n"); exit(1); }
// find T_SWITCH after OnPlayEvent
for (; $i < $n && $flat[$i]['id'] !== T_SWITCH; $i++);
if ($i >= $n) { fwrite(STDERR, "switch not found\n"); exit(1); }
// find the switch body opening '{'
for (; $i < $n && $flat[$i]['text'] !== '{'; $i++);
$switchOpen = $i;              // index of switch '{'
$switchBodyDepth = 1;          // inside the switch block, cases live at this depth

// ---- walk the switch, collecting case groups ----
$depth = 0;
$groups = [];                  // each: ['cards'=>[...], 'body'=>string]
$pending = [];                 // cardIDs awaiting a body
// Pass 1: record every case/default label (and the switch close) at switch-body depth, in order.
// For each case, capture the CardID and the offset just after its ':' (where its body begins).
$labels = [];       // ['type'=>'case','card'=>id,'labelOff'=>int,'bodyOff'=>int] | ['type'=>'default'|'end','labelOff'=>int]
$depth = 0;
for ($k = $switchOpen; $k < $n; $k++) {
    $txt = $flat[$k]['text'];
    if ($txt === '{') { $depth++; continue; }
    if ($txt === '}') { $depth--; if ($depth === 0) { $labels[] = ['type' => 'end', 'labelOff' => $flat[$k]['off']]; break; } continue; }
    if ($depth !== $switchBodyDepth) continue;
    if ($flat[$k]['id'] === T_CASE) {
        $cid = null; $bodyOff = null;
        for ($j = $k + 1; $j < $n; $j++) {
            if ($cid === null && $flat[$j]['id'] === T_CONSTANT_ENCAPSED_STRING) $cid = trim($flat[$j]['text'], "'\"");
            if ($flat[$j]['text'] === ':') { $bodyOff = $flat[$j]['off'] + 1; break; }
        }
        $labels[] = ['type' => 'case', 'card' => $cid, 'labelOff' => $flat[$k]['off'], 'bodyOff' => $bodyOff];
    } elseif ($flat[$k]['id'] === T_DEFAULT) {
        $labels[] = ['type' => 'default', 'labelOff' => $flat[$k]['off']];
    }
}

// Pass 2: group consecutive case labels. A label is a pure fall-through (shares the next label's
// body) only when the text between its ':' and the next label is empty (comments/whitespace only).
// Otherwise it OWNS its body — which may be a braced block { ... } OR a bare inline statement list
// (e.g. `case 'X': foo(); return;`). This is the fix for non-braced cases.
$stripCode = fn($s) => trim(preg_replace('#//[^\n]*|/\*.*?\*/#s', '', $s));
$li = 0; $L = count($labels);
while ($li < $L) {
    if ($labels[$li]['type'] !== 'case') { $li++; continue; }
    $cards = [];
    $j = $li;
    while (true) {
        $cards[] = $labels[$j]['card'];
        $between = substr($src, $labels[$j]['bodyOff'], $labels[$j + 1]['labelOff'] - $labels[$j]['bodyOff']);
        if ($stripCode($between) === '' && $labels[$j + 1]['type'] === 'case') { $j++; continue; } // fall-through
        break; // $j owns the body
    }
    $owner = $labels[$j];
    $bodyRaw = substr($src, $owner['bodyOff'], $labels[$j + 1]['labelOff'] - $owner['bodyOff']);
    $body = trim($bodyRaw);                             // strip ALL surrounding whitespace/newlines
    if ($body !== '' && $body[0] === '{' && substr($body, -1) === '}') {
        $body = trim(substr($body, 1, -1));            // single braced block -> drop the outer { ... }
    }
    // else: bare inline body (e.g. `foo(); return;`) — keep as-is
    $groups[] = ['cards' => $cards, 'body' => $body];
    $li = $j + 1;
}

// ---- filter to the requested set ----
$setGroups = [];
foreach ($groups as $g) {
    $inSet = array_values(array_filter($g['cards'], fn($c) => strpos($c, $SET . '_') === 0));
    if ($inSet) $setGroups[] = ['cards' => $g['cards'], 'body' => $g['body']];
}

// ---- already-registered set (idempotency) ----
$registered = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($custom, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (preg_match_all('/\$whenPlayedAbilities\s*\[\s*"([A-Z0-9]+_[0-9]+):0"\s*\]\s*=/', file_get_contents($f->getPathname()), $m))
        foreach ($m[1] as $c) $registered[$c] = true;
}

// ---- dictionary title lookup (for new-file naming) ----
$dict = file_get_contents($root . '/GeneratedCode/GeneratedCardDictionaries.php');
function titleOf($cardID, $dict) {
    if (preg_match("/'" . preg_quote($cardID, '/') . "' => '((?:[^'\\\\]|\\\\.)*)'/", $dict, $m))
        return stripcslashes($m[1]);
    return $cardID;
}
function fileNameFromTitle($title) {
    // PascalCase, strip punctuation, keep small words capitalized (matches existing convention).
    $t = preg_replace("/[^A-Za-z0-9 ]/", '', $title);
    $parts = preg_split('/\s+/', trim($t));
    return implode('', array_map('ucfirst', $parts)) . '.php';
}
function textOf($cardID, $dict) {
    // The rules text is the longest string value keyed by this CardID across the dictionary arrays.
    if (!preg_match_all("/'" . preg_quote($cardID, '/') . "' => '((?:[^'\\\\]|\\\\.)*)'/", $dict, $m)) return '';
    $best = '';
    foreach ($m[1] as $s) { $s = stripcslashes($s); if (strlen($s) > strlen($best)) $best = $s; }
    return $best;
}

$actions = [];
foreach ($setGroups as $g) {
    $first = null; $skip = false;
    foreach ($g['cards'] as $c) {
        if (strpos($c, $SET . '_') !== 0) continue; // only handle this set's cards (a mixed group shouldn't happen)
        if (isset($registered[$c])) { $skip = true; break; }
    }
    if ($skip) { $actions[] = "SKIP (already registered): " . implode(',', $g['cards']); continue; }
    if (strpos($g['body'], '$cardID') !== false) {
        $actions[] = "MANUAL (\$cardID in body): " . implode(',', $g['cards']);
        continue;
    }
    // pick the primary card = first in-set card
    $setCards = array_values(array_filter($g['cards'], fn($c) => strpos($c, $SET . '_') === 0));
    $primary = $setCards[0];

    // find existing file that mentions this cardID ANYWHERE (code OR comment — reprint-consolidated
    // files reference a printing only in a header comment). Append there; else derive a new filename.
    $target = null;
    foreach (glob("$custom/cards/" . strtolower($SET) . "/*.php") as $cf) {
        if (preg_match('/\b' . preg_quote($primary, '/') . '\b/', file_get_contents($cf))) { $target = $cf; break; }
    }
    if ($target === null) {
        $target = "$custom/cards/" . strtolower($SET) . "/" . fileNameFromTitle(titleOf($primary, $dict));
    }
    // NEVER clobber: create-with-header only if the file truly does not exist yet; otherwise append.
    $create = !file_exists($target);

    // build block
    $block  = "\n// When Played (event) — migrated from OnPlayEvent.\n";
    $block .= "\$whenPlayedAbilities[\"$primary:0\"] = function(\$player, \$mzID = '') {\n";
    $block .= rtrim($g['body']) . "\n};\n";
    foreach ($setCards as $c) if ($c !== $primary) $block .= "\$whenPlayedAbilities[\"$c:0\"] = \$whenPlayedAbilities[\"$primary:0\"];\n";

    if ($DRY) {
        $actions[] = ($create ? "CREATE " : "APPEND ") . basename($target) . "  <= " . implode(',', $setCards) . "\n" . $block;
    } else {
        if ($create) {
            $hdr = "<?php\n// $primary\n// " . titleOf($primary, $dict) . "\n";
            $txt = str_replace(["\r", "\n"], ' ', textOf($primary, $dict)); // keep the Text header one line
            if ($txt !== '') $hdr .= "// Text: $txt\n";
            file_put_contents($target, $hdr . $block);
        }
        else file_put_contents($target, $block, FILE_APPEND);
        $actions[] = ($create ? "CREATED " : "APPENDED ") . basename($target) . "  <= " . implode(',', $setCards);
    }
}

echo implode("\n", $actions) . "\n";
echo "----\n" . count($setGroups) . " group(s) for $SET; " . ($DRY ? "DRY (no writes)" : "written") . "\n";
