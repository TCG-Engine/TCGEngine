<?php
// ── The ELIMINATED log line is RED, on every board surface ──────────────────────────────────────
//
// Owner, 2026-09-26: "add a red log when someone gets really eliminated that says '<player>
// eliminated! Game will end at the end of this phase'". A real elimination puts the whole game on a
// timer (CR 12.7.1 — it ends at the end of the current phase and the highest remaining base HP
// wins), and in game 1311538 the removal and the abrupt ending were three turns apart with nothing
// on screen connecting them.
//
// ⚠ THE LOG PALETTE LIVES IN TWO SURFACES AND THEY DRIFT. Same trap as the chat palette next door
// (chat_seat_colour_surfaces_test.php, which found one panel spread over three stylesheets):
//     SWUSim/Custom/GameLayout.php        --swu-log-*  (desktop board)
//     SWUSim/Custom/GameLayoutMobile.php  --swu-log-*  (mobile board)
// A fix applied to one of them looks complete on the machine you tested it on.
//
// ⚠ THIS IS A SOURCE SCAN AND IT CANNOT SEE SPECIFICITY — a rule can be present and still lose to a
// more specific one. The computed-style half is
// DevTools/ui-harness/swusim-log-eliminated-xbrowser.mjs (chromium + firefox + webkit).
// Neither half is sufficient; keep both.
//
//     docker exec -w /var/www/html/TCGEngine <c> php -d xdebug.mode=off SWUSim/DevTools/tests/log_eliminated_colour_surfaces_test.php
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
chdir(realpath(__DIR__ . '/../../..'));

const ELIM_RED = '#e05050';
$surfaces = [
    'desktop (GameLayout.php)'      => './SWUSim/Custom/GameLayout.php',
    'mobile  (GameLayoutMobile.php)'=> './SWUSim/Custom/GameLayoutMobile.php',
];

foreach ($surfaces as $label => $path) {
    $css = (string)file_get_contents($path);
    check(str_contains($css, '--swu-log-eliminated:'), "$label defines --swu-log-eliminated");
    // The token must be an actual RED, not silently aliased to the default grey the way every
    // un-tinted type is (`--swu-log-defeat: var(--swu-log-default)`), which would read as "wired up"
    // while rendering identically to every other line.
    if (preg_match('/--swu-log-eliminated:\s*([^;]+);/', $css, $m)) {
        $val = trim($m[1]);
        check(stripos($val, 'var(--swu-log-default') === false,
            "$label does not alias it to the default grey", $val);
        check(strcasecmp($val, ELIM_RED) === 0, "$label uses " . ELIM_RED, $val);
    } else {
        check(false, "$label declaration is parseable");
    }
    check(preg_match('/\.swu-log-ELIMINATED\s*\{[^}]*color:\s*var\(--swu-log-eliminated\)/', $css) === 1,
        "$label has a .swu-log-ELIMINATED rule reading that token");

    // The counterpart type. Owner, 2026-09-26: "administrative exits can be neutral … only genuine
    // eliminations should be red to create the urgency of the end game." Here the alias to the
    // default IS the requirement, which is the exact opposite of the token above — so assert it,
    // rather than leaving the neutral case to the absence of a rule, where a later edit could
    // quietly give it a colour and no test would notice.
    check(str_contains($css, '--swu-log-removed:'), "$label defines --swu-log-removed");
    if (preg_match('/--swu-log-removed:\s*([^;]+);/', $css, $m2)) {
        check(stripos(trim($m2[1]), 'var(--swu-log-default') !== false,
            "$label keeps --swu-log-removed neutral (the default)", trim($m2[1]));
    } else {
        check(false, "$label --swu-log-removed declaration is parseable");
    }
    check(preg_match('/\.swu-log-REMOVED\s*\{[^}]*color:\s*var\(--swu-log-removed\)/', $css) === 1,
        "$label has a .swu-log-REMOVED rule reading that token");
}

// The class the client actually builds is 'swu-log-' + the entry TYPE, with no whitelist
// (GameLayoutShared.php), so the rule name has to match the type string the engine writes EXACTLY —
// including its case. Assert the producer and the consumer agree rather than trusting either alone.
$engine = (string)file_get_contents('./SWUSim/Custom/GameLogic.php');
check(str_contains($engine, "\$genuine ? 'ELIMINATED' : 'REMOVED'"),
    "the engine picks the log type from whether the elimination was GENUINE");
check(str_contains($engine, 'The game will end at the end of this phase'),
    'the engine writes the phase-end warning the owner asked for');
$shared = (string)file_get_contents('./SWUSim/Custom/GameLayoutShared.php');
check(str_contains($shared, "'swu-log-entry swu-log-' + type"),
    'the client still derives the class from the raw type (no whitelist to add it to)');

echo "\n" . ($FAILS === 0 ? "ALL PASS\n" : "$FAILS FAILED\n");
exit($FAILS === 0 ? 0 : 1);
