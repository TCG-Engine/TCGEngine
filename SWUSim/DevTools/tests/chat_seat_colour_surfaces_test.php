<?php
// ── Chat colouring: the MESSAGE TEXT carries the sender's seat colour, on every chat surface ────────
//
// Owner, 2026-09-26, three changes:
//   1. "make the text a player sends the same color as their player color in that chat"
//   2. "include that text coloring for Whisper chats. keep the italics."
//   3. "for all public pieces of whispers, keep the backwash to a grey hue now"
//
// Before this, only the NAME was seat-coloured; the message body was default grey. And the whisper
// wash was rgba(160,110,255,…) — PURPLE, which is also P4's seat colour (#d79bff), so purple meant
// two unrelated things in one panel: "this is private" and "P4 said it".
//
// ⚠ THE PALETTE LIVES IN THREE SURFACES AND THEY DRIFT. This file exists as much for that as for the
// change itself: one panel, three stylesheets, and a fix applied to one of them looks complete.
//     SWUSim/Custom/GameLayout.php        --swu-chat-p1..p4  (desktop board — the only real tokens)
//     SWUSim/Custom/GameLayoutMobile.php  hardcoded hex      (mobile board)
//     SharedUI/Render/ChatPanel.php       hardcoded hex      (Waiting Room + Sideboard)
// plus SWUSim/Custom/GameLayoutShared.php for the whisper wash, which is shared by the two boards.
//
// ⚠ THIS IS A SOURCE SCAN AND IT CANNOT SEE SPECIFICITY. A rule can be present and still lose to a
// more specific one — which is exactly how the body text stayed grey while four seat rules sat right
// above it. The computed-style half is DevTools/ui-harness/swusim-chat-colours-xbrowser.mjs, in
// chromium + firefox + webkit. Neither half is sufficient; keep both.
//
//     php -d xdebug.mode=off SWUSim/DevTools/tests/chat_seat_colour_surfaces_test.php
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
chdir(realpath(__DIR__ . '/../../..'));

const SEAT_HEX = ['p1' => '#6fb8ff', 'p2' => '#ff9b6f', 'p3' => '#7fd88f', 'p4' => '#d79bff'];

$desktop = (string)file_get_contents('./SWUSim/Custom/GameLayout.php');
$mobile  = (string)file_get_contents('./SWUSim/Custom/GameLayoutMobile.php');
$panel   = (string)file_get_contents('./SharedUI/Render/ChatPanel.php');
$shared  = (string)file_get_contents('./SWUSim/Custom/GameLayoutShared.php');

// ── 1. THE PALETTE IS THE SAME IN ALL THREE SURFACES ────────────────────────────────────────────────
// The desktop board defines tokens; the other two hardcode. A seat whose colour is changed in one
// place and not the others is the standing failure mode here.
foreach (SEAT_HEX as $seat => $hex) {
    check(stripos($desktop, "--swu-chat-{$seat}:") !== false && stripos($desktop, $hex) !== false,
          "desktop board defines --swu-chat-{$seat} and still uses {$hex}");
    check(stripos($mobile, $hex) !== false, "mobile board still uses {$hex} for {$seat}");
    check(stripos($panel,  $hex) !== false, "Waiting Room / Sideboard panel still uses {$hex} for {$seat}");
}

