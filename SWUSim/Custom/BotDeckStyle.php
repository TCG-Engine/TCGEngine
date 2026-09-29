<?php
// Deck-style classifier (spec: docs/superpowers/specs/2026-09-22-swusim-deck-style-classifier-design.md).
// Pure: no game state, no network, no database. The Main Menu preselects "Bot play style" from it.
//
// SWU_BOT_AGGRO_LEADERS (the leader nudge) lives in BotResourcing.php, which loads standalone. It is required HERE
// rather than left to the caller: when it was only read "if defined", the tests scored without the nudge while the
// endpoint scored with it, so the fitted weights did not describe what production ran (caught 2026-09-22).
require_once __DIR__ . '/BotResourcing.php';

// ── Fixture naming (owner convention, 2026-09-29) ────────────────────────────────────────────────
// A fixture is named  <leader-title>_<leader-set>_<base-name-or-archetype>  and displayed as
// "Leader Title (SET) Base" — e.g. director-krennic_law_blue-splash -> "Director Krennic (LAW) Blue Splash".
//
// ⚠ THE DISPLAY NAME IS DERIVED FROM THE CARD IDs, NEVER FROM THE FILENAME. Un-slugging cannot round-trip:
// a hyphen is both the word separator AND part of a name, so "the-mandalorian" must become "The Mandalorian"
// while "obi-wan-kenobi" must stay "Obi-Wan Kenobi", and nothing in the slug distinguishes the two. The
// previous ucwords(str_replace('_',' ',$file)) also rendered the set as "Law" and lost the parentheses.
//
// The base half is the owner's four-rule archetype scheme:
//   1. a RARE (or otherwise non-common) base keeps its full printed title — "Data Vault", "Colossus".
//   2. a 28-HP LOF common is "<Colour> Force".
//   3. a 27-HP LAW common is "<Colour> Splash".
//   4. a 30-HP common is the bare colour — "Blue".
// Rules 2 and 3 are checked BEFORE 4 and are keyed on set+HP, because the colour alone cannot tell a
// 30-HP Vigilance common from a 27-HP Vigilance LAW one. Anything a rule does not cover falls back to the
// printed title rather than guessing, so a new set cannot silently produce a wrong or colliding name.
const SWU_BOT_ASPECT_COLOURS = ['Vigilance' => 'Blue', 'Command' => 'Green', 'Aggression' => 'Red',
                                'Cunning' => 'Yellow', 'Heroism' => 'White', 'Villainy' => 'Black'];

function SWUBotBaseArchetypeName(string $baseID): string {
    if ($baseID === '' || !function_exists('CardTitle')) return '';
    $title = strval(CardTitle($baseID));
    $rarity = function_exists('CardRarity') ? strval(CardRarity($baseID)) : '';
    if ($rarity !== 'Common') return $title;           // rule 1
    $hp  = function_exists('CardHp') ? intval(CardHp($baseID)) : 0;
    $set = strtoupper(explode('_', $baseID)[0]);
    $colour = '';
    if (function_exists('CardAspect')) {
        $asp = CardAspect($baseID);
        if (is_array($asp)) $asp = strval($asp[0] ?? '');
        foreach (array_map('trim', explode(',', strval($asp))) as $a) {
            if (isset(SWU_BOT_ASPECT_COLOURS[$a])) { $colour = SWU_BOT_ASPECT_COLOURS[$a]; break; }
        }
    }
    if ($colour === '') return $title;                  // no aspect to name it by — keep the printed title
    if ($set === 'LOF' && $hp === 28) return $colour . ' Force';    // rule 2
    if ($set === 'LAW' && $hp === 27) return $colour . ' Splash';   // rule 3
    if ($hp === 30) return $colour;                                 // rule 4
    return $title;
}

// "Director Krennic (LAW) Blue Splash". Empty string if the ids cannot be resolved, so a caller can fall
// back rather than render a half-built label.
function SWUBotDeckDisplayName(string $leaderID, string $baseID): string {
    if ($leaderID === '' || !function_exists('CardTitle')) return '';
    $leader = strval(CardTitle($leaderID));
    if ($leader === '') return '';
    $set = strtoupper(explode('_', $leaderID)[0]);
    $arch = SWUBotBaseArchetypeName($baseID);
    return $arch === '' ? "{$leader} ({$set})" : "{$leader} ({$set}) {$arch}";
}

// The filename half of the same convention. A single '-' is a word break; a DOUBLE '--' is a literal
// hyphen that belongs to the name (owner, 2026-09-29), so "Obi-Wan Kenobi" -> "obi--wan-kenobi" and the
// slug round-trips exactly. Protect the literal hyphens FIRST, or collapsing the other separators eats them.
const SWU_BOT_SLUG_DASH = "\x01";                       // private placeholder, never appears in a title

