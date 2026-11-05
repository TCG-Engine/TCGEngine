<?php
// Limited deck validator — the deck must come from the player's pool —
// docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.4, §3.1, §3.4.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_deck_test.php
chdir(dirname(__DIR__, 3));
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Limited/LimitedDeck.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$isLeader = fn($c) => CardType($c) === 'Leader';
$isBase   = fn($c) => CardType($c) === 'Base';
$drawable = fn(array $pool) => array_values(array_filter($pool, fn($c) => !in_array(CardType($c), ['Leader', 'Base'], true)));
$pool     = SWULimitedOpenSealedPool(array_fill(0, 6, 'SOR'), SWULimitedRng(3));
$leader   = array_values(array_filter($pool, $isLeader))[0];
$sorBase  = SWULimitedCommonBasesInPlay(['SOR'])[0];
$cards    = $drawable($pool);
$deck30   = array_slice($cards, 0, 30);

echo "LegalSealedDeck:\n";
$errs = SWUCheckLimitedDeck($pool, ['SOR'], $leader, $sorBase, $deck30);
$check($errs === [], 'pool leader + SOR common base + 30 pool cards is legal: ' . implode('; ', $errs));

echo "LeaderMustBeFromPool:\n";
$sorLeaders = array_values(array_filter(GetAllCardIds(), fn($c) => CardSet($c) === 'SOR' && $isLeader($c) && !in_array($c, $pool, true)));
$check(SWUCheckLimitedDeck($pool, ['SOR'], $sorLeaders[0], $sorBase, $deck30) !== [], "a leader not in the pool ({$sorLeaders[0]}) is rejected");
$check(SWUCheckLimitedDeck($pool, ['SOR'], '', $sorBase, $deck30) !== [], 'no leader is rejected');

echo "BaseRules:\n";
$hmwBase = SWULimitedCommonBasesInPlay(['HMW'])[0];
$check(SWUCheckLimitedDeck($pool, ['SOR'], $leader, $hmwBase, $deck30) !== [], 'a common base of a set NOT in play is rejected');
$check(SWUCheckLimitedDeck($pool, ['SOR', 'HMW'], $leader, $hmwBase, $deck30) === [], 'the same base is legal once its set is in play');
$sorRareBases = array_values(array_filter(GetAllCardIds(), fn($c) => CardSet($c) === 'SOR' && $isBase($c) && CardRarity($c) === 'Rare'));
$unopened = array_values(array_diff($sorRareBases, $pool));
$check($unopened !== [] && SWUCheckLimitedDeck($pool, ['SOR'], $leader, $unopened[0], $deck30) !== [], 'a rare base not in the pool is rejected');
$rarePool = null; $rareBase = null;
for ($seed = 1; $seed <= 500 && $rarePool === null; $seed++) {
    $p = SWULimitedOpenSealedPool(array_fill(0, 6, 'SOR'), SWULimitedRng($seed));
    $hit = array_values(array_intersect($p, $sorRareBases));
    if ($hit) { $rarePool = $p; $rareBase = $hit[0]; }
}
$check($rarePool !== null, 'some SOR sealed pool in seeds 1..500 opened a rare base');
if ($rarePool !== null) {
    $rl = array_values(array_filter($rarePool, $isLeader))[0];
    $check(SWUCheckLimitedDeck($rarePool, ['SOR'], $rl, $rareBase, array_slice($drawable($rarePool), 0, 30)) === [], "an opened rare base ($rareBase) is legal");
}

echo "MinimumThirty:\n";
$check(SWUCheckLimitedDeck($pool, ['SOR'], $leader, $sorBase, array_slice($cards, 0, 29)) !== [], '29 cards is rejected');
$check(SWUCheckLimitedDeck($pool, ['SOR'], $leader, $sorBase, array_slice($cards, 0, 30)) === [], '30 cards is legal');

