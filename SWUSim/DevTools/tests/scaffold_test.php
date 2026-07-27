<?php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

// Load the tool's functions without running its main(). The tool guards main() behind
// a direct-invocation check (like regen-card-index.php), so require is side-effect-free.
require __DIR__ . '/../scaffold-cards.php';

// --- keyword-name extraction from a fixture snippet ---
$tmp = sys_get_temp_dir() . '/kwfix_' . getmypid() . '.php';
file_put_contents($tmp, "<?php\n\$Raid_Cards = [];\n\$Sentinel_Cards = [];\n\$Restore_Cards = [];\n");
$kw = scaffold_keyword_names($tmp);
unlink($tmp);
check(in_array('Raid', $kw, true) && in_array('Sentinel', $kw, true) && in_array('Restore', $kw, true), 'keyword names: ' . json_encode($kw));
check(!in_array('Cards', $kw, true), 'must not capture the _Cards suffix');

// --- text residue: keyword-only text reduces to empty; real ability text survives ---
$kw = ['Raid', 'Sentinel', 'Restore', 'Ambush'];
check(scaffold_text_residue('Sentinel', $kw) === '', 'lone keyword -> empty');
check(scaffold_text_residue('Raid 2 (While this unit is attacking...)', $kw) === '', 'keyword+value+reminder -> empty');
check(scaffold_text_residue('When Played: Deal 2 damage to a unit.', $kw) !== '', 'ability text -> non-empty');

// --- classifier against real dictionary cards ---
$allKw = scaffold_keyword_names(__DIR__ . '/../../GeneratedCode/GeneratedKeywordCode.php');
check(scaffold_is_non_vanilla('SOR_005', $allKw), 'Luke Skywalker (Leader) is non-vanilla');   // Leader
check(scaffold_is_non_vanilla('SOR_033', $allKw), 'Death Trooper (When Played) is non-vanilla'); // trigger stub

// --- stub body: header + marker, registers nothing ---
$body = scaffold_stub_body('SOR_033');
check(strpos($body, '<?php') === 0, 'stub starts with <?php');
check(strpos($body, '// SOR_033') !== false, 'stub header has the CID');
check(strpos($body, '// TODO: UNIMPLEMENTED') !== false, 'stub has the UNIMPLEMENTED marker');
check(strpos($body, 'Abilities[') === false && strpos($body, 'customDQHandlers[') === false, 'stub registers nothing');

// --- additive guard (coverage oracle): a fully-covered set proposes NOTHING ---
$cardsDir  = __DIR__ . '/../../Custom/cards';
$customRoot = __DIR__ . '/../../Custom';
$covered = scaffold_covered_cids($customRoot);
$kw = scaffold_keyword_names(__DIR__ . '/../../GeneratedCode/GeneratedKeywordCode.php');
$secPlan = scaffold_plan('SEC', $kw, $covered, $cardsDir);
check(count($secPlan['create']) === 0, 'SEC fully covered -> 0 to create, got: ' . json_encode(array_keys($secPlan['create'])));

// --- every proposed create is genuinely uncovered + non-vanilla + not a token; a known
//     implemented card (SOR_033 Death Trooper) is never proposed. (SOR has 2 real gaps:
//     SOR_035 / SOR_067 — trigger stub present, no handler — which the tool correctly surfaces.)
$sorPlan = scaffold_plan('SOR', $kw, $covered, $cardsDir);
check(!isset($sorPlan['create']['SOR_033']), 'implemented SOR_033 must not be proposed');
foreach ($sorPlan['create'] as $cid => $rel) {
    check(!isset($covered[$cid]), "proposed $cid must be uncovered");
    check(scaffold_is_non_vanilla($cid, $kw), "proposed $cid must be non-vanilla");
    check(!preg_match('/_T\d\d$/', $cid), "proposed $cid must not be a token");
}

// --- a written stub PHP-lints clean and includes without registering anything ---
$tmp = sys_get_temp_dir() . '/stub_' . getmypid() . '.php';
file_put_contents($tmp, scaffold_stub_body('SOR_033'));
$lint = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
check(strpos((string)$lint, 'No syntax errors') !== false, "stub lints: $lint");
include $tmp;   // must be a harmless no-op
unlink($tmp);

echo "OK\n";
