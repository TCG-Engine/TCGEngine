<?php
// General card-data supplement: fills fields the upstream API omits (base traits, deployed-leader
// sides) WITHOUT ever overriding a value the API or a mock provided, and never creates a card.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../../../AppCore/SWU/CardDataSupplementApply.php';

$fixture = sys_get_temp_dir() . '/carddatasupp_' . getmypid() . '.php';
file_put_contents($fixture, <<<'PHP'
<?php
return [
  'JTL_030' => ['trait' => ['value' => 'Tatooine', 'source' => 'swudb']],
  'SOR_024' => ['trait' => ['value' => 'Hoth', 'source' => 'swudb']],
  'SOR_010' => ['trait' => ['value' => 'Should,Not,Apply', 'source' => 'swudb']],
  'XXX_001' => ['trait' => ['value' => 'Ghost, Spectre', 'source' => 'manual']],
  'HMW_004' => [
    'leaderUnitTitle' => ['value' => 'The Death Star', 'source' => 'manual'],
    'leaderUnitTrait' => ['value' => 'Imperial, Vehicle, Capital Ship', 'source' => 'manual'],
  ],
  'ZZZ_999' => ['trait' => ['value' => 'Nowhere', 'source' => 'manual']],
];
PHP);

// --- load ---
$supp = SWULoadCardDataSupplement($fixture);
check($supp['JTL_030']['trait']['value'] === 'Tatooine', 'loads a value');
check($supp['HMW_004']['leaderUnitTitle']['source'] === 'manual', 'loads a source');
check(SWULoadCardDataSupplement('/no/such/file.php') === [], 'missing file is not an error');

// --- blank detection ---
check(SWUIsBlankDictionaryValue(null), 'null is blank');
check(SWUIsBlankDictionaryValue('  '), 'whitespace is blank');
check(SWUIsBlankDictionaryValue([]), 'empty array is blank');
check(!SWUIsBlankDictionaryValue(0), '0 is NOT blank');
check(!SWUIsBlankDictionaryValue(false), 'false is NOT blank');

// --- normalization ---
check(SWUNormalizeSupplementValue('trait', 'Ghost, Spectre') === 'Ghost,Spectre', 'list field comma-joined');
check(SWUNormalizeSupplementValue('trait', ['A', ' B ', '']) === 'A,B', 'list field from array');
check(SWUNormalizeSupplementValue('leaderUnitTitle', ' The Death Star ') === 'The Death Star', 'scalar trimmed, inner spaces kept');

// --- apply (SWUSim-shaped: has leaderUnit* fields) ---
$dicts = [
  'trait' => ['JTL_030' => '', 'SOR_024' => null, 'SOR_010' => 'Force,Imperial,Sith', 'XXX_001' => '', 'HMW_004' => 'Imperial,Official'],
  'leaderUnitTitle' => ['JTL_030' => null, 'SOR_024' => null, 'SOR_010' => null, 'XXX_001' => null, 'HMW_004' => null],
  'leaderUnitTrait' => ['JTL_030' => null, 'SOR_024' => null, 'SOR_010' => null, 'XXX_001' => null, 'HMW_004' => null],
];
$r = SWUApplyCardDataSupplement($dicts, $fixture);
check($dicts['trait']['JTL_030'] === 'Tatooine', 'empty string filled');
check($dicts['trait']['SOR_024'] === 'Hoth', 'null filled');
check($dicts['trait']['SOR_010'] === 'Force,Imperial,Sith', 'official value NEVER overridden');
check($dicts['trait']['XXX_001'] === 'Ghost,Spectre', 'filled value normalized');
check($dicts['leaderUnitTitle']['HMW_004'] === 'The Death Star', 'non-trait field filled');
check($dicts['leaderUnitTrait']['HMW_004'] === 'Imperial,Vehicle,Capital Ship', 'deployed traits filled + normalized');
check($r['filled']['JTL_030']['trait'] === 'swudb', 'fill records swudb source');
check($r['filled']['HMW_004']['leaderUnitTitle'] === 'manual', 'fill records manual source');
check(!isset($r['filled']['SOR_010']), 'an unapplied entry is not reported as filled');
check(!array_key_exists('ZZZ_999', $dicts['trait']), 'never creates a card');
check(in_array('ZZZ_999.trait', $r['unknownCard'], true), 'unknown CardID reported');

