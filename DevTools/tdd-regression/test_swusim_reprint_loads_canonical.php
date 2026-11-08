<?php
// A deck that holds a REPRINT printing must load into a game as a printing whose abilities are registered. Card
// handlers are keyed by the earliest printing (CardIDOverride; reprints are consolidated into that card's file), so a
// reprint of an SHD/TWI card — sets SWUImplementedSets does not list — has to load as its ORIGINAL. It used to fall
// through to the reprint's own id: IC27_176 Breaking In played as "IC27_176" and did nothing (TWI_224 dealt 5).
// Found after IC27 went official (2026-11-07); it bites once IC27 releases, when deck exports stop holding back
// IC27 printings (test_loaddeck_holds_preview_reprints).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_reprint_loads_canonical.php
chdir(dirname(__DIR__, 2));
require_once './SWUSim/Custom/DeckImport.php';

$checks = [];

// Every reprint printing in the dictionary, in every set: what it loads as must be the SAME CARD, and must not be the
// reprint's own id unless that id IS the earliest printing (then it is not a reprint at all).
$reprints = 0; $bad = [];
foreach (GetAllCardIds() as $id) {
    $canon = CardIDOverride($id);
    if ($canon === $id) continue;
    $reprints++;
    $loaded = SWUResolveToImplementedPrint(SWUCanonicalCardID($id));
    if (CardIDOverride($loaded) !== $canon || $loaded === $id && !in_array(SWUCardSet($id), SWUImplementedSets, true)) $bad[] = "$id -> $loaded (card $canon)";
}
$checks['the dictionary holds reprint printings to check'] = $reprints > 0;
$checks['every reprint outside an implemented set loads as its original'] = empty($bad);
if ($bad) fwrite(STDERR, '  ' . count($bad) . ': ' . implode(', ', array_slice($bad, 0, 8)) . "\n");

// The named cases.
$checks['IC27_176 Breaking In loads as TWI_224'] = SWUResolveToImplementedPrint('IC27_176') === 'TWI_224';
$checks['IC27_154 Grogu loads as SHD_196']       = SWUResolveToImplementedPrint('IC27_154') === 'SHD_196';
// Unchanged: a reprint with an implemented-set printing still loads as that printing, and a card that is not a
// reprint loads as itself.
$checks['HMW_022 still aliases to JTL_020'] = SWUResolveToImplementedPrint('HMW_022') === 'JTL_020';
$checks['IC27_050 loads as SEC_063']         = SWUResolveToImplementedPrint('IC27_050') === 'SEC_063';
$checks['IC27_117 (new card) loads as itself'] = SWUResolveToImplementedPrint('IC27_117') === 'IC27_117';
$checks['SHD_196 (an original) loads as itself'] = SWUResolveToImplementedPrint('SHD_196') === 'SHD_196';

$fail = array_keys(array_filter($checks, fn($v) => !$v));
echo empty($fail) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(' | ', $fail) . "\n";
exit(empty($fail) ? 0 : 1);
