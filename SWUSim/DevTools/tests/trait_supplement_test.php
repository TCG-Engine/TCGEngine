<?php
// Trait supplement: fills traits the upstream API omits (every base — 91 of them), WITHOUT ever
// overriding traits the API does provide.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../TraitSupplement.php';

$fixture = sys_get_temp_dir() . '/traitsupp_' . getmypid() . '.php';
file_put_contents($fixture, "<?php\nreturn ['JTL_030' => 'Tatooine', 'SOR_024' => 'Hoth', 'XXX_001' => 'Ghost, Spectre'];\n");

// --- load ---
$supp = SWUSimLoadTraitSupplement($fixture);
check($supp['JTL_030'] === 'Tatooine', 'loads a single trait');
check($supp['XXX_001'] === 'Ghost, Spectre', 'loads a multi-trait string');
check(SWUSimLoadTraitSupplement('/no/such/file.php') === [], 'missing file is not an error');

// --- fill-gaps: only where the official trait list is EMPTY ---
$traits = [
    'JTL_030' => '',            // base, API gave nothing -> filled
    'SOR_024' => null,          // base, null -> filled
    'SOR_010' => 'Force,Imperial,Sith',   // real card with traits -> untouched
    'XXX_001' => 'Ghost',       // already has a trait -> untouched even though supplemented
];
$n = SWUSimApplyTraitSupplement($traits, $fixture);
check($n === 2, 'filled exactly the two empty entries, got ' . $n);
check($traits['JTL_030'] === 'Tatooine', 'empty string filled');
check($traits['SOR_024'] === 'Hoth', 'null filled');
check($traits['SOR_010'] === 'Force,Imperial,Sith', 'official traits NEVER overridden');
check($traits['XXX_001'] === 'Ghost', 'non-empty entry left alone (official always wins)');

// --- a supplemented CardID absent from the dictionary is simply added ---
$traits2 = [];
SWUSimApplyTraitSupplement($traits2, $fixture);
check(($traits2['JTL_030'] ?? '') === 'Tatooine', 'absent key is added');

// --- normalization: whitespace after commas is trimmed to match dictionary form ---
check($traits2['XXX_001'] === 'Ghost,Spectre',
      'multi-trait normalized to comma-joined with no spaces, got: ' . $traits2['XXX_001']);

unlink($fixture);

// --- the generator applies it ---
$gen = file_get_contents(__DIR__ . '/../../../zzCardCodeGenerator.php');
check(strpos($gen, 'SWUSimApplyTraitSupplement') !== false, 'generator applies the trait supplement');

// --- the real file exists and every key is a valid CardID ---
// Uses the shared set-aware validator: TS26 numbers are TWO digits ("TS26_09"), so a flat 3-digit
// rule would reject four legitimate bases.
require_once __DIR__ . '/../../../AppCore/SWU/MockCardMerge.php';
check(SWUIsMockCardID('TS26_09'), 'validator accepts a double-digit set id');
check(SWUIsMockCardID('JTL_030'), 'validator accepts a 3-digit id');
check(SWUIsMockCardID('HMW_T01'), 'validator accepts a token id');
check(!SWUIsMockCardID('JTL_30'), 'validator rejects an under-padded 3-digit-set id');
check(!SWUIsMockCardID('TS26_009'), 'validator rejects an over-padded double-digit-set id');

$real = SWUSimLoadTraitSupplement();
check(is_array($real), 'real supplement loads');
check(count($real) >= 91, 'supplement covers every base, got ' . count($real));
foreach (array_keys($real) as $cid) {
    check(SWUIsMockCardID($cid), "supplement key $cid is a valid CardID");
}

echo "OK\n";
