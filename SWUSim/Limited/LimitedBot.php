<?php
// Limited bots — building a deck from a pool (spec docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md
// §3.7). Drafting is pure and lives in LimitedDraftState.php (LTDraftBotPickCard); building needs card data, so this file
// is loaded at FILE scope by the Limited endpoints only — never by the lobby poll.
//
// A heuristic, so no unit test pins what it builds (owner rule 2026-10-12): limited_deck_test.php checks only that every
// deck it returns is LEGAL, and the bot runs (DevTools/SWUSimLimitedBotPods.php) judge the decks.
require_once __DIR__ . '/LimitedSettings.php';   // SWULimitedCardValue, SWULimitedCommonBasesInPlay
require_once __DIR__ . '/LimitedDeck.php';       // SWUCheckLimitedDeck
require_once __DIR__ . '/LimitedRoomState.php';  // LT_BOT_ONE_DROP_LEADERS (pure)
require_once __DIR__ . '/../Rl/CardTags.php';    // SWUBotCardTags — the deck-style classifier reads them
require_once __DIR__ . '/../Custom/BotDeckStyle.php';

const LT_BOT_PIP_COST      = 2.0;   // an aspect penalty: 2 resources per missing pip
const LT_BOT_SPLASH_PIP_COST = 1.0; // with a splash base: a card exactly one V/C/A/Cunning pip off (the Epic Action waives one)
const LT_BOT_BASE_HP_COST  = 0.2;   // per HP a base has under 30 — a LAW splash base (27) costs 0.6: "it normally is worth it" (owner)
const LT_BOT_SPLASH_MAX    = 3;     // consistency: at most this many one-pip-off splash cards in a deck
// A cheap splash unit IS a turn-1 play (owner 2026-11-05): the base's Epic Action plays it on turn 1 with no penalty —
// Toydarian Technician, Lepi Lookout, Chio Fain, Devaronian Doorbuster, the 2-drop ships — so it is favoured too.
const LT_BOT_SPLASH_CHEAP_COST = 0.5;
// The shape rule (owner, 2026-11-05): 8–11 TURN-1 PLAYS — units costing 1 or 2 with NO aspect penalty against the deck's
// leader + base (an off-aspect cheap unit is no turn-1 play) — aiming for 9–10, and 20+ units.
const LT_BOT_EARLY_MIN     = 8;
const LT_BOT_EARLY_MAX     = 11;
const LT_BOT_EARLY_TARGET  = 10;    // the builder fills turn-1 plays to here first (9–10 is the aim)
const LT_BOT_UNIT_MIN      = 20;
const LT_BOT_EARLY_FLOOR   = -1.0;  // a turn-1 play worse than this is not taken just for the curve
const LT_BOT_SHAPE_BONUS   = 6.0;   // comparing leader × base: a deck that meets the shape rule wins by this much (tuned, Task 6)
const LT_BOT_TARGET_BONUS  = 2.0;   // …and by this much more when it lands on 9–10
// Deck size (owner 2026-11-05): a pool can be too good to stop at the minimum — aim for 30–31, at most 34 (minimum + 4).
const LT_BOT_EXTRA_MAX    = 4;     // cards past the base's minimum, at most
const LT_BOT_EXTRA_FIRST  = 1.0;   // the first extra card must score this much (value − pip penalty, in resources)…
const LT_BOT_EXTRA_MORE   = 2.0;   // …and each one after it this much

function _LTBotAspects(string $id): array {
    return array_values(array_filter(array_map('trim', explode(',', strval(CardAspect($id) ?? ''))), 'strlen'));
}

// How many of $card's aspect pips the leader + base do not cover (a multiset: a double-pip card needs both).
function _LTBotMissingPips(array $cardPips, array $havePips): int {
    $have = array_count_values($havePips);
    $missing = 0;
    foreach (array_count_values($cardPips) as $a => $n) $missing += max(0, $n - ($have[$a] ?? 0));
    return $missing;
}

