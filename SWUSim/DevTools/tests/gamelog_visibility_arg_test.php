<?php
// ── A GAME-LOG LINE WITH AN INVALID VISIBILITY IS SEEN BY NOBODY ─────────────────────────────────────
//
// AddGameLogEntry($type, $text, $visibility) stores "TYPE|VISIBILITY|text", and the generated reader
// (zzGameCodeGenerator's GameLog block → GetNextTurn.php) shows an entry to a seated viewer only when their
// own seat tag ('P1', 'P2', …) is in the comma-separated list, and to a spectator only when it is 'ALL'.
// Anything else matches no one — the line is written, and silently never displayed.
//
// FOUND 2026-09-11 (game-log gap pass): two card files passed a bare INT —
//     HMW_160 Noxious Refinery   AddGameLogEntry('REVEAL', …, 0);   // its public reveal: seen by nobody
//     HMW_108 The First Legion   AddGameLogEntry('ABILITY', …, 1);  // "P1 named the X trait": nobody
// The signature is `string $visibility`, but without strict_types PHP coerces the int to "0"/"1", so
// nothing errors. The schema suite's LOGCONTAINS ignores visibility, which is why both passed their tests;
// P<n>LOGSEES is the assertion that applies the real rule.
//
// This file guards the SHAPE: every literal 3rd argument must be 'ALL' or a seat-tag list ('P1',
// 'P1,P3'). A computed argument ("'P' . $player", a variable) is allowed — it cannot be judged statically.

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = realpath(__DIR__ . '/../../..');

// Every AddGameLogEntry() call whose 3rd argument is a single literal token that is not a valid visibility.
function glv_scan(string $src): array {
    $hits = [];
    $toks = token_get_all($src);
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        if (!is_array($t) || $t[0] !== T_STRING || $t[1] !== 'AddGameLogEntry') continue;
        $line = $t[2];
        $j = $i + 1;
        while ($j < $n && is_array($toks[$j]) && $toks[$j][0] === T_WHITESPACE) $j++;
        if ($j >= $n || $toks[$j] !== '(') continue;                 // not a call (e.g. the definition name)
        // The previous meaningful token must not be `function` — skip the definition itself.
        $p = $i - 1;
        while ($p >= 0 && is_array($toks[$p]) && $toks[$p][0] === T_WHITESPACE) $p--;
        if ($p >= 0 && is_array($toks[$p]) && $toks[$p][0] === T_FUNCTION) continue;
        $depth = 0; $arg = 0; $third = [];
        for ($k = $j; $k < $n; $k++) {
            $tk = $toks[$k];
            if ($tk === '(' || $tk === '[') { $depth++; if ($depth === 1) continue; }
            if ($tk === ')' || $tk === ']') { $depth--; if ($depth === 0) break; }
            if ($tk === ',' && $depth === 1) { $arg++; continue; }
            if ($arg === 2) {
                if (is_array($tk) && in_array($tk[0], [T_WHITESPACE, T_COMMENT], true)) continue;
                $third[] = $tk;
            }
        }
        if (count($third) !== 1) continue;                            // absent (defaults to 'ALL') or computed
        $only = $third[0];
        if (is_array($only) && $only[0] === T_CONSTANT_ENCAPSED_STRING) {
            $v = substr($only[1], 1, -1);
            if ($v === 'ALL' || preg_match('/^P\d+(,P\d+)*$/', $v)) continue;
            $hits[] = "line {$line}: visibility {$only[1]}";
        } elseif (is_array($only) && in_array($only[0], [T_LNUMBER, T_DNUMBER], true)) {
            $hits[] = "line {$line}: visibility {$only[1]} (an int — matches no viewer)";
        }
    }
    return $hits;
}

// Self-test: the scanner must catch the two shapes that shipped, and pass the valid ones.
check(count(glv_scan("<?php AddGameLogEntry('R', 'x', 0);")) === 1, 'scanner flags an int visibility');
check(count(glv_scan("<?php AddGameLogEntry('R', 'x', 'p1');")) === 1, 'scanner flags a malformed tag');
check(count(glv_scan("<?php AddGameLogEntry('R', 'x', 'ALL'); AddGameLogEntry('R', 'x', 'P1,P3'); AddGameLogEntry('R', 'x'); AddGameLogEntry('R', 'x', 'P' . \$p);")) === 0,
    'scanner passes ALL, seat lists, the default, and computed tags');

$files = array_merge(glob("$root/SWUSim/Custom/*.php"), glob("$root/SWUSim/Custom/cards/*/*.php"), [$root . '/SWUSim/CreateGame.php']);
$bad = [];
foreach ($files as $f) {
    foreach (glv_scan(file_get_contents($f)) as $h) $bad[] = str_replace($root . '/', '', $f) . ' ' . $h;
}
check(empty($bad), 'every literal game-log visibility is ALL or a seat list' . (empty($bad) ? '' : ":\n    " . implode("\n    ", $bad)));
echo "PASS\n";