echo "DeckSizeModifierBasesApply:\n";
$jtl = SWULimitedOpenSealedPool(array_fill(0, 6, 'JTL'), SWULimitedRng(4));
$jl  = array_values(array_filter($jtl, $isLeader))[0];
$jc  = $drawable($jtl);
$vault = array_merge($jtl, ['JTL_024']);
$check(SWUCheckLimitedDeck($vault, ['JTL'], $jl, 'JTL_024', array_slice($jc, 0, 30)) !== [], 'Data Vault (+10): 30 cards is rejected');
$check(SWUCheckLimitedDeck($vault, ['JTL'], $jl, 'JTL_024', array_slice($jc, 0, 40)) === [], 'Data Vault (+10): 40 cards is legal');
$osc = array_merge($jtl, ['JTL_025']);
$check(SWUCheckLimitedDeck($osc, ['JTL'], $jl, 'JTL_025', array_slice($jc, 0, 25)) === [], 'Thermal Oscillator (−5): 25 cards is legal');

echo "CopiesBoundedByPool:\n";
$counts = array_count_values($cards);
$single = array_search(1, $counts, true);
$twice  = array_merge(array_slice(array_values(array_filter($cards, fn($c) => $c !== $single)), 0, 28), [$single, $single]);
$errs = SWUCheckLimitedDeck($pool, ['SOR'], $leader, $sorBase, $twice);
$check($errs !== [] && strpos(implode(' ', $errs), $single) !== false, "a card held once but listed twice is rejected, naming $single");
$triple = array_merge($pool, [$single, $single]);
$three  = array_merge(array_slice(array_values(array_filter($cards, fn($c) => $c !== $single)), 0, 27), [$single, $single, $single]);
$check(SWUCheckLimitedDeck($triple, ['SOR'], $leader, $sorBase, $three) === [], 'a card held 3× may be played 3×');

echo "NoLeadersOrBasesInDrawDeck:\n";
$check(SWUCheckLimitedDeck($pool, ['SOR'], $leader, $sorBase, array_merge($deck30, [$leader])) !== [], 'a leader in the draw deck is rejected');

echo "ReprintAliasCountsAsPoolCard:\n";
$all = array_flip(GetAllCardIds());
$alias = null;
foreach (array_keys($all) as $c) {
    $canon = CardIDOverride($c);
    if ($canon !== $c && isset($all[$canon]) && !in_array(CardType($c), ['Leader', 'Base'], true)) { $alias = [$c, $canon]; break; }
}
$check($alias !== null, 'found a reprint alias pair' . ($alias ? " ({$alias[0]} → {$alias[1]})" : ''));
if ($alias !== null) {
    // Neither printing may already be in the pool, or a natural copy would satisfy the deck without the alias match.
    $bare  = array_values(array_filter($pool, fn($c) => !in_array(CardIDOverride($c), [$alias[1]], true)));
    $bareC = array_slice($drawable($bare), 0, 29);
    $check(SWUCheckLimitedDeck(array_merge($bare, [$alias[0]]), ['SOR'], $leader, $sorBase, array_merge($bareC, [$alias[1]])) === [], 'pool holds the reprint, deck lists the original: legal');
    $check(SWUCheckLimitedDeck(array_merge($bare, [$alias[1]]), ['SOR'], $leader, $sorBase, array_merge($bareC, [$alias[0]])) === [], 'pool holds the original, deck lists the reprint: legal');
    $check(SWUCheckLimitedDeck($bare, ['SOR'], $leader, $sorBase, array_merge($bareC, [$alias[1]])) !== [], 'with neither printing in the pool, the card is rejected');
}

echo "SideboardIsPoolMinusDeck:\n";
$side = SWULimitedSideboard($pool, $leader, $sorBase, $deck30);
$check(count($side) === count($cards) - 30, 'sideboard = drawable pool cards − deck (' . count($side) . ')');
$side31 = SWULimitedSideboard($pool, $leader, $sorBase, array_slice($cards, 0, 31));
$check(count($side) - count($side31) === 1, 'adding one card to the deck removes exactly one copy from the sideboard');
$check(array_filter($side, fn($c) => in_array(CardType($c), ['Leader', 'Base'], true)) === [], 'the sideboard holds no leaders or bases');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
