<?php
// Test: SWUDecisionDeclined mirrors the inline predicate exactly.
require __DIR__ . '/../../Custom/CardHelpers.php';

// Declined forms.
assert(SWUDecisionDeclined(null) === true, 'null');
assert(SWUDecisionDeclined('') === true, 'empty');
assert(SWUDecisionDeclined('-') === true, 'dash');
assert(SWUDecisionDeclined('PASS') === true, 'pass');
assert(SWUDecisionDeclined(false) === true, 'false');
assert(SWUDecisionDeclined('0') === true, 'string-zero (matches !$d)');

// Real answers (not declined).
assert(SWUDecisionDeclined('myGroundArena-0') === false, 'mzID');
assert(SWUDecisionDeclined('SOR_033') === false, 'cardid');
assert(SWUDecisionDeclined('1') === false, 'one');

// --- SWUObjUID ---
$o = (object)['UniqueID' => 7];
assert(SWUObjUID($o) === 7, 'obj uid');
assert(SWUObjUID($o, 0) === 7, 'obj uid ignores default when present');
assert(SWUObjUID(null) === -1, 'null default -1');
assert(SWUObjUID(null, 0) === 0, 'null default 0');
assert(SWUObjUID(false, 0) === 0, 'false default 0');
$noUid = (object)[];                                  // missing UniqueID prop
assert(SWUObjUID($noUid) === -1, 'missing uid default -1');
assert(SWUObjUID($noUid, 0) === 0, 'missing uid default 0');
$strUid = (object)['UniqueID' => '9'];                // intval() coercion
assert(SWUObjUID($strUid) === 9, 'string uid coerced');

// --- SWUObjGone ---
assert(SWUObjGone(null) === true, 'gone null');
assert(SWUObjGone((object)['removed' => 1]) === true, 'gone removed=1');
assert(SWUObjGone((object)['removed' => true]) === true, 'gone removed=true');
assert(SWUObjGone((object)['removed' => 0]) === false, 'live removed=0');
assert(SWUObjGone((object)['removed' => '']) === false, 'live removed empty');
assert(SWUObjGone((object)[]) === false, 'live no removed prop');

echo "OK\n";
