<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swu_token_requirements.php
//
// Pins every detection rule behind the deckbuilder's "Needs Tokens" line. Each case below is a bug
// that would otherwise ship.
//
// ⚠ Loads SWUDeck's dictionary ONLY — the one the endpoint uses. SWUSim's declares the same function
// names and requiring both is an instant fatal.
//
// READ-ONLY: pure function calls, no database, no POST.
//
// Design: docs/superpowers/specs/2026-08-06-swudeck-token-requirements-design.md §2
header('Content-Type: text/plain');
$root = dirname(__DIR__, 2);
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/AppCore/SWU/Tokens.php';
require_once $root . '/AppCore/SWU/TokenRequirements.php';

$checks = [];
$req = fn(...$ids) => SWUDeckRequiredTokens($ids);

// ── The primary rule: "<Token> token" ───────────────────────────────────────
$checks['Captain Rex creates Clone Troopers'] = $req('TWI_097') === ['Clone Trooper'];

// ── The Shielded keyword ────────────────────────────────────────────────────
// 14 cards carry Shielded and never write "Shield token". Without a keyword rule the single most
// common token in the game under-reports. JTL_189's SWUDeck text is literally just "Shielded".
$checks['Shielded implies Shield'] = $req('JTL_189') === ['Shield'];

// ── Elided pairs ────────────────────────────────────────────────────────────
// "Give an Experience or Shield token to a unit, or create a Credit token" — the first noun's
// "token" is dropped. A naive scan reports Shield + Credit and silently loses Experience.
$checks['LAW_019 yields all three'] = $req('LAW_019') === ['Shield', 'Experience', 'Credit'];

// ── Trait is not a token ────────────────────────────────────────────────────
// Sabine Wren has the Mandalorian TRAIT. 14 cards mention "Mandalorian" as a trait; matching the
// bare name would report Mandalorian tokens for every Mandalorian tribal deck.
$checks['Mandalorian trait yields nothing'] = $req('SOR_014') === [];

// ── The Force: idiom vs trait ───────────────────────────────────────────────
$checks['use the Force implies The Force'] = $req('LOF_002') === ['The Force'];
$checks['Force TRAIT implies nothing']     = $req('SEC_054') === [];

// ── New HMW tokens ──────────────────────────────────────────────────────────
$checks['Weakness detected'] = $req('HMW_059') === ['Weakness'];
$checks['Beast detected']    = $req('HMW_168') === ['Beast'];

// ── Empty and junk input ────────────────────────────────────────────────────
$checks['no-token card yields []'] = $req('SOR_229') === [];
$checks['empty input yields []']   = SWUDeckRequiredTokens([]) === [];
$checks['unknown id yields []']    = $req('NOPE_999') === [];
$checks['blank ids are skipped']   = SWUDeckRequiredTokens(['', null, 'TWI_097']) === ['Clone Trooper'];

// ── Aggregation: dedupe, and CATALOG ORDER not input order ──────────────────
// Order is a contract: the UI prints this array as-is.
$checks['dedupes repeats'] = $req('TWI_097', 'TWI_097') === ['Clone Trooper'];
// Input order is Clone Trooper, Beast, Shield, The Force; catalog order is the reverse-ish below.
$checks['catalog order, not input order'] =
    $req('TWI_097', 'HMW_168', 'JTL_189', 'LOF_002') === ['Shield', 'Beast', 'Clone Trooper', 'The Force'];

// ── Opponent-created tokens count (design §2.6) ─────────────────────────────
// "An opponent creates 2 TIE Fighter tokens and readies them." Those tokens exist because of YOUR
// card, so you bring them. This pins a documented decision that is otherwise invisible in the code.
$checks['opponent-created tokens count'] = $req('JTL_155') === ['TIE Fighter'];

// ── Normalisation helper, directly ──────────────────────────────────────────
$checks['normalise expands an elided pair'] =
    stripos(_SWUTokenNormalizeText('Give an Experience or Shield token'), 'Experience token') !== false;
$checks['normalise leaves plain text alone'] =
    _SWUTokenNormalizeText('Create a Battle Droid token.') === 'Create a Battle Droid token.';

