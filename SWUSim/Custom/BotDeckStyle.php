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
// An optional 4th field is the base TRAIT, when the deck's cards care about it (2026-10-01; see
// SWUBotBaseTraitArchetype below): doctor-hemlock_hmw_yellow_naboo -> "Doctor Hemlock (HMW) Yellow (Naboo)".
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

// ── The base TRAIT (owner, 2026-10-01) ───────────────────────────────────────────────────────────
// HMW made the base's location trait matter ("While you control a Naboo base, …"), but a base's trait is only
// part of a deck's ARCHETYPE when the deck plays cards that care about it. So the trait is appended — as
// "Doctor Hemlock (HMW) Yellow (Naboo)" / doctor-hemlock_hmw_yellow_naboo — only when the leader or a MAIN-deck
// card conditions on YOUR base having that exact trait. Maul (HMW) on Shield Generator Complex (Endor) with no
// Endor card stays "Maul (HMW) Blue", and no pre-HMW fixture changes name.
// Read off the card TEXT ("you control … a/an <Trait> base"), not a hardcoded trait list, so a later set's
// locations work without a code change. "If an OPPONENT controls a … base" (Surveillance Cruiser) is not a carer.
function SWUBotTextCaresAboutBaseTrait(string $text, string $trait): bool {
    if ($trait === '') return false;
    return (bool)preg_match('/\byou control\b[^.]*?\b(?:a|an)\s+' . preg_quote($trait, '/') . '\s+base\b/i', $text);
}

function SWUBotCardCaresAboutBaseTrait(string $cardID, string $trait): bool {
    if ($trait === '' || !function_exists('CardText')) return false;
    global $deployTextData;   // a leader's deployed side counts too
    return SWUBotTextCaresAboutBaseTrait(strval(CardText($cardID)) . "\n" . strval($deployTextData[$cardID] ?? ''), $trait);
}

// The base trait that is part of this deck's archetype, or '' when no card in it cares.
function SWUBotBaseTraitArchetype(string $baseID, array $cards, string $leaderID = ''): string {
    if ($baseID === '' || !function_exists('CardTrait')) return '';
    $ids = array_keys($cards);
    if ($leaderID !== '') $ids[] = $leaderID;
    foreach (array_filter(array_map('trim', explode(',', strval(CardTrait($baseID))))) as $trait) {
        foreach ($ids as $id) if (SWUBotCardCaresAboutBaseTrait(strval($id), $trait)) return $trait;
    }
    return '';
}

