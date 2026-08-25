<?php
// The teamsuns format registry entry, and the predicates that replace every literal
// `format === 'twinsuns'` gate in the lobby.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/AppCore/SWU/Formats.php';

$ts = SWUGetFormat('teamsuns');
check(is_array($ts),                       'teamsuns is a registered format');
check($ts['enabled'] === true,             'teamsuns is enabled');
check($ts['displayName'] === 'Team Suns',  'teamsuns display name is "Team Suns"');

// Shares the Twin Suns deckbuilding rule block verbatim.
$tw = SWUGetFormat('twinsuns');
check($ts['minDeck']     === $tw['minDeck'],     'teamsuns inherits Twin Suns minDeck (80)');
check($ts['maxCopies']   === $tw['maxCopies'],   'teamsuns inherits Twin Suns maxCopies (1)');
check($ts['leaderCount'] === $tw['leaderCount'], 'teamsuns inherits Twin Suns leaderCount (2)');
check($ts['legalSets']   === $tw['legalSets'],   'teamsuns inherits the Twin Suns set pool');

// Team markers.
check($ts['teams'] === 2,                  'teamsuns declares 2 teams');
check(!empty($ts['uniqueTeamLeaders']),    'teamsuns forbids a leader appearing twice on a team');

// Seat ranges. Team Suns is STRICTLY 4P; Twin Suns still allows 3.
check(SWUFormatSeatRange('teamsuns') === [4, 4], 'teamsuns seats exactly 4');
check(SWUFormatSeatRange('twinsuns') === [3, 4], 'twinsuns still allows 3 or 4');
check(SWUFormatSeatRange('premier')  === [2, 2], 'a normal format defaults to 2 seats');

// Predicates that replace the literal gates.
check(SWUFormatIsRoomFormat('teamsuns') === true,  'teamsuns is a room format');
check(SWUFormatIsRoomFormat('twinsuns') === true,  'twinsuns is STILL a room format (no regression)');
check(SWUFormatIsRoomFormat('premier')  === false, 'premier is not a room format');
check(SWUFormatIsTeamFormat('teamsuns') === true,  'teamsuns is a team format');
check(SWUFormatIsTeamFormat('twinsuns') === false, 'twinsuns is NOT a team format');

// SubmitGameResult rejects an unregistered format outright — this must pass or Team Suns
// results 400 at the stats endpoint.
check(SWUFormatIsRegistered('teamsuns'), 'teamsuns is registered for SubmitGameResult');
check(in_array('teamsuns', array_keys(SWUListFormats()), true),
      'teamsuns appears in the format selector');

// The stale comment must be gone — it claims 12.2.1.a is unimplemented, which is false
// (DeckValidation.php:189-194 implements it) and it sits on the block teamsuns copies.
$src = file_get_contents($root . '/AppCore/SWU/Formats.php');
check(strpos($src, 'NOT YET ENFORCED') === false,
      'the stale "NOT YET ENFORCED" comment is deleted');

echo "PASS\n";