function SWUBotFixtureSlug(string $s): string {
    $s = str_replace("'", '', $s);                      // Daimyo's Palace -> daimyos-palace, not daimyo-s
    $s = str_replace('-', SWU_BOT_SLUG_DASH, $s);       // keep the name's own hyphens out of the collapse
    $s = preg_replace('/[^A-Za-z0-9' . SWU_BOT_SLUG_DASH . ']+/', '-', $s);
    $s = str_replace(SWU_BOT_SLUG_DASH, '--', $s);      // ...then re-emit them as the escaped form
    return strtolower(trim($s, '-'));
}

// Lower-case particles, so an un-slugged name matches how the cards are actually printed. Found by the
// round-trip guard, not by inspection: "Jabba the Hutt" came back as "Jabba The Hutt" and the two
// derivations disagreed. A particle is only lowered when it is NOT the first word — "The Mandalorian"
// keeps its capital.
const SWU_BOT_TITLE_PARTICLES = ['a','an','and','at','of','the','to','in','for','from','with','on','or','nor'];

// The exact inverse: '--' -> a literal hyphen, single '-' -> a space, then capitalise every word AND every
// letter following a literal hyphen (PHP's ucwords delimiters do the second part), then lower the particles.
function SWUBotFixtureUnslug(string $slug): string {
    $s = str_replace('--', SWU_BOT_SLUG_DASH, $slug);
    $s = str_replace('-', ' ', $s);
    $s = str_replace(SWU_BOT_SLUG_DASH, '-', $s);
    $s = ucwords($s, " -");
    $words = explode(' ', $s);
    foreach ($words as $i => $w) {
        if ($i === 0) continue;
        if (in_array(strtolower($w), SWU_BOT_TITLE_PARTICLES, true)) $words[$i] = strtolower($w);
    }
    return implode(' ', $words);
}

function SWUBotFixtureFileName(string $leaderID, string $baseID): string {
    if ($leaderID === '' || !function_exists('CardTitle')) return '';
    $set = strtolower(explode('_', $leaderID)[0]);
    return SWUBotFixtureSlug(strval(CardTitle($leaderID))) . '_' . $set . '_'
         . SWUBotFixtureSlug(SWUBotBaseArchetypeName($baseID));
}

// "director-krennic_law_blue-splash" -> "Director Krennic (LAW) Blue Splash". Because the slug round-trips,
// this and SWUBotDeckDisplayName() must agree for every fixture — which the guard test asserts both ways.
// Returns '' for a stem that is not in the convention, so a caller can fall back rather than show a mangle.
function SWUBotFixtureDisplayNameFromFile(string $stem): string {
    $parts = explode('_', $stem);
    if (count($parts) !== 3) return '';
    [$leader, $set, $base] = $parts;
    if ($leader === '' || $set === '' || $base === '') return '';
    return SWUBotFixtureUnslug($leader) . ' (' . strtoupper($set) . ') ' . SWUBotFixtureUnslug($base);
}

// Parse a BotFixtures deck file: '# ' comments, then the sections Leader / Base / Deck, each line '<count> <CardID>'.
function SWUBotDeckFromFixtureText(string $text): array {
    $sec = ''; $leader = ''; $base = ''; $cards = [];
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if ($line === 'Leader' || $line === 'Base' || $line === 'Deck') { $sec = $line; continue; }
        if (!preg_match('/^(\d+)\s+(\S+)/', $line, $m)) continue;
        $n = intval($m[1]); $id = $m[2];
        if ($sec === 'Leader') $leader = $id;
        elseif ($sec === 'Base') $base = $id;
        elseif ($sec === 'Deck') $cards[$id] = ($cards[$id] ?? 0) + $n;
    }
    return ['leader' => $leader, 'base' => $base, 'cards' => $cards];
}

// The five archetypes as a 0-4 scale (SWUSim/Custom/BotArchetypes.php holds the same order).
const SWU_DECKSTYLE_SCALE = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];

