<?php
// Deck-style classifier (spec: docs/superpowers/specs/2026-09-22-swusim-deck-style-classifier-design.md).
// Pure: no game state, no network, no database. The Main Menu preselects "Bot play style" from it.
//
// The shape scan reads only the deck's printed cards (no leader term since 2026-10-11), so this file no longer loads
// BotResourcing.php for SWU_BOT_AGGRO_LEADERS. Callers load the card dictionaries and SWUSim/Rl/CardTags.php themselves.

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
// "Doctor Hemlock (HMW) Yellow (Naboo)" / doctor-hemlock_hmw_yellow_naboo — only when the leader, a main-deck card
// or (owner 2026-10-11) a SIDEBOARD card conditions on YOUR base having that exact trait: "check the tournament deck's
// base first, then verify it needs the trait in parenthesis from reading the main deck and sideboard cards' text".
// Maul (HMW) on Shield Generator Complex (Endor) with no Endor card stays "Maul (HMW) Blue", and no pre-HMW fixture
// changes name. Callers pass SWUBotNamingCards($deck) — main deck + sideboard.
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

// The cards that decide a deck's base trait: its main deck AND its sideboard (owner 2026-10-11), as [cardID => count].
// A fixture parse (SWUBotDeckFromFixtureText) carries both; a deck with no 'sideboard' field names exactly as before.
function SWUBotNamingCards(array $deck): array {
    $cards = (array)($deck['cards'] ?? []);
    foreach ((array)($deck['sideboard'] ?? []) as $id => $n) $cards[$id] = ($cards[$id] ?? 0) + intval($n);
    return $cards;
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
// about the base trait. $cards is SWUBotNamingCards($deck) — main deck + sideboard as [cardID => count] (owner
// 2026-10-11; it was the main deck alone until then); without it the trait is never added, which
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

// Accented letters folded to plain ASCII, so "Sabé" slugs to "sabe" rather than losing the letter ("sab"). Found by the
// round-trip guard on the HMW meta set (2026-10-11); Padmé Amidala would have hit it too. An explicit map, not iconv //TRANSLIT,
// whose output depends on the container's locale. A slug therefore cannot carry the accent back: compare names through it.
const SWU_BOT_ASCII_FOLD = ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
    'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ú' => 'u', 'ù' => 'u',
    'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'É' => 'E', 'È' => 'E',
    'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
    'Ç' => 'C', 'Ñ' => 'N'];
function SWUBotAsciiFold(string $s): string { return strtr($s, SWU_BOT_ASCII_FOLD); }

