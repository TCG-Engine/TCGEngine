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

// Twin Suns fixtures, built from the live dictionary so they're guaranteed real + all-sets-legal.
function _tsMain($n = 80) {           // n distinct-by-canonical playable cards (1 copy each = highlander-legal)
    global $typeData;
    $deck = []; $seen = [];
    foreach ($typeData as $cid => $type) {
        if (!in_array($type, ['Unit', 'Event', 'Upgrade'], true)) continue;
        $canon = CardIDOverride($cid);
        if (isset($seen[$canon])) continue;
        $seen[$canon] = true;
        $deck[] = $cid;
        if (count($deck) >= $n) break;
    }
    return $deck;
}
function _errHas($errors, $needle) {
    foreach ((array)$errors as $e) { if (stripos($e, $needle) !== false) return true; }
    return false;
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

// 6. SIDEBOARD LEGALITY: a card that is off-format in the SIDEBOARD is rejected (it can be swapped
//    into the deck between games). SOR_100 is Eternal-legal but NOT Premier-legal.
$sbIllegalPremier = SWUCheckFormat('premier', $leader, $base, _legalMain(), ['SOR_100']);
$sbIllegalEternal = SWUCheckFormat('eternal', $leader, $base, _legalMain(), ['SOR_100']);
$checks['premier rejects off-format sideboard card'] = !empty($sbIllegalPremier);
$checks['eternal accepts SOR sideboard card']        = ($sbIllegalEternal === []);

// 7. COMBINED COPY LIMIT: the 3-copy limit spans main deck + sideboard. _legalMain() already has
//    exactly 3x JTL_100; one more copy in the sideboard = 4 in the pool = over the limit.
$overLimitCombined = SWUCheckFormat('premier', $leader, $base, _legalMain(), ['JTL_100']);
$checks['combined copy limit rejects 3-main + 1-side'] = !empty($overLimitCombined);

// 8. NO OVER-BLOCK: a legal sideboard card that stays within the combined limit still passes.
//    JTL_103 is 1x in _legalMain(); +1 in the sideboard = 2 in the pool (<=3), legal set → OK.
$legalSideboard = SWUCheckFormat('premier', $leader, $base, _legalMain(), ['JTL_103']);
$checks['legal sideboard within combined limit passes'] = ($legalSideboard === []);

// ── Twin Suns (all sets, no bans, 2 different leaders, 1 base, >=80 cards, highlander 1-copy) ──
$tsL    = ['SOR_005', 'JTL_012']; // 2 distinct Heroism leaders (both "Luke Skywalker" variants) — a legal pair
$tsBase = 'JTL_023';
$tsMain = _tsMain(80);            // 80 distinct playable cards (drawn from all sets)

// T1. A valid Twin Suns deck (2 leaders, 80 distinct all-sets cards) passes.
$checks['twinsuns valid deck passes'] = SWUCheckFormat('twinsuns', $tsL, $tsBase, $tsMain, []) === [];

// T2. Exactly 2 leaders required — one leader is rejected.
$checks['twinsuns rejects single leader'] =
    _errHas(SWUCheckFormat('twinsuns', [$tsL[0]], $tsBase, $tsMain, []), 'exactly 2 leaders');

// T3. The two leaders must be different — duplicate leaders rejected (highlander applies to leaders).
$checks['twinsuns rejects duplicate leaders'] =
    _errHas(SWUCheckFormat('twinsuns', [$tsL[0], $tsL[0]], $tsBase, $tsMain, []), 'duplicate leaders');

// T4. Highlander: a 2nd copy of any deck card is rejected.
$tsDup = $tsMain; $tsDup[] = $tsMain[0];
$checks['twinsuns rejects duplicate card'] =
    _errHas(SWUCheckFormat('twinsuns', $tsL, $tsBase, $tsDup, []), 'copy limit');

// T5. Minimum 80 cards (not 50) — a 79-card deck is rejected.
$tsShort = array_slice($tsMain, 0, 79);
$checks['twinsuns rejects <80 deck'] =
    _errHas(SWUCheckFormat('twinsuns', $tsL, $tsBase, $tsShort, []), 'minimum');

// T6. All sets legal in Twin Suns — the same 80-card deck (spans older sets) is REJECTED by Premier,
//     proving the Twin Suns pass in T1 is genuinely cross-set, not a coincidence of Premier-legal cards.
$checks['premier rejects the twinsuns all-sets deck'] =
    !empty(SWUCheckFormat('premier', $tsL[0], $base, $tsMain, []));

// ── Twin Suns leader alignment (CR §12.2.1.a: the two leaders' STARTING sides can't combine
//    Heroism + Villainy). Color-only leaders (no alignment) pair with anyone; a flip leader that
//    prints both alignments is keyed to its starting side. ──
// Reference leaders: SOR_005 Luke = Vigilance,Heroism (H) · SOR_010 Vader = Aggression,Villainy (V)
//   JTL_012 Luke = Aggression,Heroism (H) · SOR_006 Palpatine = Command,Villainy (V)
//   SEC_018 DJ = Cunning,Cunning (neutral) · ASH_002 Fennec = Aggression,Cunning (neutral)
//   TWI_017 Palpatine "Playing Both Sides" = prints Heroism+Villainy, STARTS Heroism.

// A1. Heroism + Villainy leaders are rejected.
$checks['twinsuns rejects Heroism+Villainy leaders'] =
    _errHas(SWUCheckFormat('twinsuns', ['SOR_005', 'SOR_010'], $tsBase, $tsMain, []), 'Heroism and Villainy');
// A2. Two Heroism leaders are fine.
$checks['twinsuns allows two Heroism leaders'] =
    SWUCheckFormat('twinsuns', ['SOR_005', 'JTL_012'], $tsBase, $tsMain, []) === [];
// A3. Two Villainy leaders are fine.
$checks['twinsuns allows two Villainy leaders'] =
    SWUCheckFormat('twinsuns', ['SOR_010', 'SOR_006'], $tsBase, $tsMain, []) === [];
// A4. A color-only leader (DJ) pairs with a Villainy leader — "works with any other leader".
$checks['twinsuns DJ (neutral) pairs with Villainy'] =
    SWUCheckFormat('twinsuns', ['SEC_018', 'SOR_010'], $tsBase, $tsMain, []) === [];
// A5. A mixed-color leader (Fennec ASH) pairs with a Heroism leader.
$checks['twinsuns Fennec (neutral) pairs with Heroism'] =
    SWUCheckFormat('twinsuns', ['ASH_002', 'SOR_005'], $tsBase, $tsMain, []) === [];
// A6. Palpatine TWI STARTS Heroism, so pairing him with a Villainy leader is rejected
//     (without the start-side override he'd read as both → NEUTRAL → wrongly allowed).
$checks['twinsuns Palpatine-TWI + Villainy rejected'] =
    _errHas(SWUCheckFormat('twinsuns', ['TWI_017', 'SOR_010'], $tsBase, $tsMain, []), 'Heroism and Villainy');
// A7. Palpatine TWI (Heroism start) pairs fine with a Heroism leader.
$checks['twinsuns Palpatine-TWI + Heroism allowed'] =
    SWUCheckFormat('twinsuns', ['TWI_017', 'SOR_005'], $tsBase, $tsMain, []) === [];

// ── Shared validator (AppCore/SWU/DeckValidation.php): bool wrapper + injectable leader aspects ──
// B1/B2. SWUIsDeckLegal is the boolean form of SWUCheckFormat.
$checks['SWUIsDeckLegal true for legal premier']   = SWUIsDeckLegal('premier', $leader, $base, _legalMain(), []) === true;
$checks['SWUIsDeckLegal false for illegal premier'] = SWUIsDeckLegal('premier', $leader, $base, $eternalDeck, []) === false;
// B3. Injected $leaderAspects override the global dictionary (pure-function path): SOR_005 is really
// Heroism, but injecting it as Villainy (JTL_012 kept Heroism) makes the pair conflict — proving the
// injected map, not the global $aspectData, was used.
$checks['injected leader aspects are honored'] =
    _errHas(SWUCheckFormat('twinsuns', ['SOR_005', 'JTL_012'], $tsBase, $tsMain, [],
                           ['SOR_005' => 'Villainy', 'JTL_012' => 'Heroism']),
            'Heroism and Villainy');

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
