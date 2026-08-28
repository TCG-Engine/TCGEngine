<?php
// ── A RAW SPACE IN AddDecision()'s $param SILENTLY CORRUPTS THE DECISION ROW ─────────────────────────
//
// A DecisionQueue row is stored as ONE space-delimited string:
//     "$type $param $block $tooltip $dontSkipOnPass"
// and ZoneClasses' constructor reads it back with explode(" ", $line) — Param at index 1, Block at 2,
// Tooltip at 3, DontSkipOnPass at 4. AddDecision (Core/DecisionQueueController.php) underscores the
// TOOLTIP for exactly this reason, and its own comment says $param is deliberately NOT sanitised: a
// space there is unrecoverable rather than cosmetic, because nothing can tell it from the separator.
//
// So a call like
//     AddDecision($p, "OPTIONCHOOSE", "Ready a resource&Exhaust a unit", 1, "…")
// does not store that string. It stores Param="Ready", Block="a", Tooltip="resource&Exhaust", and the
// rest shifts off the end.
//
// MEASURED LIVE (2026-08-27), SOR_189 Leia Organa, Defiant Princess — "When Played: Either ready a
// resource or exhaust a unit." The real client rendered ONE button reading "Ready"; the answer came back
// as "Ready", the handler's `=== "Ready a resource"` test failed, and it fell through to the ELSE branch.
// The ready-a-resource half of a MANDATORY either/or was unreachable in play: Leia always exhausted.
//
// The schema suite could not see it, because a test can answer the uncorrupted label directly. Two things
// close that: SWUValidateDecisionAnswer now pool-checks OPTIONCHOOSE answers, and this file guards the
// SHAPE so no new call site can reintroduce it.
//
// THE FIX AT A CALL SITE: underscore the labels in $param and compare against the underscored form. The
// client already renders underscores as spaces at every prompt site (Tooltip.replace(/_/g,' ')).
//
// The DYNAMIC half matters just as much: `"Play_" . CardTitle($cid) . "_for_free"` puts prose straight
// into the row, and card titles are full of spaces. That cannot be settled by looking at the string, so
// this file uses an ALLOWLIST instead — every function and every variable appearing in a $param
// expression must be one whose output provably cannot contain a space (mzIDs, SET_NNN CardIDs, integers,
// and the picker helpers, which build underscored labels themselves). Today the whole codebase passes
// with zero offenders; the point is that the first `CardTitle()` written into a param fails immediately.
// To add a new safe source, add it to $SAFE_CALLS below with a one-line reason.

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = realpath(__DIR__ . '/../../..');

/**
 * Every AddDecision() call's 3rd argument that is a plain string literal containing a raw space.
 * Argument splitting is done on the token stream, so nesting and strings-with-commas are safe, and
 * comments are excluded by construction (token_get_all keeps them as their own tokens).
 */
function adps_scan(string $src, string $label): array {
    $hits = [];
    $toks = token_get_all($src);
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        if (!is_array($t) || $t[0] !== T_STRING || $t[1] !== 'AddDecision') continue;
        $line = $t[2];
        $j = $i + 1;
        while ($j < $n && $toks[$j] !== '(') {
            if (is_array($toks[$j]) && $toks[$j][0] !== T_WHITESPACE) break;   // not a call
            $j++;
        }
        if ($j >= $n || $toks[$j] !== '(') continue;
        $depth = 0; $arg = 0;
        for ($k = $j; $k < $n; $k++) {
            $tk = $toks[$k];
            if ($tk === '(') { $depth++; continue; }
            if ($tk === ')') { $depth--; if ($depth === 0) break; continue; }
            if ($depth === 1 && $tk === ',') { $arg++; continue; }
            if ($depth !== 1 || $arg !== 2) continue;
            if (is_array($tk) && $tk[0] === T_CONSTANT_ENCAPSED_STRING
                    && strpos(substr($tk[1], 1, -1), ' ') !== false) {
                $hits[] = "$label:$line  " . trim($tk[1]);
            }
        }
    }
    return $hits;
}

