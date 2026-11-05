<?php
// Limited booster packs — slot pools derived from the generated card dictionaries, and the 16-card pack generator.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.1–2.3, §4.2.
//
// Nothing here is a per-set table: every slot pool comes from CardRarity / CardType / CardSet, so a new set's packs
// follow from its card data plus one AppCore/SWU/BoosterSets.php entry. Special-rarity cards (starter decks,
// spotlight) never enter a normal slot; that alone keeps LAW_023 Great Pit of Carkoon out of packs.
require_once __DIR__ . '/../../AppCore/SWU/BoosterSets.php';
if (!function_exists('GetAllCardIds')) require_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';

// The card pools each booster slot draws from, for one booster set. Lists of card IDs:
//   leaderCommon / leaderRare   Leaders of that rarity (Special leaders are starter-deck only)
//   base                        the base slot: Common bases, or every non-Special base once the set stopped
//                               putting rare bases in the rare slot (ASH on)
//   common / uncommon           non-leader, non-base cards
//   rare                        non-leader Rares, plus Rare bases while the set still opens them in the rare slot
//   legendary                   non-leader Legendaries
//   foilCommon … foilLegendary  non-leader, non-base cards (a foil is never a leader or a base)
//   special                     non-leader, non-base Specials — only the JTL+ foil / hyperspace S rolls use it
function SWULimitedSlotPools(string $set): array {
    static $cache = [];
    if (isset($cache[$set])) return $cache[$set];
    if (!SWUIsBoosterSet($set)) throw new InvalidArgumentException("$set has no booster packs");

    $rareSlotBases = SWUBoosterSetRareSlotBases($set);
    $p = array_fill_keys(['leaderCommon', 'leaderRare', 'base', 'common', 'uncommon', 'rare', 'legendary',
                          'foilCommon', 'foilUncommon', 'foilRare', 'foilLegendary', 'special'], []);
    foreach (GetAllCardIds() as $c) {
        if (CardSet($c) !== $set) continue;
        $type = (string)CardType($c);
        if (strpos($type, 'Token') !== false || !empty(DecomposeCardID($c)['isToken'])) continue;
        $rarity = (string)CardRarity($c);
        if ($type === 'Leader') {
            if ($rarity === 'Common') $p['leaderCommon'][] = $c;
            elseif ($rarity === 'Rare') $p['leaderRare'][] = $c;
            continue;
        }
        if ($type === 'Base') {
            if ($rarity === 'Special') continue;
            if ($rarity === 'Common' || !$rareSlotBases) $p['base'][] = $c;
            elseif ($rarity === 'Rare') $p['rare'][] = $c;
            continue;
        }
        switch ($rarity) {
            case 'Common':    $p['common'][] = $c;    $p['foilCommon'][] = $c;    break;
            case 'Uncommon':  $p['uncommon'][] = $c;  $p['foilUncommon'][] = $c;  break;
            case 'Rare':      $p['rare'][] = $c;      $p['foilRare'][] = $c;      break;
            case 'Legendary': $p['legendary'][] = $c; $p['foilLegendary'][] = $c; break;
            case 'Special':   $p['special'][] = $c;   break;
        }
    }
    return $cache[$set] = $p;
}

// Pull rates per collation era (spec §2.3). Each is the chance per pack:
//   rareLeader     the leader slot is Rare — owner: 1 in 6 "until someone complains" (no published figure)
//   legendary      the rare slot is Legendary — official design targets: 1 in 8 SOR–TWI, 1 in 5 from JTL
//   foilRare / foilLegendary   PorgDepot "Expected Value" (2026-04-15), plain foil + hyperspace-foil combined
//                  because art is cosmetic here; LAW+ foils are always hyperspace-foil
//   foilSpecial / hyperSpecial  owner: starter/spotlight S cards are "as rare as legendaries" in the slot they
//                  take — the foil-Legendary rate, and PorgDepot's hyperspace-Legendary rate. JTL on only.
// The foil's remaining chance is Common or Uncommon, split by how many of each the set has.
function SWULimitedEraOdds(string $era): array {
    $eras = [
        'classic' => ['rareLeader' => 1 / 6, 'legendary' => 1 / 8, 'foilRare' => 0.097, 'foilLegendary' => 0.035, 'foilSpecial' => 0.0,   'hyperSpecial' => 0.0],
        'jtl'     => ['rareLeader' => 1 / 6, 'legendary' => 1 / 5, 'foilRare' => 0.097, 'foilLegendary' => 0.033, 'foilSpecial' => 0.033, 'hyperSpecial' => 1 / 53],
        'law'     => ['rareLeader' => 1 / 6, 'legendary' => 1 / 5, 'foilRare' => 0.042, 'foilLegendary' => 0.010, 'foilSpecial' => 0.010, 'hyperSpecial' => 1 / 48],
    ];
    if (!isset($eras[$era])) throw new InvalidArgumentException("Unknown collation era $era");
    return $eras[$era];
}