function SWUBotFixtureSlug(string $s): string {
    $s = SWUBotAsciiFold($s);                           // Sabé -> Sabe (keeps the letter; the accent cannot round-trip)
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
// The AUTHOR suffix (owner 2026-10-12): when two creators' lists in one folder derive the same name (Ninin's and Star Wars Dad's
// Maul (HMW) Blue), the second file carries "_by-<author>" as its LAST field. A credit, not part of the deck's name — read back off.
function SWUBotFixtureAuthorSuffix(string $author): string {
    $slug = SWUBotFixtureSlug(trim($author));
    return $slug === '' ? '' : '_by-' . $slug;
}

// The VARIANT field (owner 2026-10-14): two lists of one leader + base by ONE author derive the same name — Ninin's "Ahsoka Yellow w Neel"
// and her space build both read "Ahsoka Tano (ASH) Yellow". The second file names its variant, from its "# Variant:" line, as
// "_v-<variant>" right after the derived name (before any "_by-<author>"): ahsoka-tano_ash_yellow_v-space. Unlike the author it IS part
// of the deck's shown name — "Ahsoka Tano (ASH) Yellow Space" — so it round-trips through the filename.
function SWUBotFixtureVariantSuffix(string $variant): string {
    $slug = SWUBotFixtureSlug(trim($variant));
    return $slug === '' ? '' : '_v-' . $slug;
}

function SWUBotFixtureDisplayNameFromFile(string $stem): string {
    $parts = explode('_', $stem);
    if (count($parts) > 3 && str_starts_with(end($parts), 'by-')) {
        if (end($parts) === 'by-') return '';
        array_pop($parts);
    }
    $variant = null;
    if (count($parts) > 3 && str_starts_with(end($parts), 'v-')) {
        if (end($parts) === 'v-') return '';
        $variant = substr(array_pop($parts), 2);
    }
    if (count($parts) !== 3 && count($parts) !== 4) return '';
    [$leader, $set, $base] = $parts;
    $trait = $parts[3] ?? null;
    if ($leader === '' || $set === '' || $base === '' || $trait === '') return '';
    if ($trait !== null && (str_starts_with($trait, 'v-') || str_starts_with($trait, 'by-'))) return '';   // a misplaced suffix, not a trait
    $name = SWUBotFixtureUnslug($leader) . ' (' . strtoupper($set) . ') ' . SWUBotFixtureUnslug($base);
    if ($trait !== null) $name .= ' (' . SWUBotFixtureUnslug($trait) . ')';
    return $variant === null ? $name : $name . ' ' . SWUBotFixtureUnslug($variant);
}

// Parse a BotFixtures deck file: '# ' comments, then the sections Leader / Base / Deck / Sideboard, each line
// '<count> <CardID>'. `cards` is the MAIN deck only; the sideboard comes back separately.
// ⚠ 'Sideboard' MUST be a recognised header: without it the section stays 'Deck' and the ten sideboard cards
// are silently counted as main deck — the deck labels, style classifier and size check would all shift.
function SWUBotDeckFromFixtureText(string $text): array {
    $sec = ''; $leader = ''; $base = ''; $cards = []; $sideboard = []; $author = ''; $variant = '';
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        // "# Author: Ninin" — who built the list (owner 2026-10-01). Missing or empty → '' (no "By" part).
        if (preg_match('/^#\s*Author:(.*)$/i', $line, $am)) { $author = trim($am[1]); continue; }
        // "# Variant: Space" — names a second list of one leader + base by the same author (owner 2026-10-14).
        if (preg_match('/^#\s*Variant:(.*)$/i', $line, $vm)) { $variant = trim($vm[1]); continue; }
        if ($line === '' || $line[0] === '#') continue;
        if ($line === 'Leader' || $line === 'Base' || $line === 'Deck' || $line === 'Sideboard') { $sec = $line; continue; }
        if (!preg_match('/^(\d+)\s+(\S+)/', $line, $m)) continue;
        $n = intval($m[1]); $id = $m[2];
        if ($sec === 'Leader') $leader = $id;
        elseif ($sec === 'Base') $base = $id;
        elseif ($sec === 'Deck') $cards[$id] = ($cards[$id] ?? 0) + $n;
        elseif ($sec === 'Sideboard') $sideboard[$id] = ($sideboard[$id] ?? 0) + $n;
    }
    return ['leader' => $leader, 'base' => $base, 'cards' => $cards, 'sideboard' => $sideboard, 'author' => $author, 'variant' => $variant];
}

