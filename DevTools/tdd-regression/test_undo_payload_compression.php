<?php
// TDD guard for the snapshot payload codec.
// WHY: snapshots are ~97.6% of Gamestate.txt (measured: 95 entries x ~7.6 KB of a 742 KB file) and the
// whole gamestate is rewritten on EVERY action, so payloads are deflate-compressed.
// The three traps this pins:
//   1. gzdeflate (raw DEFLATE), NOT gzencode — the gzip container stamps an mtime into its header, so
//      the same input yields DIFFERENT bytes each call, breaking determinism and the byte-exact
//      payload comparison in SWUComputeUndoTarget.
//   2. The format marker must NOT be a base64 character, or a legacy (unmarked) record could be
//      misread as a marked one. base64 is [A-Za-z0-9+/=]; '~' and '!' are outside it.
//   3. UndoRecordParse must return DECOMPRESSED bytes so existing callers are unaffected.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_payload_compression.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
include_once './SWUSim/Custom/UndoStack.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Realistic payload shape: repetitive, tab/newline/NUL-bearing serialized zones.
$payload = str_repeat("SOR_046|1|0|myGround-3<v0>", 400) . "\ttab\nnewline\x00nul";

$enc = UndoPayloadEncode($payload);
$check(UndoPayloadDecode($enc) === $payload, 'encode -> decode round-trips byte-exactly');
$check($enc[0] === '~', "compressed payloads carry the '~' marker (got '" . $enc[0] . "')");
$check(strlen($enc) < strlen($payload) / 2, 'compressed is <50% of raw (got ' . strlen($enc) . ' vs ' . strlen($payload) . ')');

// Determinism: identical input MUST produce identical bytes, or the no-op-skip compare breaks.
$check(UndoPayloadEncode($payload) === $enc, 'encoding is byte-stable across calls (the gzencode mtime trap)');

// Legacy records written before this change are bare base64 with no marker.
$legacy = base64_encode($payload);
$check(UndoPayloadDecode($legacy) === $payload, 'legacy unmarked base64 payload still decodes');

// A legacy payload whose base64 happens to start with 'z' or 'r' must not be mistaken for a marker.
// (This is why the markers are '~' and '!', which are outside the base64 alphabet entirely.)
$trickyRaw = "zzzz-payload";
$check(UndoPayloadDecode(base64_encode($trickyRaw)) === $trickyRaw, 'legacy payload decodes regardless of its first char');

// Empty payload.
$check(UndoPayloadDecode(UndoPayloadEncode('')) === '', 'empty payload round-trips');

// The record layer hides all of it: build -> parse returns the ORIGINAL bytes.
$rec = UndoRecordBuild(2, 'MAIN', 'action', 1, 'my label', $payload);
$p = UndoRecordParse($rec);
$check($p['payload'] === $payload, 'UndoRecordParse returns DECOMPRESSED payload');
$check($p['seat'] === 2 && $p['phase'] === 'MAIN' && $p['boundary'] === 'action'
    && $p['revealed'] === true && $p['name'] === 'my label', 'other record fields survive');
$check(strpos($rec, "\n") === false, 'record stays newline-free');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