// The only place a Limited RNG is built — never mix mt_rand / random_int into pack opening.
// ⚠ A seed reproduces the same packs only for an UNCHANGED card dictionary: pools are lists in GetAllCardIds()
// order, so a regen that adds, drops or re-rarities a booster-set card shifts every pick. A room must PERSIST what
// it opened (packs, pools, rolled lineups); the seed is for tests and debugging, never the record.
function SWULimitedRng(int $seed): \Random\Randomizer {
    return new \Random\Randomizer(new \Random\Engine\Mt19937($seed));
}

// Opens one 16-card booster of $set, in slot order: leader, base, 9 commons (in JTL+ the last may be an S card,
// slot 'hyperspace'), 3 uncommons, the rare/legendary, the foil. Each entry is ['id' => cardID, 'slot' => slot].
// Odds are independent per pack (no box guarantees). No card repeats within the commons, uncommons and rare; the
// foil may repeat one of them.
function SWULimitedOpenPack(string $set, \Random\Randomizer $rng): array {
    $pools = SWULimitedSlotPools($set);
    $odds  = SWULimitedEraOdds(SWUBoosterSetEra($set));
    $one   = fn(array $pool) => $pool[$rng->pickArrayKeys($pool, 1)[0]];
    $many  = fn(array $pool, int $n) => array_map(fn($k) => $pool[$k], $rng->pickArrayKeys($pool, $n));
    $roll  = fn() => $rng->getInt(0, 999999999) / 1e9;   // [0, 1) — Randomizer::getFloat() is PHP 8.3, prod runs 8.2

    $pack = [];
    $pack[] = ['id' => $one($roll() < $odds['rareLeader'] ? $pools['leaderRare'] : $pools['leaderCommon']), 'slot' => 'leader'];
    $pack[] = ['id' => $one($pools['base']), 'slot' => 'base'];
    $commons = $many($pools['common'], 9);
    if ($pools['special'] && $roll() < $odds['hyperSpecial']) {
        array_pop($commons);
        $hyperspace = $one($pools['special']);
    }
    foreach ($commons as $c) $pack[] = ['id' => $c, 'slot' => 'common'];
    if (isset($hyperspace)) $pack[] = ['id' => $hyperspace, 'slot' => 'hyperspace'];
    foreach ($many($pools['uncommon'], 3) as $c) $pack[] = ['id' => $c, 'slot' => 'uncommon'];
    $pack[] = ['id' => $one($roll() < $odds['legendary'] ? $pools['legendary'] : $pools['rare']), 'slot' => 'rare'];

    $r = $roll();
    $special = $pools['special'] ? $odds['foilSpecial'] : 0.0;
    if ($r < $special)                                                    $foilPool = $pools['special'];
    elseif ($r < $special + $odds['foilLegendary'])                       $foilPool = $pools['foilLegendary'];
    elseif ($r < $special + $odds['foilLegendary'] + $odds['foilRare'])   $foilPool = $pools['foilRare'];
    else {
        $cu = count($pools['foilCommon']) + count($pools['foilUncommon']);
        $foilPool = $rng->getInt(1, $cu) <= count($pools['foilCommon']) ? $pools['foilCommon'] : $pools['foilUncommon'];
    }
    $pack[] = ['id' => $one($foilPool), 'slot' => 'foil'];
    return $pack;
}
