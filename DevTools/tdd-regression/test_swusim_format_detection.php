<?php
// Format detection from a deck link (owner spec, 2026-09-25).
//
// Order is MOST RESTRICTIVE -> LEAST, and the first format the deck is legal in wins:
//   twinsuns -> twinsuns-preview -> padawan -> padawan-preview
//   -> premier -> preview(Premier Preview) -> eternal -> eternal-preview -> open
// Open is the wildcard floor, so detection NEVER fails: a deck legal nowhere else lands on Open.
//
// Run: curl http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_format_detection.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../../AppCore/SWU/DeckValidation.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond) {
    global $PASS, $FAIL, $MSGS;
    if ($cond) $PASS++; else { $FAIL++; $MSGS[] = "FAIL: $name"; }
}

check('SWUDetectFormat() exists', function_exists('SWUDetectFormat'));

if (function_exists('SWUDetectFormat')) {
    // The ladder itself, independent of any deck.
    $order = SWUDetectFormatOrder();
    check('the ladder is most-restrictive first', $order[0] === 'twinsuns');
    check('Open is the floor', end($order) === 'open');
    check('preview variants follow their base format',
        array_search('twinsuns-preview', $order) === array_search('twinsuns', $order) + 1 &&
        array_search('preview', $order)          === array_search('premier', $order) + 1 &&
        array_search('eternal-preview', $order)  === array_search('eternal', $order) + 1 &&
        array_search('padawan-preview', $order)  === array_search('padawan', $order) + 1);

    // Real decks, 3 copies max — the copy limit is enforced, so a fixture of 30 identical
    // cards is illegal EVERYWHERE and silently lands on Open, which looks like a detection bug.
    $rep = function (array $ids, int $each = 3) {
        $out = [];
        foreach ($ids as $id) for ($i = 0; $i < $each; $i++) $out[] = $id;
        return $out;
    };
    $ashUnits = ['ASH_027','ASH_028','ASH_029','ASH_030','ASH_031','ASH_032','ASH_033','ASH_034',
                 'ASH_035','ASH_036','ASH_037','ASH_038','ASH_039','ASH_040','ASH_041','ASH_042','ASH_043'];
    $sorUnits = ['SOR_229','SOR_240','SOR_084','SOR_189','SOR_225','SOR_237','SOR_035','SOR_049',
                 'SOR_083','SOR_129','SOR_132','SOR_198','SOR_226','SOR_227','SOR_228','SOR_230','SOR_232'];
    // Twin Suns is SINGLETON — max 1 copy — so it needs 80 DISTINCT cards. A fixture with
    // repeats is illegal and lands on Open, which reads as a detection bug.
    $ts80 = ['SOR_229','SOR_240','SOR_084','SOR_189','SOR_225','SOR_235','SOR_237','SOR_246','SOR_035','SOR_049','SOR_058','SOR_074','SOR_078','SOR_083','SOR_126','SOR_129','SOR_132','SOR_198','SOR_217','SOR_218','SOR_226','SOR_227','SOR_228','SOR_230','SOR_232','SOR_233','SOR_234','SOR_236','SOR_242','SOR_244','SOR_097','SOR_107','SOR_125','SOR_143','SOR_155','SOR_164','SOR_172','SOR_203','SOR_204','SOR_213','SOR_186','SOR_200','SOR_222','SOR_033','SOR_037','SOR_038','SOR_042','SOR_044','SOR_045','SOR_059','SOR_060','SOR_063','SOR_066','SOR_046','SOR_047','SOR_048','SOR_052','SOR_067','SOR_075','SOR_077','SOR_079','SOR_080','SOR_081','SOR_082','SOR_086','SOR_088','SOR_089','SOR_090','SOR_092','SOR_099','SOR_100','SOR_102','SOR_109','SOR_112','SOR_113','SOR_115','SOR_118','SOR_123','SOR_128','SOR_124'];

    // An ASH-only list is legal in Premier (ASH is a Premier set) — and premier must win over
    // eternal, which is less restrictive and comes later.
    check('a Premier-legal list detects as premier',
        SWUDetectFormat('ASH_009', 'ASH_025', $rep($ashUnits), []) === 'premier');

    // Two leaders => Twin Suns, ahead of everything else on the ladder.
    check('a two-leader singleton list detects as twinsuns',
        SWUDetectFormat(['TS26_02','TS26_04'], 'TS26_11', $ts80, []) === 'twinsuns');

    // SOR is Eternal-only, so the list cannot be Premier and falls through to eternal.
    check('an Eternal-only card falls through to eternal',
        SWUDetectFormat('SOR_005', 'SOR_024', $rep($sorUnits), []) === 'eternal');

    // A banned card is legal nowhere restricted -> Open, the floor.
    check('a banned card lands on open',
        SWUDetectFormat('SOR_005', 'SOR_024',
            array_merge($rep(array_slice($sorUnits, 0, 16)), ['JTL_140', 'JTL_140']), []) === 'open');
}

echo "PASS=$PASS FAIL=$FAIL\n";
foreach ($MSGS as $m) echo "$m\n";
echo $FAIL === 0 ? "ALL GREEN\n" : "RED\n";
