<?php
// ── The merged log+chat panel must look the SAME on the phone board as on the desktop board ─────────
//
// Owner, 2026-09-26, looking at the phone board: "Mobile needs some more love. the chat patterns did
// not carry over."
//
// GameLayoutMobile.php does NOT load GameLayout.php's CSS — it is a separate stylesheet with its own
// copy of everything it wants. It had copied the CHAT rules and nothing else, so on the phone:
//   • .swu-log-entry did not exist    → no row separators, and every game-log line fell back to the
//     panel's inherited near-white instead of --swu-log-default rgba(255,255,255,0.78). The log was
//     LOUDER than the chat, inverting the hierarchy the desktop panel is built on.
//   • the 14 per-type rules did not exist → PHASE/REVEAL/OVERWHELM lost their colours entirely.
//   • .swu-card-link did not exist    → card names rendered as flat text, not dotted-underlined links.
//
// ⚠ THIS IS THE "fix in ONE context, check the OTHERS" failure in its purest form: the seat-colour
// change landed in both files and still shipped half a panel, because the half that was missing had
// been missing all along. The guard is PARITY, not a list of rules — anything the desktop panel gains
// later must reach the phone too, and this test fails until it does.
//
//     php -d xdebug.mode=off SWUSim/DevTools/tests/log_panel_desktop_mobile_parity_test.php
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
chdir(realpath(__DIR__ . '/../../..'));

$desktop = (string)file_get_contents('./SWUSim/Custom/GameLayout.php');
$mobile  = (string)file_get_contents('./SWUSim/Custom/GameLayoutMobile.php');
check(strlen($desktop) > 10000 && strlen($mobile) > 10000, 'both stylesheets are readable');

/** Every distinct match of $pat, deduped. */
$all = function (string $s, string $pat): array {
    preg_match_all($pat, $s, $m);
    return array_values(array_unique($m[1] ?? []));
};

// ── 1. EVERY .swu-log-<TYPE> RULE THE DESKTOP PANEL HAS, THE PHONE PANEL HAS ────────────────────────
$dSel = $all($desktop, '/\.(swu-log-[A-Za-z0-9-]+)\s*[,{]/');
$mSel = $all($mobile,  '/\.(swu-log-[A-Za-z0-9-]+)\s*[,{]/');
$missingSel = array_values(array_diff($dSel, $mSel));
check($missingSel === [],
      'the phone panel styles every .swu-log-* rule the desktop panel does', $missingSel);

// ── 2. AND EVERY --swu-log-* TOKEN, WITH THE SAME VALUE ─────────────────────────────────────────────
// Same value matters as much as same name: a phone-only tint is drift that no screenshot will catch
// until someone puts the two boards side by side.
$decls = function (string $s): array {
    preg_match_all('/(--swu-log-[a-z0-9-]+)\s*:\s*([^;]+);/i', $s, $m, PREG_SET_ORDER);
    $out = [];
    foreach ($m as $d) $out[trim($d[1])] = preg_replace('/\s+/', ' ', trim($d[2]));
    return $out;
};
$dVar = $decls($desktop);
$mVar = $decls($mobile);
$missingVar = array_values(array_diff(array_keys($dVar), array_keys($mVar)));
check($missingVar === [], 'the phone panel defines every --swu-log-* token the desktop panel does', $missingVar);

$drifted = [];
foreach ($dVar as $k => $v) {
    if (isset($mVar[$k]) && $mVar[$k] !== $v) $drifted[] = "{$k}: desktop '{$v}' vs phone '{$mVar[$k]}'";
}
check($drifted === [], 'and gives each of them the SAME value', $drifted);

// ── 3. THE ROW ITSELF: separator + default colour (the two things the screenshot showed missing) ────
if (preg_match('/\.swu-log-entry\s*\{([^}]*)\}/', $mobile, $m)) {
    $entry = preg_replace('/\s+/', ' ', trim($m[1]));
    check(preg_match('/border-bottom\s*:/i', $entry) === 1,
          'phone: .swu-log-entry draws the row SEPARATOR (a wall of text needs it most on a phone)', $entry);
    check(preg_match('/(?<![-\w])color\s*:/i', $entry) === 1,
          'phone: .swu-log-entry sets the default log colour, so log lines are QUIETER than chat', $entry);
} else {
    check(false, 'phone: a .swu-log-entry rule exists at all');
}
check(preg_match('/\.swu-log-entry:last-child\s*\{[^}]*border-bottom\s*:\s*0/i', $mobile) === 1,
      'phone: the LAST row has no trailing rule (it reads as a message that failed to render)');

// ── 4. CARD LINKS. "Rebel Trooper" must look clickable on a phone too. ──────────────────────────────
if (preg_match('/\.swu-card-link\s*\{([^}]*)\}/', $mobile, $m)) {
    $link = preg_replace('/\s+/', ' ', trim($m[1]));
    check(preg_match('/text-decoration\s*:[^;]*underline/i', $link) === 1,
          'phone: .swu-card-link is underlined', $link);
    check(preg_match('/text-decoration-style\s*:\s*dotted/i', $link) === 1,
          'phone: …with the same DOTTED style as the desktop board', $link);
    check(preg_match('/(?<![-\w])color\s*:\s*inherit/i', $link) === 1,
          'phone: …and inherits its row colour, so a link inside a chat line stays the seat colour', $link);
} else {
    check(false, 'phone: a .swu-card-link rule exists at all');
}

// ── 5. THE CONTROL. Parity must not be achieved by flattening the phone board into the desktop one —
// the phone keeps its own panel geometry, which is the whole reason it is a separate stylesheet.
check(preg_match('/#swuLogPanel\s*\{[^}]*max-height\s*:\s*\d+vh/i', $mobile) === 1,
      'phone: #swuLogPanel keeps its own vh cap — parity is about the ROWS, not the panel box');
// ⚠ Match the USE, not the name: GameLayoutMobile.php mentions --swu-chat-p1..p4 in a comment that
// points at the desktop tokens it is copying from, and a bare substring check fails on that comment.
check(preg_match('/var\(\s*--swu-chat-p\d/i', $mobile) !== 1,
      'phone: the seat hexes stay literal here (it cannot READ the desktop board\'s tokens)');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n{$FAILS} FAILED\n";
exit($FAILS === 0 ? 0 : 1);