// ── Matcher helper, directly ────────────────────────────────────────────────
$checks['plural form matches']        = _SWUTokenTextRequires('Create 2 Clone Trooper tokens.', 'Clone Trooper') === true;
$checks['bare trait does not match']  = _SWUTokenTextRequires('This unit is a Spy.', 'Spy') === false;
$checks['X-Wing hyphen matches']      = _SWUTokenTextRequires('Create an X-Wing token.', 'X-Wing') === true;
$checks['Shielded is not Shield tok'] = _SWUTokenTextRequires('Shielded', 'Experience') === false;

// ── Every catalog token is reachable by its own primary phrasing ────────────
// Guards a typo'd catalog name that could never match anything.
$unreachable = [];
foreach (array_keys(SWUTokenCatalog()) as $name) {
    $probe = ($name === 'The Force') ? 'You may use the Force.' : "Create a $name token.";
    if (!_SWUTokenTextRequires(_SWUTokenNormalizeText($probe), $name)) $unreachable[] = $name;
}
$checks['every catalog token is reachable'] = $unreachable === [];

// ── The endpoint contract ───────────────────────────────────────────────────
// Source-scan with comments STRIPPED. A bare strpos would match the very comment explaining the
// change — test_swu_format_stats_policy.php was wrong twice this way.
$endpointSrc = @file_get_contents($root . '/SWUDeck/ValidateDeckState.php');
$checks['endpoint readable'] = $endpointSrc !== false;
$endpointCode = '';
if ($endpointSrc !== false) {
    foreach (token_get_all($endpointSrc) as $tk) {
        if (is_array($tk)) {
            if ($tk[0] === T_COMMENT || $tk[0] === T_DOC_COMMENT) continue;
            $endpointCode .= $tk[1];
        } else { $endpointCode .= $tk; }
    }
}
$checks['endpoint includes TokenRequirements'] = strpos($endpointCode, 'TokenRequirements.php') !== false;
$checks['endpoint computes tokens']            = strpos($endpointCode, 'SWUDeckRequiredTokens') !== false;
$checks['endpoint emits a tokens key']         = strpos($endpointCode, "'tokens'") !== false;

// THE REGRESSION THIS TASK CAN CAUSE: the endpoint used to return applicable:false and exit BEFORE
// ParseGamestate() for Open decks. Tokens are format-independent, so that early return had to move
// after the parse. If it ever moves back, Open decks lose their token line — and that is invisible
// on a Premier deck, so no ordinary testing would catch it.
$openPos  = strpos($endpointCode, "=== 'open'");
$parsePos = strpos($endpointCode, 'ParseGamestate()');
$checks['open-format check comes AFTER the parse'] =
    $openPos !== false && $parsePos !== false && $openPos > $parsePos;

// And the open-format branch must itself carry tokens.
$openBranch = ($openPos !== false) ? substr($endpointCode, $openPos, 400) : '';
$checks['open-format response carries tokens'] = strpos($openBranch, 'tokens') !== false;

// The sideboard must feed detection: you need the tokens present for games 2-3. Asserting the
// merge names $sideboard catches a copy-paste that silently drops it.
if (preg_match('/SWUDeckRequiredTokens\(([^;]*)\);/', $endpointCode, $mArgs)) {
    $checks['tokens include leaders']   = strpos($mArgs[1], '$leaders')   !== false;
    $checks['tokens include base']      = strpos($mArgs[1], '$base')      !== false;
    $checks['tokens include main deck'] = strpos($mArgs[1], '$mainDeck')  !== false;
    $checks['tokens include sideboard'] = strpos($mArgs[1], '$sideboard') !== false;
} else {
    $checks['tokens include leaders'] = $checks['tokens include base'] =
    $checks['tokens include main deck'] = $checks['tokens include sideboard'] = false;
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    if ($unreachable) echo "  unreachable: " . implode(', ', $unreachable) . "\n";
    foreach (['TWI_097', 'JTL_189', 'LAW_019', 'SOR_014', 'LOF_002', 'SEC_054', 'HMW_059', 'HMW_168', 'JTL_155'] as $id) {
        echo "  $id => [" . implode(', ', $req($id)) . "]\n";
    }
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