// The name a fixture is SHOWN by: the card-derived name, plus " By <Author>" when its "# Author:" line names someone
// (owner 2026-10-01: "if it's either missing or empty, then don't display the By part"). The filename and the
// card/filename invariant stay on SWUBotDeckDisplayName — the author is a credit, not part of the deck's identity.
function SWUBotFixtureDisplayName(array $deck): string {
    $name = SWUBotDeckDisplayName(strval($deck['leader'] ?? ''), strval($deck['base'] ?? ''), SWUBotNamingCards($deck));
    $variant = trim(strval($deck['variant'] ?? ''));
    if ($name !== '' && $variant !== '') $name .= ' ' . $variant;   // "# Variant:" (owner 2026-10-14) is part of the shown name
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

// ── THE SHAPE SCAN: a three-layer model of the deck's printed cards — NO leader term (owner 2026-10-11: "it should be enough to
// classify the deck's shape rather than interpret by leader"). Replaced the 2026-09-22 / 10-01 single weighted score, which leaned on
// SWU_BOT_AGGRO_LEADERS (−0.5 for a listed leader): with that term removed it fell from 79% to 61% exact, because none of its
// features measured aggression. Pattern study (memory swusim-opponent-style-map, 49 owner-labelled archetypes, 2,560 lists):
//   - unit / event TOTALS identify only hard control (event share 40–55% vs ≤ 22% for every other style); the other four all run
//     ~40–45 units and 5–8 events, and are told apart by COST and CARD TEXT;
//   - the curve tilt (units costing ≤ 3 vs ≥ 6) is the aggro ↔ control axis; burn and the attack/buff tags mark aggro;
//   - soft aggro is the most unit-HEAVY style; hyper aggro has the cheapest curve and the burn;
//   - midrange vs soft control share their totals — removal events, wipes and big units decide, attack/buff tags pull to midrange.
// So:  L1 event share >= 'hardEvents' → hard control.
//      L2 logistic SIDE — aggro (hyper/soft) vs middle (midrange/soft control).
//      L3 logistic within the side — hyper vs soft, midrange vs soft control.
// Fitted 2026-10-11 by SWUSim/DevTools/deckstyle/fit_deckstyle.py (ordinal layers, archetypes weighted equally, L2 0.01) on the 61
// owner-labelled curated lists + 2,499 tournament lists inheriting their archetype's label. LEAVE-ONE-ARCHETYPE-OUT: 54/61 owner lists
// (89%), 29/34 on the HMW / Force Fam lists the old scan never saw (old: 23/34), 76% of tournament lists, 99–100% within one step.
// ⚠ The per-layer features were chosen by a search over those same archetypes, so the true figure on an unseen deck is lower —
// the pre-search model scored 71% on the 34. Misses left: hyper read as soft (Ahsoka Yellow, Vader JTL), one step each.
// Each feature: [mean, sd, weight] — standardised, so a weight is "per standard deviation".
const SWU_DECKSTYLE_MODEL = [
    'hardEvents' => 0.35,
    'side'  => ['bias' => 1.9461, 'w' => [     // P(middle side): + = toward midrange / soft control
        'units3' => [23.7064, 6.4349, -1.3929], 'aggroTags' => [7.4143, 7.1228, -0.6127], 'wipe' => [1.9709, 2.2922, 0.4202],
        'draw' => [1.2912, 2.0861, 0.5443], 'burn' => [2.6402, 4.6555, -0.6563], 'heal' => [4.0582, 3.4420, 0.8242],
        'avgCost' => [3.7153, 0.7444, 0.8024]]],
    'aggro' => ['bias' => 0.1447, 'w' => [     // P(soft aggro | aggro side)
        'burn' => [4.4564, 5.7387, -1.5951], 'cheap' => [17.4301, 6.3506, -1.3586], 'units' => [42.8219, 6.4144, 1.0737],
        'units3' => [29.1250, 5.4270, -1.0430], 'draw' => [0.3834, 0.9383, 0.4438]]],
    'ctrl'  => ['bias' => -0.9544, 'w' => [    // P(soft control | middle side)
        'removalEvent' => [4.0137, 2.5719, 0.8252], 'wipe' => [2.8164, 2.3555, 0.7223], 'big' => [10.2938, 4.0367, 0.4209],
        'aggroTags' => [4.3718, 5.3529, -1.6664], 'eventsBig' => [6.1640, 3.4439, -0.3355]]],
];
// The attack/buff tags that mark an aggro list (tags v3): ~3–5 per aggro deck, ~0.1 per control deck.
const SWU_DECKSTYLE_AGGRO_TAGS = ['buff', 'grants-attack', 'pump', 'gives-raid', 'shoot-first'];

// $GLOBALS['SWUDeckStyleWeightOverride'] = ['<feature>' => <weight>] replaces that feature's weight in EVERY layer (tests prove a
// feature matters by zeroing it). Unset in normal use, so the fitted model is what runs.
function SWUBotDeckStyleModel(): array {
    $m = SWU_DECKSTYLE_MODEL;
    foreach ((array)($GLOBALS['SWUDeckStyleWeightOverride'] ?? []) as $feat => $w) {
        foreach (['side', 'aggro', 'ctrl'] as $l) if (isset($m[$l]['w'][$feat])) $m[$l]['w'][$feat][2] = floatval($w);
    }
    return $m;
}

// Deck shape, read from the printed cards. Mirrors SWUSim/DevTools/deck_features.php, which stays as the CLI tool.
function SWUBotDeckFeatures(array $deck): array {
    $base = strval($deck['base'] ?? '');
    // 'removal'/'wipe' are the totals; the *Event / *Unit halves are what the score prices. A 2-cost body that kills
    // something on arrival (Karis) is not the same card as a removal SPELL held for a threat: counting them together
    // read Maul Blue Force — 12 removal UNITS, 3 removal events — as a control deck (owner, 2026-09-22).
    $f = ['n' => 0, 'units' => 0, 'events' => 0, 'upgrades' => 0, 'cheap' => 0, 'units3' => 0, 'big' => 0, 'events3' => 0, 'costSum' => 0,
          'removal' => 0, 'removalEvent' => 0, 'removalUnit' => 0, 'wipe' => 0, 'wipeEvent' => 0, 'wipeUnit' => 0,
          'burn' => 0, 'draw' => 0, 'heal' => 0, 'aggroTags' => 0, 'space' => 0, 'avgCost' => 0.0,
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
            if ($cost <= 3) $f['units3'] += $n;
            if ($cost >= 6) $f['big'] += $n;
            if (str_contains(strval(CardArena($id) ?? ''), 'Space')) $f['space'] += $n;
        } elseif (str_contains($type, 'Event')) { $f['events'] += $n; if ($cost <= 3) $f['events3'] += $n; }
        elseif (str_contains($type, 'Upgrade')) $f['upgrades'] += $n;
        $isUnit = str_contains($type, 'Unit');
        $tags = array_unique(SWUBotCardTags(strval($id)));
        // Every attack/buff tag a card carries counts (a card that both buffs and pumps counts twice) — as fitted.
        $f['aggroTags'] += $n * count(array_intersect($tags, SWU_DECKSTYLE_AGGRO_TAGS));
        // Tags v3 split 'indirect-damage' out of 'burn' (owner ruling 2026-09-28). For the deck's SHAPE the two
        // are the same axis — reach that does not need a board — so indirect folds back into the burn bucket.
        // De-duplicated first: a card tagged BOTH would otherwise count twice.
        $shape = [];
        foreach ($tags as $t) {
            // The SHAPE feature keeps its name 'burn'; its tag input is damage-enemy-base since `burn` retired 2026-10-01.
            $shape[($t === 'indirect-damage' || $t === 'damage-enemy-base') ? 'burn' : $t] = true;
        }
        foreach (array_keys($shape) as $t) {
            if (!in_array($t, ['removal', 'wipe', 'burn', 'draw', 'heal'], true)) continue;
            $f[$t] += $n;
            if ($t === 'removal' || $t === 'wipe') $f[$t . ($isUnit ? 'Unit' : 'Event')] += $n;
        }
    }
    $f['avgCost'] = $f['n'] ? round($f['costSum'] / $f['n'], 2) : 0.0;
    $f['eventsBig'] = $f['events'] - $f['events3'];
    return $f;
}

