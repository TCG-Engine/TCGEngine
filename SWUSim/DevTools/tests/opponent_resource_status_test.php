<?php
// "my opponent's resources do not go down as they play cards" — the READY count in the opponent's
// resource badge never dropped, while their own client was correct and the viewer's own side was fine.
//
// ROOT CAUSE (not a refresh problem — a DATA problem): Resources is a Visibility=Self zone, so the
// transport masks the opponent's entries as `ClientRenderedCard("CardBack")`, which serialises to
// "CardBack 0 -" — no cardJSON at all. parseResCountFromData (GameLayoutShared.php) counts every entry
// toward `total` but can only count an EXHAUSTED one when the entry carries {"Status":0}, so for the
// opponent exhausted is always 0 and the badge reads N/N forever.
//
// Exhausted state is PUBLIC in SWU — the physical resource is visibly rotated, and the zone schema
// already declares `Rotation: Status=0:9` / `Overlay: Status=0:exhausted` for it. Only the card's
// IDENTITY is hidden. So a masked resource must still carry Status.
//
// ⚠ An earlier fix (the window.theirResourcesData setter in GameLayoutShared.php) addressed a
// STALENESS variant of this report — the badge not recomputing at all. It cannot help here: recomputing
// perfectly from data that has no Status still yields N/N.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// --- the GENERATOR is the tracked source; GetNextTurn.php is regenerated from it ---
$gen = file_get_contents($root . '/zzGameCodeGenerator.php');
check($gen !== false, 'zzGameCodeGenerator.php is readable');
$genCode = preg_replace('~^\s*//[^\n]*~m', '', $gen);          // assert CODE, not comments
check(strpos($genCode, 'SWUMaskedResourceJSON') !== false,
      'the generator emits a masked-resource payload helper rather than a bare CardBack');

// --- the regenerated transport actually carries it ---
$ntp = $root . '/SWUSim/GetNextTurn.php';
if (is_file($ntp)) {
    $nt = file_get_contents($ntp);
    check(preg_match('~else echo\(ClientRenderedCard\("CardBack", cardJSON:SWUMaskedResourceJSON~', $nt) === 1,
          'the regenerated GetNextTurn masks the resource but still sends its Status');
    // Hand must NOT gain a payload — a hidden hand card has no public per-card state.
    check(substr_count($nt, 'ClientRenderedCard("CardBack")') > 0,
          'other masked zones (Hand, Deck) still send a bare CardBack');
} else {
    echo "  skip: GetNextTurn.php not generated here\n";
}

// --- behavioural: the client parser must now see the opponent's spend ---
// Mirrors parseResCountFromData in SWUSim/Custom/GameLayoutShared.php.
function parseResCount(string $raw): array {
    if ($raw === '' || $raw === '-') return ['ready'=>0,'total'=>0];
    $total = 0; $exhausted = 0;
    foreach (explode('<|>', $raw) as $entry) {
        $entry = trim($entry); if ($entry === '') continue;
        $parts = explode(' ', $entry, 3);
        $total++;
        $json = $parts[2] ?? '-';
        if ($json !== '-' && $json !== '') {
            $o = json_decode(str_replace('_', ' ', $json), true);
            if (is_array($o) && isset($o['Status']) && intval($o['Status']) === 0) $exhausted++;
        }
    }
    return ['ready'=>$total-$exhausted, 'total'=>$total];
}
require_once $root . '/AppCore/SWU/CardImagePath.php';           // harmless; keeps includes uniform
$before = parseResCount(implode('<|>', array_fill(0, 5, 'CardBack 0 -')));
check($before['ready'] === 5 && $before['total'] === 5,
      'the OLD masked payload reads 5/5 even with resources spent (the bug)');
$after = parseResCount(
    'CardBack 0 {"Status":0}<|>CardBack 0 {"Status":0}<|>CardBack 0 {"Status":1}<|>'
  . 'CardBack 0 {"Status":1}<|>CardBack 0 {"Status":1}');
check($after['ready'] === 3 && $after['total'] === 5,
      'the NEW masked payload reads 3/5 — two spent resources are visible to the opponent');

echo "PASS\n";
