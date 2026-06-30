<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_check_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../SWUSim/Custom/DeckImport.php';

// 50-card legal Premier deck: 16 legal commons x3 (=48) + 2 more legal commons x1.
function _legalMain() {
    $cards = ['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010',
              'JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011',
              'JTL_102','LOF_102','SEC_102','LAW_102'];
    $deck = [];
    foreach ($cards as $c) { $deck[] = $c; $deck[] = $c; $deck[] = $c; } // 48
    $deck[] = 'JTL_103'; $deck[] = 'LOF_103';                            // 50
    return $deck;
}

$leader = 'JTL_001';  // Premier-legal leader
$base   = 'JTL_023';  // Premier-legal base
$checks = [];

// 1. A legal Premier deck passes.
$checks['premier legal deck'] = SWUCheckFormat('premier', $leader, $base, _legalMain(), []) === [];

// 2. Eternal accepts an older-set (SOR) card that Premier would reject.
$eternalDeck = _legalMain();
$eternalDeck[0] = 'SOR_100'; // swap one entry for a SOR card (legal in Eternal, not Premier)
$premierVerdict = SWUCheckFormat('premier', $leader, $base, $eternalDeck, []);
$eternalVerdict = SWUCheckFormat('eternal', $leader, $base, $eternalDeck, []);
$checks['premier rejects SOR card'] = !empty($premierVerdict);
$checks['eternal accepts SOR card'] = ($eternalVerdict === []);

// 3. BANLIST CANONICALIZATION: ban Wampa by its Premier-legal printing LOF_164.
//    A deck listing LOF_164 (canon SOR_164) must be caught — both sides canonicalized.
$bannedConfigEntry = 'LOF_164';                 // natural authoring: ban the legal printing
$deckCardCanon     = CardIDOverride('LOF_164'); // = SOR_164
$bannedCanon       = CardIDOverride($bannedConfigEntry);
$checks['ban canon matches'] = ($deckCardCanon === $bannedCanon);
$checks['ban canon is SOR_164'] = ($bannedCanon === 'SOR_164');

// 4. Unknown format errors.
$checks['unknown format errors'] = !empty(SWUCheckFormat('nope', $leader, $base, _legalMain(), []));

// 5. Wrapper parity: SWUCheckPremierFormat === SWUCheckFormat('premier', ...).
$checks['wrapper parity'] =
    SWUCheckPremierFormat($leader, $base, _legalMain(), [])
    === SWUCheckFormat('premier', $leader, $base, _legalMain(), []);

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