// The three layers' probabilities and the class probabilities over hyper / soft / midrange / soft control, plus the hard-control
// test. Pure: features in, numbers out.
function SWUBotDeckLayerProbs(array $f): array {
    $m = SWUBotDeckStyleModel();
    $layer = function (array $l) use ($f): float {
        $z = floatval($l['bias']);
        foreach ($l['w'] as $feat => [$mu, $sd, $w]) $z += $w * (floatval($f[$feat] ?? 0) - $mu) / $sd;
        return 1.0 / (1.0 + exp(-max(-30.0, min(30.0, $z))));
    };
    $q = $layer($m['side']); $a = $layer($m['aggro']); $b = $layer($m['ctrl']);
    $share = $f['events'] / max(1, $f['n']);
    return ['side' => $q, 'aggro' => $a, 'ctrl' => $b, 'eventShare' => $share, 'hard' => $share >= $m['hardEvents'],
            'p' => [(1 - $q) * (1 - $a), (1 - $q) * $a, $q * (1 - $b), $q * $b]];
}

// Where the deck sits on the 0–4 scale. The STYLE is the most probable class; the score is the expected rank clamped inside that
// class's band, so SWUBotStyleFromScore rounds back to it and its distance from the band's edge still reads as confidence.
// Hard control: 3.5 + how far the event share clears the bar (as before).
function SWUBotDeckShapeScore(array $deck): float {
    $f = SWUBotDeckFeatures($deck);
    if ($f['n'] === 0) return 2.0;
    $L = SWUBotDeckLayerProbs($f);
    if ($L['hard']) return min(4.0, 3.5 + min(0.5, ($L['eventShare'] - SWUBotDeckStyleModel()['hardEvents']) / 0.2));
    $k = array_keys($L['p'], max($L['p']))[0];
    $expect = 0.0; foreach ($L['p'] as $i => $p) $expect += $i * $p;
    return max(0.0, min(3.49, max($k - 0.49, min($k + 0.49, $expect))));
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
    $L = SWUBotDeckLayerProbs($f);
    $reasons = [$L['hard']
        ? sprintf('events are %d%% of the deck — hard control', (int)round(100 * $L['eventShare']))
        : sprintf('shape score %.2f — %d%% aggro side (%d units ≤3 cost, %d burn, %d attack/buff cards); %d units ≥6 cost, %d removal events, %d wipes',
            $score, (int)round(100 * (1 - $L['side'])), $f['units3'], $f['burn'], $f['aggroTags'], $f['big'], $f['removalEvent'], $f['wipe'])];
    if ($best !== null) $reasons[] = sprintf('nearest labelled list %s is only %d%%', $best['file'], (int)round(100 * $bestOverlap));
    return ['style' => $r['style'], 'confidence' => $r['confidence'], 'source' => 'shape', 'reasons' => $reasons, 'score' => $score];
}