// The shape scan's weights, fitted against the owner's 23 labelled decks (2026-09-22): 16/23 exact (70%) and 23/23
// within one step, leave-one-out over the LABEL rule. ⚠ The weights themselves were fitted on all 23, so 70% is an
// optimistic estimate of how it will do on a deck nobody has labelled; the within-one-step figure is the robust one.
// bot_deckstyle_test.php re-measures both on every run.
// 'cost' fitted to 0: the curve is already carried by 'cheap' and 'big', and pricing it twice pushed every control
// list to the top of the scale. 'centre'/'scale'/'pivotCost' shape the scale itself.
const SWU_DECKSTYLE_WEIGHTS = ['cost' => 0.0, 'cheap' => 0.05, 'big' => 0.04, 'answers' => 0.03,
                               'answersUnit' => 0.02, 'draw' => 0.04, 'burn' => 0.06, 'space' => 0.25, 'baseHp' => 0.2, 'aggroLeader' => 0.5,
                               'centre' => 2.0, 'scale' => 1.0, 'pivotCost' => 3.5];

// The weights in force, with $GLOBALS['SWUDeckStyleWeightOverride'] applied — the fitter sweeps them without
// editing this file. Unset in normal use, so the constants above are what runs.
function SWUBotDeckStyleWeights(): array {
    return array_merge(SWU_DECKSTYLE_WEIGHTS, (array)($GLOBALS['SWUDeckStyleWeightOverride'] ?? []));
}

// Deck shape, read from the printed cards. Mirrors SWUSim/DevTools/deck_features.php, which stays as the CLI tool.
function SWUBotDeckFeatures(array $deck): array {
    $base = strval($deck['base'] ?? '');
    // 'removal'/'wipe' are the totals; the *Event / *Unit halves are what the score prices. A 2-cost body that kills
    // something on arrival (Karis) is not the same card as a removal SPELL held for a threat: counting them together
    // read Maul Blue Force — 12 removal UNITS, 3 removal events — as a control deck (owner, 2026-09-22).
    $f = ['n' => 0, 'units' => 0, 'events' => 0, 'upgrades' => 0, 'cheap' => 0, 'big' => 0, 'costSum' => 0,
          'removal' => 0, 'removalEvent' => 0, 'removalUnit' => 0, 'wipe' => 0, 'wipeEvent' => 0, 'wipeUnit' => 0,
          'burn' => 0, 'draw' => 0, 'space' => 0, 'avgCost' => 0.0,
          'baseHp' => $base !== '' ? intval(CardHp($base)) : 0, 'baseAspect' => $base !== '' ? strval(CardAspect($base)) : ''];
    foreach ((array)($deck['cards'] ?? []) as $id => $n) {
        $n = intval($n);
        $f['n'] += $n;
        $type = strval(CardType($id));
        $cost = intval(CardCost($id));
        $f['costSum'] += $n * $cost;
        if (str_contains($type, 'Unit')) {
            $f['units'] += $n;
            if ($cost <= 2) $f['cheap'] += $n;
            if ($cost >= 6) $f['big'] += $n;
            if (str_contains(strval(CardArena($id) ?? ''), 'Space')) $f['space'] += $n;
        } elseif (str_contains($type, 'Event')) $f['events'] += $n;
        elseif (str_contains($type, 'Upgrade')) $f['upgrades'] += $n;
        $isUnit = str_contains($type, 'Unit');
        // Tags v3 split 'indirect-damage' out of 'burn' (owner ruling 2026-09-28). For the deck's SHAPE the two
        // are the same axis — reach that does not need a board — so indirect folds back into the burn bucket and
        // the 0.06 weight is unchanged. De-duplicated first: a card tagged BOTH would otherwise count twice.
        $shape = [];
        foreach (SWUBotCardTags(strval($id)) as $t) {
            $shape[$t === 'indirect-damage' ? 'burn' : $t] = true;
        }
        foreach (array_keys($shape) as $t) {
            if (!in_array($t, ['removal', 'wipe', 'burn', 'draw'], true)) continue;
            $f[$t] += $n;
            if ($t === 'removal' || $t === 'wipe') $f[$t . ($isUnit ? 'Unit' : 'Event')] += $n;
        }
    }
    $f['avgCost'] = $f['n'] ? round($f['costSum'] / $f['n'], 2) : 0.0;
    return $f;
}