// "Director Krennic (LAW) Blue Splash", or "Doctor Hemlock (HMW) Yellow (Naboo)" when the deck's cards care
// about the base trait. $cards is the MAIN deck as [cardID => count]; without it the trait is never added, which
// is exactly the pre-2026-10-01 behaviour. Empty string if the ids cannot be resolved, so a caller can fall back
// rather than render a half-built label.
function SWUBotDeckDisplayName(string $leaderID, string $baseID, array $cards = []): string {
    if ($leaderID === '' || !function_exists('CardTitle')) return '';
    $leader = strval(CardTitle($leaderID));
    if ($leader === '') return '';
    $set = strtoupper(explode('_', $leaderID)[0]);
    $arch = SWUBotBaseArchetypeName($baseID);
    $name = $arch === '' ? "{$leader} ({$set})" : "{$leader} ({$set}) {$arch}";
    $trait = SWUBotBaseTraitArchetype($baseID, $cards, $leaderID);
    return $trait === '' ? $name : "{$name} ({$trait})";
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

// <leader>_<set>_<base>, plus a 4th field _<trait> when the deck's cards care about the base trait (see
// SWUBotBaseTraitArchetype). Slugs never contain '_', so 3 vs 4 fields is unambiguous. The set stays lower-case
// like every other fixture: macOS is case-insensitive, so mixed-case names can collide on disk.
function SWUBotFixtureFileName(string $leaderID, string $baseID, array $cards = []): string {
    if ($leaderID === '' || !function_exists('CardTitle')) return '';
    $set = strtolower(explode('_', $leaderID)[0]);
    $name = SWUBotFixtureSlug(strval(CardTitle($leaderID))) . '_' . $set . '_'
          . SWUBotFixtureSlug(SWUBotBaseArchetypeName($baseID));
    $trait = SWUBotBaseTraitArchetype($baseID, $cards, $leaderID);
    return $trait === '' ? $name : $name . '_' . SWUBotFixtureSlug($trait);
}

// "director-krennic_law_blue-splash" -> "Director Krennic (LAW) Blue Splash", and
// "doctor-hemlock_hmw_yellow_naboo" -> "Doctor Hemlock (HMW) Yellow (Naboo)". Because the slug round-trips,
// this and SWUBotDeckDisplayName() must agree for every fixture — which the guard test asserts both ways.
// Returns '' for a stem that is not in the convention, so a caller can fall back rather than show a mangle.
function SWUBotFixtureDisplayNameFromFile(string $stem): string {
    $parts = explode('_', $stem);
    if (count($parts) !== 3 && count($parts) !== 4) return '';
    [$leader, $set, $base] = $parts;
    $trait = $parts[3] ?? null;
    if ($leader === '' || $set === '' || $base === '' || $trait === '') return '';
    $name = SWUBotFixtureUnslug($leader) . ' (' . strtoupper($set) . ') ' . SWUBotFixtureUnslug($base);
    return $trait === null ? $name : $name . ' (' . SWUBotFixtureUnslug($trait) . ')';
}

// Parse a BotFixtures deck file: '# ' comments, then the sections Leader / Base / Deck / Sideboard, each line
// '<count> <CardID>'. `cards` is the MAIN deck only; the sideboard comes back separately.
// ⚠ 'Sideboard' MUST be a recognised header: without it the section stays 'Deck' and the ten sideboard cards
// are silently counted as main deck — the deck labels, style classifier and size check would all shift.
function SWUBotDeckFromFixtureText(string $text): array {
    $sec = ''; $leader = ''; $base = ''; $cards = []; $sideboard = []; $author = '';
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        // "# Author: Ninin" — who built the list (owner 2026-10-01). Missing or empty → '' (no "By" part).
        if (preg_match('/^#\s*Author:(.*)$/i', $line, $am)) { $author = trim($am[1]); continue; }
        if ($line === '' || $line[0] === '#') continue;
        if ($line === 'Leader' || $line === 'Base' || $line === 'Deck' || $line === 'Sideboard') { $sec = $line; continue; }
        if (!preg_match('/^(\d+)\s+(\S+)/', $line, $m)) continue;
        $n = intval($m[1]); $id = $m[2];
        if ($sec === 'Leader') $leader = $id;
        elseif ($sec === 'Base') $base = $id;
        elseif ($sec === 'Deck') $cards[$id] = ($cards[$id] ?? 0) + $n;
        elseif ($sec === 'Sideboard') $sideboard[$id] = ($sideboard[$id] ?? 0) + $n;
    }
    return ['leader' => $leader, 'base' => $base, 'cards' => $cards, 'sideboard' => $sideboard, 'author' => $author];
}

// The name a fixture is SHOWN by: the card-derived name, plus " By <Author>" when its "# Author:" line names someone
// (owner 2026-10-01: "if it's either missing or empty, then don't display the By part"). The filename and the
// card/filename invariant stay on SWUBotDeckDisplayName — the author is a credit, not part of the deck's identity.
function SWUBotFixtureDisplayName(array $deck): string {
    $name = SWUBotDeckDisplayName(strval($deck['leader'] ?? ''), strval($deck['base'] ?? ''), (array)($deck['cards'] ?? []));
    $author = trim(strval($deck['author'] ?? ''));
    return ($name !== '' && $author !== '') ? $name . ' By ' . $author : $name;
}

// SUPERSET research mode (owner 2026-10-01): the fixture's sideboard folded INTO its main deck, so 50 → 60 and
// a Data Vault 60 → 70. Not a real deck — a probe of whether a matchup swings once sideboard answers exist.
// Done by renaming the 'Sideboard' header to 'Deck', which the game's own parser (ParseFreeTextDeck) reads as
// more main deck, so the game loads it through exactly the path a normal fixture takes. Returns null when the
// text has no Sideboard section, so a caller can refuse rather than silently run a game-1 deck as "superset".
function SWUBotFixtureSuperset(string $text): ?string {
    $out = preg_replace('/^Sideboard[ \t]*$/m', 'Deck', $text, -1, $n);
    return ($n > 0) ? $out : null;
}

