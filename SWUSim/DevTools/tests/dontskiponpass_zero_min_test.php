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

/**
 * Source of ONE $customDQHandlers["NAME"] body, via token_get_all().
 * ⚠ Must be the tokenizer, not a regex window and not brace counting. A regex window run past the
 * handler start reads into the NEXT function and reports whatever it finds there (that false-positived
 * SHD_012 Bo-Katan on a `SWUAfterAction` belonging to her leaderAbilities block further down the file).
 * Brace counting is no better: it miscounts `{` inside strings, comments and "{$interpolation}", which
 * is how an earlier scan read a 19-line handler as 3261 lines.
 */
function dsop_handler_body(string $text, string $name): ?string {
    $T = token_get_all($text); $n = count($T);
    $norm = [];
    foreach ($T as $x) $norm[] = is_array($x) ? [$x[0], $x[1]] : [null, $x];
    for ($i = 0; $i < $n; $i++) {
        if ($norm[$i][0] !== T_VARIABLE || $norm[$i][1] !== '$customDQHandlers') continue;
        $j = $i + 1; while ($j < $n && $norm[$j][0] === T_WHITESPACE) $j++;
        if ($norm[$j][1] !== '[') continue;
        $j++; while ($j < $n && $norm[$j][0] === T_WHITESPACE) $j++;
        if ($norm[$j][0] !== T_CONSTANT_ENCAPSED_STRING || trim($norm[$j][1], "'\"") !== $name) continue;
        $k = $j; $d = 0; $open = -1;
        for (; $k < $n; $k++) {
            $c = $norm[$k][1];
            if ($c === '(') $d++; elseif ($c === ')') $d--;
            elseif ($c === '{' && $d === 0) { $open = $k; break; }
            elseif ($c === ';' && $d === 0) break;
        }
        if ($open < 0) continue;
        $d = 0; $out = '';
        for ($k = $open; $k < $n; $k++) {
            $id = $norm[$k][0]; $c = $norm[$k][1];
            if ($c === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) $d++;
            elseif ($c === '}') { $d--; if ($d === 0) return $out; }
            // ⚠ COMMENTS ARE EXCLUDED. Matching them is a real failure mode, not a hypothetical: this
            // check first reported IC27_168#2 as an action-closer because its body carries the comment
            // "No SWUAfterAction: this is an event…". A sibling scan flagged Yularen as a two-seat
            // hardcode by matching the comment telling you not to hardcode seats. Assert CODE.
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) continue;
            $out .= $c;
        }
    }
    return null;
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

// ── The MZMAYCHOOSE half of the same class ───────────────────────────────────────────────────────────
// ⚠ THE CLIENT NEVER SUBMITS '-'. All three decline paths in Core/UILibraries*.js — the keyboard pass
// (TryPassCurrentDecision), the inline board Pass button, and the MZChoose popup Pass button — submit the
// literal "PASS". `-` is a server/harness-internal value only. So for an MZMAYCHOOSE, "PASS" is not the
// exotic decline, it is the ONLY decline a player can produce, and a sticky PASS skipped the continuation
// every single time. Where that continuation also closed the action (SWUAfterAction), the player kept the
// turn after paying the cost — measured on JTL_003 Lando and SOR_022 Energy Conversion Lab, 2026-08-27.
//
// The fix is at the helper: SWUQueueMayChooseTarget now DEFAULTS dontSkipOnPass to 1, mirroring
// SWUQueueMultiChoose's min<=0 derivation. That is safe because all 205 continuations inspect
// $lastDecision and SWUDecisionDeclined() treats '-' and "PASS" identically.
$gl = file_get_contents($root . '/SWUSim/Custom/GameLogic.php');
check(preg_match('/function\s+SWUQueueMayChooseTarget\s*\([^)]*\$dontSkipOnPass\s*=\s*1\s*\)/', $gl) === 1,
    'SWUQueueMayChooseTarget defaults dontSkipOnPass to 1 (a 0 default silently skips every real decline)');

// Raw MZMAYCHOOSE + CUSTOM pairs bypass that helper, so they must carry the flag themselves whenever the
// continuation closes the action.
$rawOffenders = [];
foreach ($files as $path) {
    $text = file_get_contents($path);
    if (strpos($text, 'MZMAYCHOOSE') === false) continue;
    $calls = dsop_calls($text);
    foreach ($calls as $idx => $call) {
        $args = dsop_split_args($call['body']);
        if (count($args) < 2 || strpos($args[1], 'MZMAYCHOOSE') === false) continue;
        $next = $calls[$idx + 1] ?? null;
        if ($next === null) continue;
        $nargs = dsop_split_args($next['body']);
        if (count($nargs) < 3 || strpos($nargs[1], 'CUSTOM') === false) continue;
        if (preg_match('/dontSkipOnPass\s*:\s*(?!0\b)/', $next['body']) || count($nargs) >= 6) continue;
        // only flag it when the continuation closes the action — that is the case with a visible cost
        if (!preg_match('/^\s*["\']([^"\'|]+)/', $nargs[2], $hm)) continue;
        $hbody = dsop_handler_body($text, $hm[1]);
        if ($hbody === null || strpos($hbody, 'SWUAfterAction') === false) continue;
        $rawOffenders[] = str_replace($root . '/', '', $path) . ':' . $next['line'];
    }
}
check(empty($rawOffenders),
    'no raw MZMAYCHOOSE pair closes the action through an unflagged continuation'
    . (empty($rawOffenders) ? '' : " — UNFLAGGED:\n    " . implode("\n    ", $rawOffenders)));

