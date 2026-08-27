<?php
// ── "PASS" is OVERLOADED, and a zero-lower-bound multi-select is where that bites ────────────────────
//
// A "PASS" answer means two different things and the decision queue cannot tell them apart:
//   • "I decline"      — for a genuine "you may", skipping the follow-up CUSTOM is CORRECT.
//   • "I chose zero"   — for a multi-select whose lower bound is 0, picking none is an ANSWER, and the
//                        follow-up must still run.
// ExecuteStaticMethods (Core/DecisionQueueController.php) makes a PASS STICKY: it skips every following
// CUSTOM / MZMOVE / SYSTEM that is not flagged DontSkipOnPass. So a "0|N|…" MZMULTICHOOSE followed by an
// unflagged CUSTOM silently drops that continuation the moment the player confirms with nothing selected.
//
// This is not theoretical. Measured live (2026-08-26 and 2026-08-27):
//   • CREDIT_PAY / DROID_PAY — confirming zero Credits left the card UNPLAYED, cost unpaid.
//   • SEC_164 Warrior of Clan Ordo — took ZERO self-base damage instead of 2.
//   • EXPLOIT_RESOLVE — declining Exploit left the unit UNPLAYED. Every Exploit card in the game.
//   • JTL_232 Jump to Lightspeed — the unit was never bounced; the event was spent for nothing.
//   • SEC_169 AAT Incinerator — the "if no friendly units were damaged" self-base penalty was waived.
//   • LOF_205 Force Speed — the TempZone staging was never drained.
//
// ⚠ WHY THE SCHEMA SUITE CANNOT CATCH THIS: `-` and "PASS" are TWO DIFFERENT DECLINES, and `-` takes a
// branch that works. Historically every decline test in the repo answered `-`, so all of the above passed
// green. Guard sections are now written as byte-for-byte twins answering "PASS"; this file guards the
// SHAPE so a new call site cannot reintroduce the class without a twin existing yet.
//
// THE FIX AT A CALL SITE: use SWUQueueMultiChoose($player,$min,$max,$mzIDs,$tooltip,$continuation) — it
// DERIVES dontSkipOnPass from $min <= 0, so the hazard cannot come back by forgetting. A raw AddDecision
// pair is still allowed, but then the CUSTOM must carry `dontSkipOnPass: 1` explicitly.

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

/** Split a PHP argument list on top-level commas (string- and nesting-aware). */
function dsop_split_args(string $s): array {
    $out = []; $cur = ''; $depth = 0; $q = null; $n = strlen($s);
    for ($i = 0; $i < $n; $i++) {
        $c = $s[$i];
        if ($q !== null) {
            if ($c === '\\') { $cur .= $c . ($s[$i+1] ?? ''); $i++; continue; }
            if ($c === $q) $q = null;
            $cur .= $c;
        } elseif ($c === '"' || $c === "'") { $q = $c; $cur .= $c; }
        elseif ($c === '(' || $c === '[' || $c === '{') { $depth++; $cur .= $c; }
        elseif ($c === ')' || $c === ']' || $c === '}') { $depth--; $cur .= $c; }
        elseif ($c === ',' && $depth === 0) { $out[] = trim($cur); $cur = ''; }
        else $cur .= $c;
    }
    if (trim($cur) !== '') $out[] = trim($cur);
    return $out;
}

/** Every AddDecision( … ) call in $text, in source order: ['line'=>int,'body'=>string]. */
function dsop_calls(string $text): array {
    $out = [];
    if (!preg_match_all('/AddDecision\s*\(/', $text, $m, PREG_OFFSET_CAPTURE)) return $out;
    $n = strlen($text);
    foreach ($m[0] as $hit) {
        $i = $hit[1] + strlen($hit[0]); $depth = 1; $j = $i;
        while ($j < $n && $depth > 0) {
            $c = $text[$j];
            if ($c === '"' || $c === "'") {
                $q = $c; $j++;
                while ($j < $n && $text[$j] !== $q) { if ($text[$j] === '\\') $j++; $j++; }
            } elseif ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            $j++;
        }
        $out[] = ['line' => substr_count($text, "\n", 0, $hit[1]) + 1, 'body' => substr($text, $i, $j - 1 - $i)];
    }
    return $out;
}

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/SWUSim/Custom'));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php' && $f->getFilename() !== '_index.generated.php')
        $files[] = $f->getPathname();
}
sort($files);
check(count($files) > 100, 'scanned the SWUSim/Custom tree (' . count($files) . ' php files)');

$offenders = []; $guarded = 0;
foreach ($files as $path) {
    $text = file_get_contents($path);
    if (strpos($text, 'MZMULTICHOOSE') === false) continue;
    $calls = dsop_calls($text);
    foreach ($calls as $idx => $call) {
        $args = dsop_split_args($call['body']);
        if (count($args) < 3 || strpos($args[1], 'MZMULTICHOOSE') === false) continue;
        // Only a LITERAL "0|…" lower bound is decidable here. A computed min (e.g. "{$min}|…") is left to
        // SWUQueueMultiChoose, which derives the flag at runtime — that is exactly why it is the seam.
        if (!preg_match('/^\s*["\']\s*0\s*\|/', $args[2])) continue;
        $next = $calls[$idx + 1] ?? null;
        if ($next === null) continue;
        $nargs = dsop_split_args($next['body']);
        if (count($nargs) < 2 || strpos($nargs[1], 'CUSTOM') === false) continue;
        $named      = preg_match('/dontSkipOnPass\s*:\s*(?!0\b)/', $next['body']) === 1;
        $positional = count($nargs) >= 6 && trim($nargs[5]) !== '0';
        if ($named || $positional) { $guarded++; continue; }
        $offenders[] = str_replace($root . '/', '', $path) . ':' . $next['line'];
    }
}

check($guarded > 0, "found flagged zero-min continuations to compare against ({$guarded})");
check(empty($offenders),
    'every literal-zero-lower-bound MZMULTICHOOSE has a DontSkipOnPass continuation'
    . (empty($offenders) ? '' : " — UNFLAGGED:\n    " . implode("\n    ", $offenders)
        . "\n  Fix: route the pair through SWUQueueMultiChoose(\$player, 0, \$max, \$mzIDs, \$tooltip, \$continuation)"
        . "\n       (it derives the flag from \$min <= 0), or add `dontSkipOnPass: 1` to the CUSTOM."
        . "\n  Then add a decline test that answers \"PASS\", not just `-` — they are different declines."));

// Self-test the scanner: a guard that cannot fail is not a guard. Feed it the exact broken shape and
// assert it is detected (the #971 art-path guard passed with the implementation deleted; not again).
$probe = '<?php AddDecision($p, "MZMULTICHOOSE", "0|3|" . $x, 1); AddDecision($p, "CUSTOM", "H", 1);';
$pc = dsop_calls($probe);
$pa = dsop_split_args($pc[1]['body']);
check(count($pc) === 2 && count($pa) === 4 && preg_match('/dontSkipOnPass/', $pc[1]['body']) === 0,
    'self-test: the scanner parses and would flag an unguarded 0-min pair');

echo "PASS: dontskiponpass_zero_min_test\n";
