<?php
// Team Suns forbids a leader appearing twice on a TEAM. Identity is the CANONICAL CardID
// (CardIDOverride), never the title — six distinct Darth Vader leader printings exist, and a
// team fielding four of them is legal. Reprints must collapse, so a future leader reprint added
// to Overrides.php is respected with no change here.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/AppCore/SWU/DeckValidation.php';

// Four DIFFERENT Vader printings across the two teammates — all legal.
$fourVaders = SWUTeamLeaderConflicts([
    ['SOR_010', 'JTL_006'],
    ['LAW_011', 'IBH_053'],
]);
check($fourVaders === [], 'four different Darth Vader printings on one team is LEGAL');

// The same leader on both teammates — one conflict, reported by canonical ID.
$dupe = SWUTeamLeaderConflicts([
    ['SOR_010', 'JTL_006'],
    ['SOR_010', 'IBH_053'],
]);
check($dupe === ['SOR_010'], 'the same leader on both teammates is a conflict');

// Two conflicts are both reported, in first-seen order.
$both = SWUTeamLeaderConflicts([
    ['SOR_010', 'JTL_006'],
    ['SOR_010', 'JTL_006'],
]);
check($both === ['SOR_010', 'JTL_006'], 'every conflicting leader is reported, in order');

// REPRINTS COLLAPSE. C24_002 is a reprint of SOR_087 (Darth Vader - Commanding the First
// Legion) per Overrides.php, so they are the SAME leader and must conflict.
check(CardIDOverride('C24_002') === 'SOR_087', 'precondition: C24_002 canonicalizes to SOR_087');
$reprint = SWUTeamLeaderConflicts([
    ['SOR_087', 'JTL_006'],
    ['C24_002', 'IBH_053'],
]);
check($reprint === ['SOR_087'], 'a reprint of the same leader IS a conflict, reported canonically');

// Degenerate inputs must not warn or fatal.
check(SWUTeamLeaderConflicts([]) === [],                     'empty pool has no conflicts');
check(SWUTeamLeaderConflicts([[], []]) === [],               'seats with no leaders yet have no conflicts');
check(SWUTeamLeaderConflicts([['SOR_010'], []]) === [],      'a half-filled team has no conflicts');
check(SWUTeamLeaderConflicts([['', null], ['SOR_010']]) === [], 'empty/null leader entries are ignored');

echo "PASS\n";