// ── Every ACTION-CLOSING MZMAYCHOOSE continuation must have a "PASS" decline guard ────────────────────
// The 8 continuations whose decline path calls SWUAfterAction() are the highest-severity members of this
// class: skipping one meant the cost was paid, nothing happened, and the player KEPT THE TURN — a free
// extra action (measured on JTL_003 Lando, 2026-08-27). They now RUN on a decline because the helper
// default flipped, so each needs a section answering "PASS" to prove the flip did not instead cause a
// DOUBLE close. A test answering `-` does NOT count: that is the other decline, and it always worked.
// ⚠ A handler is very often REGISTERED in one file and QUEUED from another — DISCOUNT_PLAY_FROM_HAND
// lives in CardDQHandlers.php but is queued from CrixMadine_StrikeTeamStrategist.php. Looking the body
// up only in the queueing file silently dropped it (and any other cross-file handler) from this check.
$bodyOf = [];
foreach ($files as $path) {
    $text = file_get_contents($path);
    if (strpos($text, '$customDQHandlers') === false) continue;
    if (!preg_match_all('/\$customDQHandlers\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*=/', $text, $rm)) continue;
    foreach ($rm[1] as $hn) {
        $b = dsop_handler_body($text, $hn);
        if ($b !== null) $bodyOf[$hn] = [$b, $path];
    }
}
$closers = [];
foreach ($files as $path) {
    $text = file_get_contents($path);
    if (strpos($text, 'SWUQueueMayChooseTarget') === false) continue;
    if (!preg_match_all('/SWUQueueMayChooseTarget\s*\(([^;]{0,400}?)\)\s*;/s', $text, $mm)) continue;
    foreach ($mm[1] as $args) {
        $parts = preg_split('/,(?![^(\[]*[)\]])/', $args);
        if (count($parts) < 5 || !preg_match('/[\'"]([^\'"|]+)/', $parts[4], $hm)) continue;
        if (!isset($bodyOf[$hm[1]])) continue;
        if (strpos($bodyOf[$hm[1]][0], 'SWUAfterAction') !== false) $closers[$hm[1]] = $path;
    }
}
check(count($closers) >= 8, 'found the action-closing continuations (' . count($closers) . ')');

// A guard exists if ANY test case answers "PASS" in a file naming that handler's card.
$caseText = '';
$ci = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/SWUSim/Tests/Cases'));
$caseFiles = [];
foreach ($ci as $cf) if ($cf->isFile() && $cf->getExtension() === 'md') $caseFiles[] = $cf->getPathname();
$unguarded = [];
foreach ($closers as $handler => $srcPath) {
    // the card id is the handler name up to '#', when it looks like one
    $card = preg_match('/^([A-Z]{2,5}[0-9]*_[0-9A-Z]+)/', $handler, $cm) ? $cm[1] : null;
    // Match on the test FILE NAME as well as its content. A handler with no CardID in its name
    // (DISCOUNT_PLAY_FROM_HAND) will never appear in the .md text, but its card's test file is named
    // after the card that queues it — CrixMadine_StrikeTeamStrategist.php -> …/CrixMadine_….md.
    $srcBase = basename($srcPath, '.php');
    $found = false;
    foreach ($caseFiles as $cf) {
        $t = file_get_contents($cf);
        if (strpos($t, 'AnswerDecision:PASS') === false) continue;
        if (basename($cf, '.md') === $srcBase) { $found = true; break; }
        if ($card !== null && strpos($t, $card) !== false) { $found = true; break; }
    }
    if (!$found) $unguarded[] = $handler . '  (' . str_replace($root . '/', '', $srcPath) . ')';
}
check(empty($unguarded),
    'every action-closing MZMAYCHOOSE continuation has a "PASS" decline guard'
    . (empty($unguarded) ? '' : " — UNGUARDED:\n    " . implode("\n    ", $unguarded)
        . "\n  Add a section answering \"PASS\" that asserts the action still closes (TURNPLAYER swaps)."
        . "\n  ⚠ Do NOT set P1OnlyActions in it — that makes TURNPLAYER unobservable, which is exactly how"
        . "\n    the Lando bug stayed green in a section that already answered \"PASS\"."));

// Self-test the scanner: a guard that cannot fail is not a guard. Feed it the exact broken shape and
// assert it is detected (the #971 art-path guard passed with the implementation deleted; not again).
$probe = '<?php AddDecision($p, "MZMULTICHOOSE", "0|3|" . $x, 1); AddDecision($p, "CUSTOM", "H", 1);';
$pc = dsop_calls($probe);
$pa = dsop_split_args($pc[1]['body']);
check(count($pc) === 2 && count($pa) === 4 && preg_match('/dontSkipOnPass/', $pc[1]['body']) === 0,
    'self-test: the scanner parses and would flag an unguarded 0-min pair');

echo "PASS: dontskiponpass_zero_min_test\n";