// Functions whose result provably cannot contain a raw space.
$SAFE_CALLS = [
    'implode'      => 'joins mzIDs / SET_NNN ids, none of which contain a space',
    'intval'       => 'an integer',
    'count'        => 'an integer',
    'min'          => 'an integer',
    'max'          => 'an integer',
    'array_values' => 'reindex only — the members are checked by whatever built them',
    'array_keys'   => 'mzID keys',
    'array_map'    => 'element-wise; the mapped values are ids',
    'array_slice'  => 'subset only',
    'UniqueID'     => 'property fetch — an integer',
    'CardID'       => 'property fetch — SET_NNN',
    'SWUPlayerPickerLabels' => 'builds underscored labels itself',
    'SWUDeckPickerLabels'   => 'builds underscored labels itself',
    '_SWUEncodeHits'        => 'encodes uid:amount pairs',
    '_SWUEncodeDamageSource' => 'returns U<uid> / M<mzID> / empty — a UniqueID or an mzID, never prose',
    'OtherPlayer'           => 'a seat number',
    'str_replace'           => "the underscore wrapper itself — that is the FIX, so it is safe by construction",
];
// Variable names that read as PROSE. A param is a machine token, never a sentence.
$PROSE_VARS = ['title', 'name', 'label', 'text', 'prompt', 'tooltip', 'question', 'desc', 'description', 'msg', 'message'];

/** Functions and prose-looking variables used inside each AddDecision $param expression. */
function adps_dynamic(string $src, string $label, array $safe, array $proseVars): array {
    $bad = [];
    $toks = token_get_all($src);
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        if (!is_array($t) || $t[0] !== T_STRING || $t[1] !== 'AddDecision') continue;
        $line = $t[2];
        $j = $i + 1;
        while ($j < $n && $toks[$j] !== '(') {
            if (is_array($toks[$j]) && $toks[$j][0] !== T_WHITESPACE) break;
            $j++;
        }
        if ($j >= $n || $toks[$j] !== '(') continue;
        $depth = 0; $arg = 0; $expr = ''; $hits = [];
        for ($k = $j; $k < $n; $k++) {
            $tk = $toks[$k];
            if ($tk === '(') { $depth++; if ($depth === 1) continue; }
            if ($tk === ')') { $depth--; if ($depth === 0) break; }
            if ($depth === 1 && $tk === ',') { $arg++; continue; }
            if ($arg !== 2) continue;
            if (is_array($tk)) {
                $expr .= $tk[1];
                if ($tk[0] === T_STRING && !isset($safe[$tk[1]])) $hits[] = $tk[1] . '()';
                if ($tk[0] === T_VARIABLE) {
                    // Match the WHOLE name, or a camelCase / underscore-delimited TAIL ($cardTitle,
                    // $card_title) — never a bare substring suffix. A case-insensitive suffix test reads
                    // "$context" as "…text" and flagged SOR_016, whose param only ever carries a machine
                    // token, so the camelCase arm is deliberately CASE-SENSITIVE.
                    $vn = ltrim($tk[1], '$');
                    foreach ($proseVars as $pv) {
                        $whole  = strtolower($vn) === $pv;
                        $camel  = strlen($vn) > strlen($pv)
                               && substr($vn, -strlen($pv)) === ucfirst($pv)
                               && preg_match('/[a-z0-9]/', $vn[strlen($vn) - strlen($pv) - 1]);
                        $snake  = strlen($vn) > strlen($pv) + 1
                               && strtolower(substr($vn, -strlen($pv) - 1)) === '_' . $pv;
                        if ($whole || $camel || $snake) { $hits[] = $tk[1]; break; }
                    }
                }
            } else $expr .= $tk;
        }
        if (!empty($hits)) {
            $bad[] = "$label:$line  " . implode(', ', array_unique($hits))
                   . '  in: ' . preg_replace('/\s+/', ' ', trim($expr));
        }
    }
    return $bad;
}

// Per-site exemptions. A variable can be provably space-free without the scanner being able to see it —
// but "provably" means a NORMALISATION AT THE SOURCE, not a convention among today's callers. Each entry
// names the file:line and the reason. Anything else must be underscored where it is built.
$VETTED = [
    // _SWUQueueFriendlyResourceDefeatStage() normalises $label with str_replace(' ', '_', …) on entry,
    // so both of its AddDecision sites receive an already-underscored token whatever the caller passed.
    'SWUSim/Custom/GameLogic.php' => ['$label'],
];