// Is $card's one missing pip (against $have) a V/C/A/Cunning one — the kind a splash base's Epic Action waives?
function _LTBotMissingIsSplashable(array $cardPips, array $havePips): bool {
    $have = array_count_values($havePips);
    foreach (array_count_values($cardPips) as $a => $n) if ($n > ($have[$a] ?? 0)) return !in_array($a, ['Heroism', 'Villainy'], true);
    return false;
}

// The best legal deck a bot can build from $pool: for each pool leader × each base in reach (the common bases of the sets
// in play plus the pool's own), every card scored as its value − LT_BOT_PIP_COST × missing pips; then 2-cost units up to
// LT_BOT_EARLY_TARGET, units up to LT_BOT_UNIT_MIN, the best of the rest to the base's minimum size, then up to
// LT_BOT_EXTRA_MAX more while they score high (30–31 is the aim, 34 the most). The (leader,
// base) with the highest total whose deck passes SWUCheckLimitedDeck wins. Returns [leader, base, main[], style], or null
// when no legal deck exists.
function SWULimitedBotBuildDeck(array $pool, array $setsInPlay): ?array {
    $leaders = array_values(array_unique(array_filter($pool, fn($c) => CardType($c) === 'Leader')));
    $ownBases = array_filter($pool, fn($c) => CardType($c) === 'Base');
    // One base per distinct aspect set — and per kind: a splash base (a LAW common base) plays differently — is enough to
    // compare (the deck's value only sees its pips, its HP and whether it can splash).
    $bases = [];
    foreach (array_merge(SWULimitedCommonBasesInPlay($setsInPlay), $ownBases) as $b) {
        $k = implode(',', _LTBotAspects($b)) . (SWULimitedIsSplashBase($b) ? '|splash' : '');
        if (!isset($bases[$k])) $bases[$k] = $b;
    }
    $cards = array_values(array_filter($pool, fn($c) => !in_array(CardType($c), ['Leader', 'Base'], true)));
    if (!$leaders || !$bases || !$cards) return null;
    $value = [];
    foreach (array_unique($cards) as $c) $value[$c] = SWULimitedCardValue($c);
    $best = null; $bestTotal = -INF;
    foreach ($leaders as $leader) {
        foreach ($bases as $base) {
            $have = array_merge(_LTBotAspects($leader), _LTBotAspects($base));
            $splashBase = SWULimitedIsSplashBase($base);
            $scored = [];
            foreach ($cards as $i => $c) {
                $miss = _LTBotMissingPips(_LTBotAspects($c), $have);
                $unit = CardType($c) === 'Unit';
                $penalty = LT_BOT_PIP_COST * $miss;
                $splash = $splashBase && $miss === 1 && _LTBotMissingIsSplashable(_LTBotAspects($c), $have);
                $cheap = $unit && intval(CardCost($c)) <= 2;
                if ($splash) $penalty = $cheap ? LT_BOT_SPLASH_CHEAP_COST : LT_BOT_SPLASH_PIP_COST;
                $scored[] = ['id' => $c, 'i' => $i, 's' => $value[$c] - $penalty, 'unit' => $unit, 'splash' => $splash, 'cost' => intval(CardCost($c)),
                             'two' => $cheap && ($miss === 0 || $splash)];   // a turn-1 play: cheap, in aspect or a splash
            }
            usort($scored, fn($x, $y) => $y['s'] <=> $x['s'] ?: $x['i'] <=> $y['i']);
            $size = SWU_LIMITED_MIN_DECK + (SWUGlobalDeckSizeModifiers()[CardIDOverride($base)] ?? 0);
            if (count($scored) < $size) continue;
            $taken = []; $units = 0; $twos = 0; $splashes = 0;
            $take = function (array $e) use (&$taken, &$units, &$twos, &$splashes) { $taken[$e['i']] = $e; if ($e['splash']) $splashes++; if ($e['unit']) { $units++; if ($e['two']) $twos++; } };
            $splashFull = function (array $e) use (&$splashes) { return $e['splash'] && $splashes >= LT_BOT_SPLASH_MAX; };
            // Turn-1 plays: 2-drops first (the better play), 1-drops only to reach the minimum — unless the leader plays two
            // units at once (General Grievous, HMW), when 1-drops rank with them by score (owner 2026-11-05).
            $oneDropLeader = in_array(CardIDOverride($leader), LT_BOT_ONE_DROP_LEADERS, true);
            foreach ($scored as $e) if (count($taken) < $size && $e['two'] && ($oneDropLeader || $e['cost'] >= 2) && $twos < LT_BOT_EARLY_TARGET && $e['s'] >= LT_BOT_EARLY_FLOOR && !$splashFull($e)) $take($e);
            if (!$oneDropLeader) foreach ($scored as $e) if (count($taken) < $size && !isset($taken[$e['i']]) && $e['two'] && $twos < LT_BOT_EARLY_MIN && $e['s'] >= LT_BOT_EARLY_FLOOR && !$splashFull($e)) $take($e);
            foreach ($scored as $e) if (count($taken) < $size && !isset($taken[$e['i']]) && $e['unit'] && $units < LT_BOT_UNIT_MIN && !($e['two'] && $twos >= LT_BOT_EARLY_MAX) && !$splashFull($e)) $take($e);
            foreach ($scored as $e) if (count($taken) < $size && !isset($taken[$e['i']]) && !($e['two'] && $twos >= LT_BOT_EARLY_MAX) && !$splashFull($e)) $take($e);
            foreach ($scored as $e) if (count($taken) < $size && !isset($taken[$e['i']])) $take($e);   // only if nothing else is left
            // Past the minimum: keep the next-best card while it is good enough to play over the 30th (never a turn-1 play
            // beyond the cap). The extras do not add to the comparison total, so a bigger deck is never preferred for size.
            $extra = 0;
            foreach ($scored as $e) {
                if ($extra >= LT_BOT_EXTRA_MAX || isset($taken[$e['i']]) || ($e['two'] && $twos >= LT_BOT_EARLY_MAX) || $splashFull($e)) continue;
                if ($e['s'] < ($extra === 0 ? LT_BOT_EXTRA_FIRST : LT_BOT_EXTRA_MORE)) break;   // sorted best-first: nothing later is better
                $take($e);
                $extra++;
            }
            $total = array_sum(array_column(array_slice(array_values($taken), 0, $size), 's'))
                   - LT_BOT_BASE_HP_COST * max(0, 30 - intval(CardHp($base)));   // a 27 HP splash base must earn its splashes
            if ($units >= LT_BOT_UNIT_MIN && $twos >= LT_BOT_EARLY_MIN && $twos <= LT_BOT_EARLY_MAX) {
                $total += LT_BOT_SHAPE_BONUS + (($twos === 9 || $twos === 10) ? LT_BOT_TARGET_BONUS : 0.0);
            }
            if ($total <= $bestTotal) continue;
            $main = array_column(array_values($taken), 'id');
            if (SWUCheckLimitedDeck($pool, $setsInPlay, $leader, $base, $main) !== []) continue;
            $bestTotal = $total;
            $best = ['leader' => $leader, 'base' => $base, 'main' => $main];
        }
    }
    if ($best === null) return null;
    $style = SWUBotDeckStyle(['leader' => $best['leader'], 'base' => $best['base'], 'cards' => array_count_values($best['main'])])['style'] ?? null;
    $best['style'] = in_array($style, ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'], true) ? $style : 'midrange';
    return $best;
}

// [pid => deck|null] for every live bot seat of a room in phase 'building' that has not submitted yet — computed outside
// the lobby lock (SWULimitedTick), then applied with LTBotSubmit.
function SWULimitedBotBuilds(array $lt): array {
    if ($lt['phase'] !== 'building') return [];
    $out = [];
    foreach (LTLiveSeats($lt) as $p) {
        if (!LTIsBot($lt, $p) || !empty($lt['seats'][$p]['submitted'])) continue;
        $out[$p] = SWULimitedBotBuildDeck($lt['pools'][$p] ?? [], $lt['setsInPlay'] ?? []);
    }
    return $out;
}
