<?php
// The TOPDECKSEARCH panel is shared by every top-deck search in the game (~65 call sites), and the thing
// that decides WHICH cards are legal is a PHP closure — it cannot cross the request boundary, so the
// client has nothing to describe the selection with unless the caller sends the words.
//
// That gap was filled, once, by hardcoding the wording of the panel's only caller of the day: SOR_087
// Darth Vader, "any number of Villainy units with combined cost 3 or less". Six more cost-budget callers
// arrived afterwards and every one of them inherited Vader's sentence — ASH_110 Admiral Ackbar searches
// for SPACE units and told the player to select Villainy ones; LAW_063 L3-37 (Droid), HMW_265 Twi'lek
// Kalikori (Twi'lek), SOR_104 U-Wing Reinforcement (any unit), SHD_123 Bounty Hunter's Quarry and
// LOF_117 Sifo-Dyas (which DISCARDS its picks) likewise. The confirm button had the same shape: a fixed
// "Take N cards" over callers that play or discard.
//
// The fix makes the wording a REQUIRED argument of the server-side funnel and carries it on the wire as
// param segments 4 (label) and 5 (verb). This test guards both halves of that contract statically,
// because the failure mode is not a crash and not a wrong game state — it is a sentence that is simply
// untrue, which no gameplay assertion can see. The behavioural half is
// Tests/Cases/ash/AdmiralAckbar_AssumeAttackCoordinates.md::SearchPanelNamesSpaceUnits_NotVillainy.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// ── Client half ────────────────────────────────────────────────────────────────────────────────────
// Newest bundle, the same way the loader picks it, so a cache-bust rename never leaves this reading a
// stale copy.
$bundles = glob($root . '/Core/UILibraries[0-9]*.js');
check(!empty($bundles), 'a Core/UILibraries<date>.js bundle exists');
rsort($bundles);
$js = file_get_contents($bundles[0]);
check($js !== false && $js !== '', basename($bundles[0]) . ' is readable');

$start = strpos($js, 'function ShowTopDeckSearchPanel(');
check($start !== false, 'ShowTopDeckSearchPanel is present');
$end  = strpos($js, "\nfunction ", $start + 10);
$body = substr($js, $start, ($end === false ? strlen($js) : $end) - $start);
// Assert CODE, not prose: this file's own explanation names Villainy and Vader repeatedly, and so does
// the panel's comment. A guard that reads comments passes on a comment.
$code = preg_replace('~//[^\n]*~', '', $body);

check(strpos($code, 'parts[4]') !== false && strpos($code, 'parts[5]') !== false,
      'the panel reads the label and verb from the decision param');
check(preg_match('~subtitle\.textContent\s*=[^;]*pickLabel~', $code) === 1,
      'the subtitle is built from the caller-supplied label');
check(preg_match('~confirmBtn\.textContent\s*=[^;]*pickVerb~s', $code) === 1,
      'the confirm button is built from the caller-supplied verb');

// The regression itself: no card-specific word may appear in the panel's own strings. These are the
// filters the cost-budget callers actually use, which is exactly the set that got baked in before.
foreach (['Villainy', 'Droid', "Twi'lek", 'Clone', 'space unit', 'Vehicle', 'Rebel', 'Imperial'] as $word) {
    check(stripos($code, $word) === false,
          "the panel hardcodes no card-specific wording (\"{$word}\")");
}
// And no fixed verb on the commit button.
check(preg_match("~['\"]Take\s~", $code) !== 1, 'the confirm button no longer hardcodes "Take"');

// ── Server half ────────────────────────────────────────────────────────────────────────────────────
$gl = file_get_contents($root . '/SWUSim/Custom/GameLogic.php');
check($gl !== false && $gl !== '', 'SWUSim/Custom/GameLogic.php is readable');

// REQUIRED, not defaulted — a default is how one card's wording became everyone's. If these ever gain
// "= ''" the compiler stops catching a caller that forgot to say what it is searching for.
check(preg_match('~function _topDeckSearchBegin\([^)]*string \$label, string \$verb\)~s', $gl) === 1,
      '_topDeckSearchBegin takes $label and $verb as REQUIRED parameters');
check(preg_match('~function DoTopDeckSearch\([^)]*string \$label\)~s', $gl) === 1,
      'DoTopDeckSearch takes $label as a REQUIRED parameter');
check(preg_match('~function DoTopDeckPlay\([^)]*string \$label,~s', $gl) === 1,
      'DoTopDeckPlay takes $label as a REQUIRED parameter');
// Scope this to the funnel's own body. A '.*' spanning GameLogic.php (15k+ lines) trips PCRE's backtrack
// limit and preg_match returns FALSE — which an `=== 1` check reports as a failure and a truthy check
// would have reported as a PASS.
$fnStart = strpos($gl, 'function _topDeckSearchBegin(');
check($fnStart !== false, '_topDeckSearchBegin is defined in GameLogic.php');
$fnBody  = substr($gl, $fnStart, strpos($gl, "\nfunction ", $fnStart + 10) - $fnStart);
check(preg_match('~\$param = .*_swuTopDeckWireText\(\$label\).*_swuTopDeckWireText\(\$verb\);~s', $fnBody) === 1,
      'both fields are appended to the decision param, underscored for the space-delimited row');

// A DecisionQueue row is space-delimited and this param is '|'-delimited, so the funnel — not 65 card
// files — has to make caller prose safe. Exercise the REAL normaliser: lift its source out of
// GameLogic.php under another name rather than reimplementing it here (a copy would only test the copy),
// and rather than including GameLogic.php, which drags in the whole engine.
check(preg_match('~function _swuTopDeckWireText\(string \$s\): string \{.*?\n\}~s', $gl, $m) === 1,
      '_swuTopDeckWireText is defined in GameLogic.php');
eval(str_replace('_swuTopDeckWireText', '_wireTextUnderTest', $m[0]));
foreach ([
    ['space units',              'space_units',              'a space becomes an underscore'],
    ["Twi'lek units",            "Twi'lek_units",            'an apostrophe survives'],
    ['a | b',                    'a_/_b',                    "a '|' cannot shift the param's fields"],
    ['  padded  words  ',        'padded_words',             'runs of whitespace collapse to one underscore'],
    ["tabs\tand\nnewlines",      'tabs_and_newlines',        'tabs and newlines are separators too'],
] as [$in, $want, $why]) {
    $got = _wireTextUnderTest($in);
    check($got === $want, "wire text: {$why} (" . var_export($in, true) . " → '{$got}')");
}
echo "PASS\n";