$offenders = [];
$dynamic   = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/SWUSim/Custom'));
$files = [];
foreach ($it as $f) if ($f->isFile() && $f->getExtension() === 'php') $files[] = $f->getPathname();
sort($files);
foreach ($files as $file) {
    $src = file_get_contents($file);
    $rel = str_replace($root . '/', '', $file);
    $offenders = array_merge($offenders, adps_scan($src, $rel));
    $found = adps_dynamic($src, $rel, $SAFE_CALLS, $PROSE_VARS);
    if (isset($VETTED[$rel])) {
        $found = array_values(array_filter($found, function ($row) use ($VETTED, $rel) {
            foreach ($VETTED[$rel] as $v) if (strpos($row, '  ' . $v . '  ') !== false) return false;
            return true;
        }));
    }
    $dynamic = array_merge($dynamic, $found);
}
$offenders = array_values(array_unique($offenders));
$dynamic   = array_values(array_unique($dynamic));

check(empty($offenders),
    'no AddDecision() $param literal contains a raw space (' . count($files) . ' files scanned)'
    . (empty($offenders) ? '' : " — OFFENDERS:\n    " . implode("\n    ", $offenders)
        . "\n  Underscore the labels; the client renders '_' as a space."));

check(empty($dynamic),
    'every AddDecision() $param expression is built only from space-free sources'
    . (empty($dynamic) ? '' : " — UNVETTED:\n    " . implode("\n    ", $dynamic)
        . "\n  Wrap prose in str_replace(' ', '_', …), or add the source to \$SAFE_CALLS with a reason."));

// Self-test: a guard that cannot fail is not a guard. Feed it the exact SOR_189 shape and the fixed one.
$bad  = '<?php DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Ready a resource&Exhaust a unit", 1, "T");';
$good = '<?php DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Ready_a_resource&Exhaust_a_unit", 1, "T");';
check(count(adps_scan($bad, 'probe')) === 1, 'self-test: the scanner flags the spaced label');
check(count(adps_scan($good, 'probe')) === 0, 'self-test: the scanner accepts the underscored label');
// And that it reads the PARAM, not the tooltip — a spaced tooltip is legal (AddDecision underscores it).
$tip = '<?php DecisionQueueController::AddDecision($p, "YESNO", "-", 1, "Play this for free?");';
check(count(adps_scan($tip, 'probe')) === 0, 'self-test: a spaced TOOLTIP is not flagged (arg 5, not arg 3)');

// …and the dynamic half, both directions.
$prose = '<?php DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Play_" . CardTitle($cid) . "_free", 1, "T");';
check(count(adps_dynamic($prose, 'probe', $SAFE_CALLS, $PROSE_VARS)) === 1,
    'self-test: a CardTitle() in the param is flagged');
$wrapped = '<?php DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Play_" . str_replace(\' \', \'_\', CardTitle($cid)) . "_free", 1, "T");';
check(count(adps_dynamic($wrapped, 'probe', $SAFE_CALLS, $PROSE_VARS)) === 1,
    'self-test: str_replace does NOT whitelist the CardTitle inside it (the wrapper is safe, the source still needs vetting)');
$safe = '<?php DecisionQueueController::AddDecision($p, "MZCHOOSE", implode("&", $targets), 1, "T");';
check(count(adps_dynamic($safe, 'probe', $SAFE_CALLS, $PROSE_VARS)) === 0,
    'self-test: implode() over mzIDs is accepted');
$prosevar = '<?php DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Play_" . $cardTitle, 1, "T");';
check(count(adps_dynamic($prosevar, 'probe', $SAFE_CALLS, $PROSE_VARS)) === 1,
    'self-test: a prose-looking VARIABLE in the param is flagged');
// The TOOLTIP is arg 5 and is sanitised by AddDecision, so nothing there is ever flagged.
$tipdyn = '<?php DecisionQueueController::AddDecision($p, "YESNO", "-", 1, "Play_" . CardTitle($cid) . "?");';
check(count(adps_dynamic($tipdyn, 'probe', $SAFE_CALLS, $PROSE_VARS)) === 0,
    'self-test: prose in the TOOLTIP is not flagged (AddDecision underscores it)');

echo "PASS: adddecision_param_space_test\n";
