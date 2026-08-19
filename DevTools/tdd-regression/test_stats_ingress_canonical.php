<?php
// Stats must aggregate under ONE identity forever, or a card's history splits every time it is
// reprinted. This already holds — SWUCardIdentityClassify folds through CardIDOverride at ingress —
// but nothing pinned it, and publishing latest-printing ids means those ids will now genuinely arrive
// at SubmitGameResult from clients echoing our own export back.
$root = realpath(__DIR__ . '/../..');
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/SWUDeck/Custom/DeckFormats.php';
require_once $root . '/AppCore/SWU/CardDisplayID.php';
require_once $root . '/AppCore/SWU/CardIdentity.php';
SWUDeckSetReprintUniverse();

$checks = [];
$drift  = [];
$groups = [];
foreach ($GLOBALS['SWUReprintUniverse'] as $id) $groups[CardIDOverride($id)][] = $id;
foreach (array_filter($groups, fn($g) => count($g) > 1) as $canon => $printings) {
    foreach ($printings as $p) {
        // Whatever printing arrives — canonical, reprint, or the one we now display — ingress must
        // classify it to the same canonical identity.
        if (SWUCardIdentityClassify($p) !== SWUCardIdentityClassify(SWUDisplayCardID($p))) $drift[] = $p;
    }
}
$checks['ingress folds the display printing to canonical'] = empty($drift);
if ($drift) fwrite(STDERR, "  drift on: " . implode(', ', array_slice($drift, 0, 10)) . "\n");

$checks['Wampa printings classify identically'] =
    SWUCardIdentityClassify('LOF_164') === SWUCardIdentityClassify('SOR_164');

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
