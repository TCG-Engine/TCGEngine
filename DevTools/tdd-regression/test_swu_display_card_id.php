<?php
// SWUDisplayCardID: any printing -> the latest NON-SPECIAL printing to show.
// The inverse direction of CardIDOverride (any printing -> earliest). Special (rarity 'S') printings
// are showcase variants and are never selected: a literal "newest" would swap 14 cards to showcase art.
$root = realpath(__DIR__ . '/../..');
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/SWUDeck/Custom/DeckFormats.php';   // SWUDeckSetReprintUniverse
require_once $root . '/AppCore/SWU/CardDisplayID.php';
SWUDeckSetReprintUniverse();

$checks = [];

// Wampa: SOR_164 (Common) -> LOF_164 (Common). The canonical case from the request.
$checks['Wampa maps SOR_164 -> LOF_164'] = SWUDisplayCardID('SOR_164') === 'LOF_164';
// Idempotent: feeding the display id back gives the same answer.
$checks['idempotent on the display id']   = SWUDisplayCardID('LOF_164') === 'LOF_164';

// Open Fire: only newer printing (TWI_174) is Special, so it must NOT move.
$checks['Special printing is never selected'] = SWUDisplayCardID('SOR_172') === 'SOR_172';
// Cell Block Guard: same shape (SHD_238 is Special).
$checks['Cell Block Guard stays canonical']   = SWUDisplayCardID('SOR_229') === 'SOR_229';

// Cards with no reprints and unknown ids pass straight through.
$checks['no-reprint card unchanged'] = SWUDisplayCardID('SOR_095') === 'SOR_095';
$checks['unknown id unchanged']      = SWUDisplayCardID('NOPE_999') === 'NOPE_999';
$checks['empty string unchanged']    = SWUDisplayCardID('') === '';

// Legacy decks store FFG UUIDs, and the render seam passes the stored CardID in raw. A UUID for a
// reprinted card must still resolve to the display printing, or the same deck renders differently
// depending on when it was saved. Caught by the browser check, not by any structural assertion.
$wampaUuid = UUIDLookup('SOR_164');
$checks['UUID of a reprinted card resolves to the display printing'] =
    $wampaUuid && SWUDisplayCardID($wampaUuid) === 'LOF_164';
// …but a UUID with no reprint mapping passes through untouched: this feature must not quietly re-key
// the ~2300 cards it is not about.
$plainUuid = UUIDLookup('SOR_095');
$checks['UUID of a non-reprinted card is untouched'] = $plainUuid && SWUDisplayCardID($plainUuid) === $plainUuid;

// THE INVARIANT: display never changes which card a thing IS.
$bad = [];
foreach ($GLOBALS['SWUReprintUniverse'] as $id) {
    if (CardIDOverride(SWUDisplayCardID($id)) !== CardIDOverride($id)) $bad[] = $id;
}
$checks['display never changes canonical identity'] = empty($bad);
if ($bad) fwrite(STDERR, "  identity drift on: " . implode(', ', array_slice($bad, 0, 10)) . "\n");

// Exactly 31 of 83 groups change. A different number means the rule or the corpus moved; both are
// worth failing on, because these numbers are what the design was signed off against.
//
// The arithmetic, so a future reader can tell a legitimate set release from a regression:
//   44 groups / 30 changes  was the corpus before 2026-08-18.
//   +39 groups came from linking same-name printings that Overrides.php had missed:
//        38 intra-set IBH duplicates (the product prints many cards at several collector numbers)
//         1 cross-set reprint, Viper Probe Droid SOR_228 / SEC_239.
//   +1 change is Viper Probe Droid alone (SOR_228 -> SEC_239, Common -> Common).
//   The 38 IBH groups add ZERO display changes: every one of their printings is rarity Special, and
//   the rule never selects a Special printing. That is asserted separately below.
$groups = [];
foreach ($GLOBALS['SWUReprintUniverse'] as $id) $groups[CardIDOverride($id)][] = $id;
$multi   = array_filter($groups, fn($g) => count($g) > 1);
$changed = 0;
foreach (array_keys($multi) as $canon) if (SWUDisplayCardID($canon) !== $canon) $changed++;
$checks['83 reprint groups']  = count($multi) === 83;
$checks['31 display changes'] = $changed === 31;

// The IBH links must never move a display id — they are all Special printings. This is the check that
// keeps the count above meaningful: without it, a future rule change that started selecting Special
// printings would just look like a bigger number.
$ibhMoved = 0;
foreach (array_keys($multi) as $canon) {
    if (strpos($canon, 'IBH_') === 0 && SWUDisplayCardID($canon) !== $canon) $ibhMoved++;
}
$checks['intra-set IBH links add no display changes'] = $ibhMoved === 0;

// Same-name printings must share a copy budget: the 3-copy limit counts CardIDOverride values, so an
// unlinked duplicate silently doubles the legal number of copies (six Hoth Lieutenants, pre-fix).
$sixHoth = array_count_values(array_map('CardIDOverride',
    array_merge(array_fill(0, 3, 'IBH_064'), array_fill(0, 3, 'IBH_092'))));
$checks['duplicate collector numbers share one copy budget'] = max($sixHoth) === 6;

// Every display target must be a real card with art, or the board renders a broken image.
require_once $root . '/AppCore/SWU/CardImagePath.php';
$missing = [];
foreach (array_keys($multi) as $canon) {
    $shown = SWUDisplayCardID($canon);
    if (CardTitle($shown) === null) { $missing[] = "$shown (not in dictionary)"; continue; }
    foreach (['tile', 'card'] as $kind) {
        $fs = $root . preg_replace('~^/TCGEngine~', '', (string)SWUCardImagePath($shown, $kind));
        if (!is_file($fs)) $missing[] = "$shown [$kind]";
    }
}
$checks['every display target has art at both sizes'] = empty($missing);
if ($missing) fwrite(STDERR, "  missing: " . implode(', ', array_slice($missing, 0, 10)) . "\n");

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
