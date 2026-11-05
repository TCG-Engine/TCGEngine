<?php
// Limited deck rules — a deck built only from the player's own pool.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.4, §3.1, §3.4.
//
// Deliberately NOT SWUCheckFormat(): Constructed's sideboard cap of 10 and copy limits don't apply here, and it has
// no notion of a pool. Every comparison goes through CardIDOverride, so a reprint (TWI_077) and its original
// (SOR_078) are the same card on both sides.
require_once __DIR__ . '/LimitedSettings.php';
if (!function_exists('CardIDOverride')) require_once __DIR__ . '/../../AppCore/SWU/Overrides.php';
if (!function_exists('SWUGlobalDeckSizeModifiers')) require_once __DIR__ . '/../../AppCore/SWU/Formats.php';

const SWU_LIMITED_MIN_DECK = 30;

// Every problem with the deck, as short messages for the builder. [] = legal. $pool and $mainDeck are expanded card
// lists (one entry per copy); $setsInPlay decides which common bases are free to use.
//   leader   exactly one, from the pool
//   base     any Common base of a set in play, or any base in the pool
//   deck     at least 30 (plus a base's printed deck-size modifier: JTL_024 +10, JTL_025 −5), no leaders or
//            bases, and never more copies of a card than the pool holds
function SWUCheckLimitedDeck(array $pool, array $setsInPlay, string $leader, string $base, array $mainDeck): array {
    $errors = [];
    $have = array_count_values(array_map('CardIDOverride', $pool));
    $owns = fn(string $c) => ($have[CardIDOverride($c)] ?? 0) > 0;

    if ($leader === '' || CardType($leader) !== 'Leader') $errors[] = 'Choose a leader from your pool.';
    elseif (!$owns($leader)) $errors[] = "Leader $leader is not in your pool.";

    $freeBases = array_map('CardIDOverride', SWULimitedCommonBasesInPlay($setsInPlay));
    if ($base === '' || CardType($base) !== 'Base') $errors[] = 'Choose a base.';
    elseif (!in_array(CardIDOverride($base), $freeBases, true) && !$owns($base)) {
        $errors[] = "Base $base is not in your pool or a common base of the sets in play.";
    }

    $min = SWU_LIMITED_MIN_DECK + (SWUGlobalDeckSizeModifiers()[CardIDOverride($base)] ?? 0);
    if (count($mainDeck) < $min) $errors[] = "Your deck needs at least $min cards (it has " . count($mainDeck) . ').';

    foreach (array_count_values(array_map('CardIDOverride', $mainDeck)) as $c => $n) {
        if (in_array(CardType($c), ['Leader', 'Base'], true)) { $errors[] = "$c is a " . strtolower(CardType($c)) . ' and cannot go in your deck.'; continue; }
        $held = $have[$c] ?? 0;
        if ($n > $held) $errors[] = $held === 0 ? "$c is not in your pool." : "Your pool has $held of $c, but the deck lists $n.";
    }
    return $errors;
}

// The pool's leader- and base-free cards that the deck isn't using — the builder's left pane and the Bo3 sideboard.
// Returned as the pool's own card IDs, one entry per copy.
function SWULimitedSideboard(array $pool, string $leader, string $base, array $mainDeck): array {
    $used = array_count_values(array_map('CardIDOverride', $mainDeck));
    $side = [];
    foreach ($pool as $c) {
        if (in_array(CardType($c), ['Leader', 'Base'], true)) continue;
        $canon = CardIDOverride($c);
        if (($used[$canon] ?? 0) > 0) { $used[$canon]--; continue; }
        $side[] = $c;
    }
    return $side;
}

// A deck error as a player reads it: every card ID becomes its title ("Darth Vader, Dark Lord of the Sith"), so nobody is
// told "SOR_010 is not in your pool". IDs with no title in the dictionary are left as they are.
function SWULimitedErrorText(string $error): string {
    return preg_replace_callback('/\b[A-Z][A-Z0-9]*_\d{3}\b/', function ($m) {
        $title = strval(CardTitle($m[0]) ?? '');
        if ($title === '') return $m[0];
        $sub = strval(CardSubtitle($m[0]) ?? '');
        return $sub !== '' ? "$title, $sub" : $title;
    }, $error);
}

// What the builder needs to show, search and filter each card, keyed by ID. Aspects and traits are lists (the dictionary
// stores them comma-separated); cost is null for a base.
function SWULimitedCardFacts(array $ids): array {
    $list = fn($v) => array_values(array_filter(array_map('trim', explode(',', strval($v ?? ''))), 'strlen'));
    $out = [];
    foreach (array_unique($ids) as $id) {
        $cost = CardCost($id);
        $out[$id] = ['title' => strval(CardTitle($id) ?? ''), 'subtitle' => strval(CardSubtitle($id) ?? ''),
                     'cost' => ($cost === null || $cost === '') ? null : intval($cost), 'aspects' => $list(CardAspect($id)),
                     'type' => strval(CardType($id) ?? ''), 'arena' => strval(CardArena($id) ?? ''), 'traits' => $list(CardTrait($id)),
                     'text' => strval(CardText($id) ?? ''), 'rarity' => strval(CardRarity($id) ?? ''), 'set' => strval(CardSet($id) ?? '')];
    }
    return $out;
}

// A built deck ({leader, base, main[]}) as the standard deck JSON SWUResolveDeckInput reads — what a Limited seat's
// Player::deckLink holds once Ready, so the child match resolves it like any other deck. The sideboard is the unused rest
// of the pool (spec §3.4: the same JSON re-imports as a Constructed deck).
function SWULimitedDeckJson(array $deck, array $pool): string {
    $entries = function (array $cards) {
        $out = [];
        foreach (array_count_values($cards) as $id => $n) $out[] = ['id' => strval($id), 'count' => $n];
        return $out;
    };
    $main = array_values(array_map('strval', $deck['main'] ?? []));
    return json_encode(['leader' => ['id' => strval($deck['leader'] ?? '')], 'base' => ['id' => strval($deck['base'] ?? '')],
                        'deck' => $entries($main),
                        'sideboard' => $entries(SWULimitedSideboard($pool, strval($deck['leader'] ?? ''), strval($deck['base'] ?? ''), $main))]);
}

// A resolved deck (SWUResolveDeckInput / a match's currentDeck) back in the builder's {leader, base, main[]} shape.
function SWULimitedDeckFromResolved(array $resolved): array {
    $leader = $resolved['leader'] ?? '';
    return ['leader' => is_array($leader) ? strval($leader[0] ?? '') : strval($leader), 'base' => strval($resolved['base'] ?? ''),
            'main' => array_values(array_map('strval', $resolved['mainDeck'] ?? []))];
}