// ── 2. THE ROW CARRIES A COLOUR, NOT JUST A RAIL (change 1) ─────────────────────────────────────────
// The body text is a bare span (board) or a bare text node (panel), so the only way to colour it is
// on the ROW. A rule that sets border-left-color alone leaves the message default-grey — which is
// precisely what shipped before.
foreach (array_keys(SEAT_HEX) as $seat) {
    // e.g. ".swu-log-CHAT.chatMsg-p1 { border-left-color: var(--swu-chat-p1); color: var(--swu-chat-p1); }"
    check(preg_match('/\.swu-log-CHAT\.chatMsg-' . $seat . '\s*\{[^}]*(?<![-\w])color\s*:/i', $desktop) === 1,
          "desktop: .chatMsg-{$seat} row sets a text colour, not only the rail");
    check(preg_match('/\.swu-log-CHAT\.chatMsg-' . $seat . '\s*\{[^}]*(?<![-\w])color\s*:/i', $mobile) === 1,
          "mobile: .chatMsg-{$seat} row sets a text colour, not only the rail");
    check(preg_match('/\.tcgc-row\.tcgc-' . $seat . '\s*\{[^}]*(?<![-\w])color\s*:/i', $panel) === 1,
          "panel: .tcgc-{$seat} row sets a text colour, not only the rail");
}

// ── 3. WHISPERS KEEP THE ITALICS AND INHERIT THE SEAT COLOUR (change 2) ─────────────────────────────
check(preg_match('/\.chatMsg-whisper\b[^{]*\{[^}]*font-style\s*:\s*italic/i', $shared) === 1,
      'a whisper row is still ITALIC — that is what distinguishes it now that the wash is quieter');
// The seat colour reaches whisper text by INHERITANCE from the row, so the whisper rule must not set
// its own colour and undo change 1.
check(preg_match('/\.chatMsg-whisper\b[^{]*\{[^}]*(?<![-\w])color\s*:/i', $shared) !== 1,
      'the whisper rule does NOT override the row colour — the seat colour must reach whisper text');

// ── 4. THE PUBLIC PIECE OF A WHISPER IS GREY (change 3) ─────────────────────────────────────────────
// The redacted stub ("P2 whispered something to P1 and P4") is the only part of a whisper everyone
// sees. Purple was doing double duty with P4's identity colour; grey matches the game-log rows, which
// is what a stub actually is — an announcement that a conversation happened.
if (preg_match('/\.chatMsg-whisperStub\b[^{]*\{([^}]*)\}/i', $shared, $m)) {
    $stub = $m[1];
    check(preg_match('/background\s*:[^;]*rgba\(\s*170\s*,\s*182\s*,\s*196/i', $stub) === 1,
          'the whisper STUB wash is the neutral log grey rgba(170,182,196,…)', trim($stub));
    check(preg_match('/background\s*:[^;]*rgba\(\s*160\s*,\s*110\s*,\s*255/i', $stub) !== 1,
          'and it is NOT the old purple rgba(160,110,255,…) that collided with P4', trim($stub));
} else {
    check(false, 'a .chatMsg-whisperStub rule exists at all');
}

// THE CONTROL. A readable whisper (sent/received) is NOT a public piece, so it keeps its own tint —
// without this, "make everything grey" would pass every assertion above.
if (preg_match('/\.chatMsg-whisper\b(?!Stub)[^{]*\{([^}]*)\}/i', $shared, $m)) {
    check(preg_match('/background\s*:/i', $m[1]) === 1,
          'a READABLE whisper still has its own background — only the public stub went grey', trim($m[1]));
} else {
    check(false, 'a .chatMsg-whisper rule exists at all');
}

// ── 5. THE STUB STILL NAMES ITS SENDER IN THE SENDER'S COLOUR ──────────────────────────────────────
// The stub's only child span is the generated "P2 whispered something to …" text, and it is matched by
// the per-seat first-child rule. Deleting those rules as "redundant" once the row carries a colour
// would hand the NAME back to the generic white-70% rule, which is more specific than inheritance.
foreach (array_keys(SEAT_HEX) as $seat) {
    check(preg_match('/\.swu-log-CHAT\.chatMsg-' . $seat . '\s*>\s*span:first-child/i', $desktop) === 1,
          "desktop: the per-seat NAME rule for {$seat} is still present (it outranks the generic one)");
}

echo $FAILS === 0 ? "\nALL PASS\n" : "\n{$FAILS} FAILED\n";
exit($FAILS === 0 ? 0 : 1);