// Where the deck sits on the 0-4 scale. Starts at midrange (2.0); aggro terms subtract, control terms add.
function SWUBotDeckShapeScore(array $deck): float {
    $f = SWUBotDeckFeatures($deck);
    if ($f['n'] === 0) return 2.0;
    $w = SWUBotDeckStyleWeights();
    $s = 0.0;
    $s += $w['cost'] * ($f['avgCost'] - $w['pivotCost']);
    $s -= $w['cheap'] * max(0, $f['cheap'] - 12);
    $s += $w['big'] * max(0, $f['big'] - 6);
    // Answers: a removal EVENT or a wipe is control's currency; a unit that removes on arrival is a body first.
    $s += $w['answers'] * max(0, ($f['removalEvent'] + 2 * $f['wipeEvent'] + $f['wipeUnit']) - 4);
    $s += $w['answersUnit'] * $f['removalUnit'];
    $s += $w['draw'] * $f['draw'];
    $s -= $w['burn'] * $f['burn'];
    if ($f['units'] > 0 && $f['space'] / $f['units'] >= 0.6) $s -= $w['space'];
    if ($f['baseHp'] >= 30) $s += $w['baseHp'];
    // A leader whose lists are labelled aggro nudges, never decides (owner: an off-meta tempo Vader must not read
    // as Hyper Aggro).
    if (in_array(strval($deck['leader'] ?? ''), SWU_BOT_AGGRO_LEADERS, true)) $s -= $w['aggroLeader'];
    return max(0.0, min(4.0, $w['centre'] + $w['scale'] * $s));
}

// The style a score rounds to, and how far it sits from the boundary between two styles. The spec states the
// distance FROM THE BOUNDARY (>= 0.30 high, >= 0.15 medium); $d below is measured from the CENTRE, so the same
// thresholds read as 0.5 - 0.30 = 0.20 and 0.5 - 0.15 = 0.35.
function SWUBotStyleFromScore(float $score): array {
    $score = max(0.0, min(4.0, $score));
    $i = (int)round($score);
    $d = abs($score - $i);            // 0 = dead centre, 0.5 = on the boundary
    return ['style' => SWU_DECKSTYLE_SCALE[$i], 'confidence' => $d <= 0.20 ? 'high' : ($d <= 0.35 ? 'medium' : 'low')];
}

// A labelled deck whose card overlap reaches this takes its label outright (owner, 2026-09-22: "75% similarity is
// enough to instant label it. if it goes farther off, then scan").
const SWU_DECKSTYLE_LABEL_OVERLAP = 0.75;

function SWUBotDeckLabelRegistry(): array {
    static $decks = null;
    if ($decks === null) {
        $raw = @file_get_contents(__DIR__ . '/BotDeckLabels.json');
        $j = is_string($raw) ? json_decode($raw, true) : null;
        $decks = is_array($j) && isset($j['decks']) && is_array($j['decks']) ? $j['decks'] : [];
    }
    return $decks;
}

// Shared main-deck cards, counting copies, over the larger list (lists run 45-60 cards by base).
function SWUBotDeckOverlap(array $a, array $b): float {
    $shared = 0;
    foreach ($a as $id => $n) $shared += min(intval($n), intval($b[$id] ?? 0));
    $size = max(array_sum($a), array_sum($b));
    return $size > 0 ? $shared / $size : 0.0;
}

// The archetype of a deck: ['leader' => CardID, 'base' => CardID, 'cards' => [CardID => count]].
// $registry overrides the label set (the leave-one-out test hides a deck from itself).
function SWUBotDeckStyle(array $deck, ?array $registry = null): array {
    $cards = (array)($deck['cards'] ?? []);
    if (strval($deck['leader'] ?? '') === '' || array_sum($cards) === 0) {
        return ['style' => null, 'confidence' => 'low', 'source' => 'none', 'reasons' => ['no deck'], 'score' => null];
    }
    $best = null; $bestOverlap = 0.0;
    foreach ($registry ?? SWUBotDeckLabelRegistry() as $d) {
        if (strval($d['leader'] ?? '') !== strval($deck['leader'])) continue;
        $o = SWUBotDeckOverlap($cards, (array)($d['cards'] ?? []));
        if ($o > $bestOverlap) { $bestOverlap = $o; $best = $d; }
    }
    if ($best !== null && $bestOverlap >= SWU_DECKSTYLE_LABEL_OVERLAP) {
        return ['style' => strval($best['style']), 'confidence' => 'high', 'source' => 'label',
                'reasons' => [sprintf('matches %s (%d%% of cards)', $best['file'], (int)round(100 * $bestOverlap))], 'score' => null];
    }
    $score = SWUBotDeckShapeScore($deck);
    $r = SWUBotStyleFromScore($score);
    $f = SWUBotDeckFeatures($deck);
    $reasons = [sprintf('shape score %.2f (avg cost %.2f, %d cheap, %d big, %d removal, %d wipe, %d draw)',
        $score, $f['avgCost'], $f['cheap'], $f['big'], $f['removal'], $f['wipe'], $f['draw'])];
    if ($best !== null) $reasons[] = sprintf('nearest labelled list %s is only %d%%', $best['file'], (int)round(100 * $bestOverlap));
    return ['style' => $r['style'], 'confidence' => $r['confidence'], 'source' => 'shape', 'reasons' => $reasons, 'score' => $score];
}