// --- apply (SWUDeck-shaped: no leaderUnit* fields) ---
$deck = ['trait' => ['HMW_004' => 'Imperial,Official', 'JTL_030' => '']];
$r2 = SWUApplyCardDataSupplement($deck, $fixture);
check($deck['trait']['JTL_030'] === 'Tatooine', 'SWUDeck gets traits');
check(!array_key_exists('leaderUnitTitle', $deck), 'SWUDeck gains no leaderUnit field');
check(in_array('HMW_004.leaderUnitTitle', $r2['unknownField'], true), 'field absent from this app reported');

// --- writer round-trips ---
$out = sys_get_temp_dir() . '/carddatasupp_out_' . getmypid() . '.php';
check(SWUWriteCardDataSupplement(['B_001' => ['trait' => ['value' => 'X', 'source' => 'swudb']], 'A_001' => ['trait' => ['value' => 'Y', 'source' => 'manual']]], $out), 'writer succeeds');
$src = file_get_contents($out);
check(strpos($src, 'SCAFFOLD-IGNORE') !== false, 'written file carries SCAFFOLD-IGNORE');
check(strpos($src, "'A_001'") < strpos($src, "'B_001'"), 'entries sorted by CardID');
check(SWULoadCardDataSupplement($out)['B_001']['trait']['value'] === 'X', 'written file loads back');

unlink($fixture); unlink($out);
// --- the real file: >= 91 base traits migrated, every key a valid CardID, every entry well-formed ---
require_once __DIR__ . '/../../../AppCore/SWU/MockCardMerge.php';
check(SWUIsMockCardID('TS26_09'), 'validator accepts a double-digit set id');
check(SWUIsMockCardID('HMW_T01'), 'validator accepts a token id');
check(!SWUIsMockCardID('JTL_30'), 'validator rejects an under-padded id');
$real = SWULoadCardDataSupplement();
$traitCount = 0;
foreach ($real as $cid => $fields) {
    check(SWUIsMockCardID($cid), "supplement key $cid is a valid CardID");
    foreach ($fields as $field => $entry) {
        check(is_array($entry) && isset($entry['value']) && in_array($entry['source'] ?? '', ['swudb', 'manual'], true),
              "$cid.$field has value + a valid source");
        if ($field === 'trait') $traitCount++;
    }
}
check($traitCount >= 91, 'every legacy base trait migrated, got ' . $traitCount);
check(($real['JTL_030']['trait']['value'] ?? '') === 'Tatooine', 'JTL_030 migrated');
check(($real['TS26_09']['trait']['value'] ?? '') === 'Coruscant', 'TS26_09 migrated');

// --- the generator applies it, for BOTH SWU apps, and the old applier is gone ---
$gen = file_get_contents(__DIR__ . '/../../../zzCardCodeGenerator.php');
check(preg_match('/\$rootName == "SWUSim" \|\| \$rootName == "SWUDeck"[\s\S]{0,300}SWUApplyCardDataSupplement/', $gen) === 1,
      'generator applies the card data supplement for BOTH SWUSim and SWUDeck');
check(strpos($gen, 'SWUApplyTraitSupplement') === false, 'old trait applier no longer called');
check(!file_exists(__DIR__ . '/../../../AppCore/SWU/TraitSupplement.php'), 'TraitSupplement.php deleted');
check(!file_exists(__DIR__ . '/../../../AppCore/SWU/CardTraitSupplement.php'), 'CardTraitSupplement.php deleted');

echo "OK\n";