// The five archetypes as a 0-4 scale (SWUSim/Custom/BotArchetypes.php holds the same order).
const SWU_DECKSTYLE_SCALE = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];

// The shape scan's weights, fitted against the owner's 23 labelled decks (2026-09-22): 16/23 exact (70%) and 23/23
// within one step, leave-one-out over the LABEL rule. ⚠ The weights themselves were fitted on all 23, so 70% is an
// optimistic estimate of how it will do on a deck nobody has labelled; the within-one-step figure is the robust one.
// bot_deckstyle_test.php re-measures both on every run.
// 'cost' fitted to 0: the curve is already carried by 'cheap' and 'big', and pricing it twice pushed every control
// list to the top of the scale. 'pivotCost' is the curve's pivot; 'cuts'/'outerBand'/'hardEvents' place the score.
// ⚠ RE-FIT 2026-10-01 (owner: "go for 1"): the feature weights are unchanged; what changed is how the raw score
// becomes a style. 'centre' 2.0 + 'scale' 1.0 put the bands at even raw cut points -1.5 / -0.5 / +0.5 / +1.5, and
// the labelled decks do not sit that way: NO hyper aggro list scored under -1.5 (all four read soft aggro, three of
// them "high" confidence), and hard control overlaps soft control completely on this score. Now:
//   'cuts' — the raw boundaries hyper|soft aggro, soft aggro|midrange, midrange|soft control, each the midpoint
//            between the neighbouring labels' scores (fitted on the 27 ash-meta-2026-09 decks);
//   hard control is not a score at all — it is the event share: >= 'hardEvents' of the deck on the control side.
//            The owner's own observation (swu-archetype-vocabulary): it is the ONE archetype composition separates;
//            here 45-51% for the three hard control lists against at most 27% for everything else.
// Measured, after the same day's second pass (space term dropped, Boba Fett (JTL) off the leader nudge, owner
// relabels: Boba Lake Country -> hyper aggro, Ezra Yellow -> soft aggro): leave-one-out 25/27 exact (was 18/27) and
// 27/27 within one step; STRICT leave-one-out, re-fitting the cuts without the held-out deck (min-error cut, widest
// gap on ties — which reproduces these cuts on all 27), 22/27; on the 5 owner-labelled HMW predictions, which the
// cuts never saw, 4/5 (was 2/5). ⚠ Tight: Vader Yellow sits at -0.75 against the -0.74 hyper cut.
// Still wrong on shape: Luke (JTL) Data Vault reads midrange (label soft aggro, one step — it read aggro only
// through the dropped space term); Luke (ASH) Data Vault and Tarfful (HMW) read soft control (label midrange — on
// every feature here they sit inside the soft control lists; Luke ~ Thrawn Yellow, Tarfful's only outlier is 0 cheap
// units). Archetype is a judgement about the PLAN, not the list (swu-archetype-vocabulary): a pasted copy of a
// labelled list gets its label from the 75% match, not from shape. Re-measured by bot_deckstyle_test.php on every
// run; the ends are pinned in bot_deckstyle_shape_test.php.
const SWU_DECKSTYLE_WEIGHTS = ['cost' => 0.0, 'cheap' => 0.05, 'big' => 0.04, 'answers' => 0.03,
                               'answersUnit' => 0.02, 'draw' => 0.04, 'burn' => 0.06, 'baseHp' => 0.2, 'aggroLeader' => 0.5,
                               'pivotCost' => 3.5, 'cuts' => [-0.74, -0.50, 0.40], 'outerBand' => 0.6, 'hardEvents' => 0.40];

// Leaders the classifier does NOT nudge toward aggro, though the bot's list has them (owner 2026-10-01). Boba Fett
// (JTL) leads both a hyper aggro burn list (Lake Country) and a midrange one with slight burn (Blue): the leader
// says nothing about which, and the nudge was what dragged Blue to hyper aggro.
const SWU_DECKSTYLE_NOT_AGGRO_LEADERS = ['JTL_009'];

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
            // The SHAPE feature keeps its name 'burn'; its tag input is damage-enemy-base since `burn` retired 2026-10-01.
            $shape[($t === 'indirect-damage' || $t === 'damage-enemy-base') ? 'burn' : $t] = true;
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

// Where the deck sits on the 0-4 scale (rounding it gives the style; SWUBotStyleFromScore). The RAW score starts at
// 0 = midrange-ish; aggro terms subtract, control terms add. It is then mapped onto the scale through the fitted cut
// points, piecewise-linearly, so each style's band runs from one cut to the next and a deck AT a cut sits on the x.5
// boundary — which is what the confidence levels measure.
function SWUBotDeckShapeScore(array $deck): float {
    $f = SWUBotDeckFeatures($deck);
    if ($f['n'] === 0) return 2.0;
    $w = SWUBotDeckStyleWeights();
    $s = SWUBotDeckRawShapeScore($deck, $f, $w);
    [$c1, $c2, $c3] = $w['cuts'];
    $out = floatval($w['outerBand']);   // raw width of one style step beyond the outer cuts
    if ($s < $c1)      $x = 0.5 - ($c1 - $s) / $out;
    elseif ($s < $c2)  $x = 0.5 + ($s - $c1) / ($c2 - $c1);
    elseif ($s < $c3)  $x = 1.5 + ($s - $c2) / ($c3 - $c2);
    else               $x = min(3.49, 2.5 + ($s - $c3) / $out);   // soft control — never rounds to hard on shape
    // Hard control is the EVENT SHARE, on the control side only.
    $share = $f['events'] / max(1, $f['n']);
    if ($s >= $c3 && $share >= $w['hardEvents']) $x = 3.5 + min(0.5, ($share - $w['hardEvents']) / 0.2);
    return max(0.0, min(4.0, $x));
}

// The raw shape score — the weighted features, before the cut points place it on the scale.
function SWUBotDeckRawShapeScore(array $deck, ?array $f = null, ?array $w = null): float {
    $f = $f ?? SWUBotDeckFeatures($deck);
    $w = $w ?? SWUBotDeckStyleWeights();
    $s = 0.0;
    $s += $w['cost'] * ($f['avgCost'] - $w['pivotCost']);
    $s -= $w['cheap'] * max(0, $f['cheap'] - 12);
    $s += $w['big'] * max(0, $f['big'] - 6);
    // Answers: a removal EVENT or a wipe is control's currency; a unit that removes on arrival is a body first.
    $s += $w['answers'] * max(0, ($f['removalEvent'] + 2 * $f['wipeEvent'] + $f['wipeUnit']) - 4);
    $s += $w['answersUnit'] * $f['removalUnit'];
    $s += $w['draw'] * $f['draw'];
    // Burn counts LINEARLY, every card (owner 2026-10-01: "Boba LC with 18 burn cards should be hyper aggro"). A cap
    // was tried the same day, on the earlier reading that burn is soft aggro's — it pulled Lake Country back to soft
    // aggro, and once the owner relabelled it hyper the fit preferred no cap (25/27 exact vs 24).
    $s -= $w['burn'] * $f['burn'];
    // NO space term (owner 2026-10-01: dropped). The arena is a FLAVOUR, not an archetype (swu-archetype-vocabulary),
    // and "mostly ships" read as aggro: it was the whole gap between the two Boba Fett (JTL) lists — Blue (66% ships,
    // midrange) paid it, Lake Country (55%) did not.
    if ($f['baseHp'] >= 30) $s += $w['baseHp'];
    // A leader whose lists are labelled aggro nudges, never decides (owner: an off-meta tempo Vader must not read
    // as Hyper Aggro). SWU_DECKSTYLE_NOT_AGGRO_LEADERS takes a leader off the nudge for THIS classifier only —
    // SWU_BOT_AGGRO_LEADERS is shared with the bot's resourcing and play features, which stay as they were.
    $lead = strval($deck['leader'] ?? '');
    if (in_array($lead, SWU_BOT_AGGRO_LEADERS, true) && !in_array($lead, SWU_DECKSTYLE_NOT_AGGRO_LEADERS, true)) $s -= $w['aggroLeader'];
    return $s;
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
        // The JSON also feeds the Arenabot pre-con picker, which offers every group; only a group marked 'classify'
        // (the reviewed tournament set) may label a player's list. A JSON written before groups existed has none.
        $classify = [];
        foreach ((array)($j['groups'] ?? []) as $g) if (!empty($g['classify'])) $classify[strval($g['id'] ?? '')] = true;
        if ($classify) $decks = array_values(array_filter($decks, fn($d) => isset($classify[strval($d['group'] ?? '')])));
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
