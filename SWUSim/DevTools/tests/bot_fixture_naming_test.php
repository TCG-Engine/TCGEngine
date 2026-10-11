<?php
// The fixture naming convention (owner, 2026-09-29): a fixture file is
//     <leader-title>_<leader-set>_<base-name-or-archetype>
// and its Bot Arena display name is "Leader Title (SET) Base" — the underscores becoming " (" and ") ".
//
// Pinned against the owner's four worked examples, plus the two traps that make this worth a test at all:
//   • a word-break hyphen and a hyphen INSIDE a name must stay distinguishable. A single '-' is the break, a
//     DOUBLE '--' is a literal hyphen: "the-mandalorian" -> "The Mandalorian" but "obi--wan-kenobi" ->
//     "Obi-Wan Kenobi". That escape makes the slug reversible, so the two derivations — from the CARD IDs and
//     from the FILENAME — can be checked against each other, which is what caught "Jabba the Hutt" coming
//     back as "Jabba The Hutt". The picker still reads the CARD-derived name; the filename is the cross-check.
//   • the base half has four rules, and rules 2/3 (LOF 28-HP Force, LAW 27-HP Splash) must be tested BEFORE
//     rule 4 (30-HP bare colour) — colour alone cannot tell a 30-HP Vigilance common from a 27-HP LAW one.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_fixture_naming_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Custom/BotDeckStyle.php';

// ── the owner's four worked examples, verbatim ───────────────────────────────────────────────────
// file -> [leaderID, baseID, expected filename, expected display name]
$examples = [
    'krennic_splash'    => ['LAW_008', 'LAW_020', 'director-krennic_law_blue-splash',  'Director Krennic (LAW) Blue Splash'],
    'mando_colossus'    => ['ASH_014', 'JTL_021', 'the-mandalorian_ash_colossus',      'The Mandalorian (ASH) Colossus'],
    'luke_datavault'    => ['JTL_012', 'JTL_024', 'luke-skywalker_jtl_data-vault',     'Luke Skywalker (JTL) Data Vault'],
    'lukeash_datavault' => ['ASH_005', 'JTL_024', 'luke-skywalker_ash_data-vault',     'Luke Skywalker (ASH) Data Vault'],
];
foreach ($examples as $old => [$lead, $base, $wantFile, $wantName]) {
    $gotFile = SWUBotFixtureFileName($lead, $base);
    $gotName = SWUBotDeckDisplayName($lead, $base);
    $check($gotFile === $wantFile, "{$old}: filename -> {$wantFile}" . ($gotFile === $wantFile ? '' : " (got {$gotFile})"));
    $check($gotName === $wantName, "{$old}: display  -> {$wantName}" . ($gotName === $wantName ? '' : " (got {$gotName})"));
}

// ── the hyphen escape: '--' is a LITERAL dash, and the letter after it capitalises ────────────────
// Owner, 2026-09-29. LOF_008 is Obi-Wan Kenobi, so a single '-' could not tell the word break from the
// name's own hyphen. The double form makes the slug REVERSIBLE, which is what lets the two derivations
// (from card ids, and from the filename) be checked against each other below.
$obi = SWUBotDeckDisplayName('LOF_008', 'LOF_019');
$check($obi === 'Obi-Wan Kenobi (LOF) Vergence Temple', "internal hyphen and set survive: got '{$obi}'");
$check(SWUBotFixtureFileName('LOF_008', 'LOF_019') === 'obi--wan-kenobi_lof_vergence-temple',
    "the literal hyphen is escaped as '--': got '" . SWUBotFixtureFileName('LOF_008', 'LOF_019') . "'");
$check(SWUBotFixtureUnslug('obi--wan-kenobi') === 'Obi-Wan Kenobi',
    "unslug: obi--wan-kenobi -> Obi-Wan Kenobi (got '" . SWUBotFixtureUnslug('obi--wan-kenobi') . "')");
$check(SWUBotFixtureUnslug('the-mandalorian') === 'The Mandalorian',
    "unslug: a single dash is a word break (got '" . SWUBotFixtureUnslug('the-mandalorian') . "')");
$check(SWUBotFixtureDisplayNameFromFile('obi--wan-kenobi_lof_vergence-temple') === 'Obi-Wan Kenobi (LOF) Vergence Temple',
    'the whole filename round-trips to the display name');
// A three-part name must survive too — Ki-Adi-Mundi is the shape that breaks a naive single-escape.
$check(SWUBotFixtureSlug('Ki-Adi-Mundi') === 'ki--adi--mundi'
    && SWUBotFixtureUnslug('ki--adi--mundi') === 'Ki-Adi-Mundi', 'two literal hyphens in one name round-trip');
// An apostrophe must not become a stray hyphen: LAW_020 is Daimyo's Palace.
$check(SWUBotFixtureSlug("Daimyo's Palace") === 'daimyos-palace',
    "apostrophes are dropped, not hyphenated: got '" . SWUBotFixtureSlug("Daimyo's Palace") . "'");

// ── the four base rules, each on a real base from the fixture set ────────────────────────────────
$rules = [
    // rule 1 — non-common keeps its printed title
    ['JTL_024', 'Data Vault',     'rule 1: a RARE base keeps its full name'],
    ['JTL_021', 'Colossus',       'rule 1: Colossus (rare, 35 HP)'],
    ['JTL_031', 'Lake Country',   'rule 1: Lake Country (rare, and has NO aspect — must not read as "?")'],
    ['LAW_019', 'Alliance Outpost','rule 1: a rare below 30 HP is still its own name, not a colour'],
    // rule 2 — LOF 28-HP common
    ['LOF_020', 'Blue Force',     'rule 2: LOF 28-HP Vigilance common -> Blue Force'],
    ['LOF_029', 'Yellow Force',   'rule 2: LOF 28-HP Cunning common -> Yellow Force'],
    // rule 3 — LAW 27-HP common
    ['LAW_020', 'Blue Splash',    'rule 3: LAW 27-HP Vigilance common -> Blue Splash'],
    ['LAW_027', 'Red Splash',     'rule 3: LAW 27-HP Aggression common -> Red Splash'],
    // rule 4 — 30-HP common is the bare colour
    ['JTL_019', 'Blue',           'rule 4: 30-HP Vigilance common -> Blue'],
    ['ASH_026', 'Yellow',         'rule 4: 30-HP Cunning common -> Yellow'],
    ['ASH_024', 'Red',            'rule 4: 30-HP Aggression common -> Red'],
];
foreach ($rules as [$base, $want, $label]) {
    $got = SWUBotBaseArchetypeName($base);
    $check($got === $want, "{$label}" . ($got === $want ? '' : " — got '{$got}'"));
}

// ⚠ Rule ORDER is load-bearing, and this is the section that proves it: LAW_020 (27 HP) and JTL_019 (30 HP)
// are BOTH Vigilance commons. If the 30-HP rule ran first, or if the set/HP test were dropped, they would
// both read "Blue" and two fixtures would collide on one filename.
$check(SWUBotBaseArchetypeName('LAW_020') !== SWUBotBaseArchetypeName('JTL_019'),
    'a 27-HP LAW Vigilance common and a 30-HP Vigilance common do NOT collapse to the same name');

// ── the base TRAIT, a 4th field (owner, 2026-10-01) ──────────────────────────────────────────────
// HMW made the base's location trait matter, but it is only part of the archetype when the deck's cards CARE.
// Torrent HMW_100 ("If you control a Naboo base, …") and Overgrowth HMW_151 ("If you control a Kashyyyk base")
// are carers; Shield Generator Complex JTL_020 is an Endor base.
$hemlock = ['HMW_100' => 2, 'SEC_026' => 3];
$check(SWUBotDeckDisplayName('HMW_003', 'HMW_033', $hemlock) === 'Doctor Hemlock (HMW) Yellow (Naboo)',
    "owner's example: Hemlock on Otoh Gunga (Naboo) with Torrent -> 'Doctor Hemlock (HMW) Yellow (Naboo)' (got '"
    . SWUBotDeckDisplayName('HMW_003', 'HMW_033', $hemlock) . "')");
$check(SWUBotFixtureFileName('HMW_003', 'HMW_033', $hemlock) === 'doctor-hemlock_hmw_yellow_naboo',
    "...and its file is doctor-hemlock_hmw_yellow_naboo (got '" . SWUBotFixtureFileName('HMW_003', 'HMW_033', $hemlock) . "')");
$check(SWUBotDeckDisplayName('HMW_003', 'HMW_033', ['SEC_026' => 3]) === 'Doctor Hemlock (HMW) Yellow',
    'the SAME base with no card that cares carries NO trait — the trait is the deck\'s, not the base\'s');
$check(SWUBotDeckDisplayName('HMW_016', 'JTL_020', ['HMW_100' => 2]) === 'Maul (HMW) Blue',
    'an Endor base with only a NABOO carer: the trait must MATCH the base, so no "(Endor)" and no "(Naboo)"');
$check(SWUBotDeckDisplayName('HMW_003', 'HMW_033') === 'Doctor Hemlock (HMW) Yellow',
    'no cards passed -> the pre-2026-10-01 three-part name, so old callers are unchanged');
// The wording, on synthetic text: only YOUR base counts. (No printed card has the single-trait opponent form, so
// a card-based check could not tell "you control" from "controls" — Surveillance Cruiser's four-trait LIST does
// not match either way. Caught by mutating the pattern 2026-10-01.)
$check(!SWUBotTextCaresAboutBaseTrait('When Played: If an opponent controls a Naboo base, draw a card.', 'Naboo'),
    'an OPPONENT\'s Naboo base is not a reason to call MY deck a Naboo deck');
$check(SWUBotTextCaresAboutBaseTrait('While you control a Naboo base, this unit gains Grit.', 'Naboo')
    && !SWUBotTextCaresAboutBaseTrait('While you control a Naboo base, this unit gains Grit.', 'Endor'),
    '"you control a Naboo base" cares about Naboo, and ONLY Naboo');
$check(!SWUBotCardCaresAboutBaseTrait('HMW_247', 'Endor'),
    'Surveillance Cruiser (an opponent\'s Endor, Kashyyyk, Naboo, or Tatooine base) is not a carer');
$check(SWUBotCardCaresAboutBaseTrait('HMW_084', 'Naboo'),
    'Gunga City Guard "another Gungan unit OR a Naboo base" is a carer');
$check(SWUBotFixtureDisplayNameFromFile('tarfful_hmw_blue_kashyyyk') === 'Tarfful (HMW) Blue (Kashyyyk)',
    'a 4-field filename round-trips to "… (Trait)"');
$check(SWUBotFixtureDisplayNameFromFile('a_b_c_d_e') === '' && SWUBotFixtureDisplayNameFromFile('a_b_c_') === '',
    'five fields, or an EMPTY 4th field (a trailing underscore), is not the convention');

// ── every fixture in the renamed dirs must produce a UNIQUE name ─────────────────────────────────
// A collision silently makes one deck unreachable, so this is the invariant that matters most. It is
// computed from the files on disk, so adding a colliding fixture fails here rather than at rename time.
// ⚠ There is NO exemption list, deliberately. A deck the convention cannot name uniquely does not get an
// exemption here — it belongs in a directory that is not renamed. The owner's krennic_ninin is the worked
// example: a second Director Krennic list on another LAW 27-HP Vigilance base (Coaxium Mine vs Daimyo's
// Palace), which the convention collapses to the same "Blue Splash", so it lives in force-fam-old/ instead.
// meta-2026-09-field/ was deleted 2026-09-29; force-fam-old/ (force-fam/ until 2026-10-01) and weak-2026-09/ are
// outside the convention by design (attribution, and defect-encoding names, respectively).
// force-fam-HMW-predictions/ follows the convention (owner, 2026-10-01) — it is where the base-trait 4th field was
// introduced.
foreach (['ash-meta-2026-09', 'force-fam-HMW-predictions'] as $dir) {
    $byName = []; $misnamed = [];
    // ⚠ An EMPTY or renamed directory would make every check below pass vacuously — the owner renamed
    // star-wars-dad-HMW-predictions/ to force-fam-HMW-predictions/ mid-session, and this test kept passing on nothing.
    $files = glob("./SWUSim/Tests/BotFixtures/{$dir}/*.txt") ?: [];
    $check(count($files) > 0, "{$dir}: the directory exists and holds fixtures (" . count($files) . ")");
    foreach ($files as $path) {
        $stem = basename($path, '.txt');
        $d = SWUBotDeckFromFixtureText((string)file_get_contents($path));
        $n = SWUBotFixtureFileName(strval($d['leader'] ?? ''), strval($d['base'] ?? ''), $d['cards'] ?? []);
        $check($n !== '', "{$dir}/{$stem}: resolves to a name");
        $byName[$n][] = $stem;
        if ($n !== $stem) $misnamed[] = "{$stem} (should be {$n})";
    }
    // The file on disk must carry the name its cards derive — otherwise a deck that gains or loses its only trait
    // carer (a list edit) keeps a stale "(Naboo)" in its filename and the picker disagrees with the file.
    $check(empty($misnamed), "{$dir}: every file is named by the convention" . (empty($misnamed) ? '' : ' — ' . implode('; ', $misnamed)));
    $dups = array_filter($byName, fn($v) => count($v) > 1);
    $msg = [];
    foreach ($dups as $n => $olds) $msg[] = "{$n} <- " . implode('+', $olds);
    $check(empty($dups), "{$dir}: all " . count($byName) . " fixtures get unique names"
        . (empty($dups) ? '' : ' — COLLIDING: ' . implode('; ', $msg)));

    // THE INVARIANT the reversible slug buys: the name derived from the CARD IDs and the name read back
    // out of the FILENAME must be identical. If they ever diverge, one of the two derivations is wrong and
    // the picker would disagree with the fixture on disk.
    $bad = [];
    foreach ($byName as $newStem => $olds) {
        $path = "./SWUSim/Tests/BotFixtures/{$dir}/{$olds[0]}.txt";
        $d = SWUBotDeckFromFixtureText((string)file_get_contents($path));
        $fromCards = SWUBotDeckDisplayName(strval($d['leader'] ?? ''), strval($d['base'] ?? ''), $d['cards'] ?? []);
        $fromFile  = SWUBotFixtureDisplayNameFromFile($newStem);
        if ($fromCards !== $fromFile) $bad[] = "{$newStem}: cards='{$fromCards}' file='{$fromFile}'";
    }
    $check(empty($bad), "{$dir}: card-derived and filename-derived display names agree for all "
        . count($byName) . (empty($bad) ? '' : ' — MISMATCH: ' . implode('; ', array_slice($bad, 0, 4))));
}

// "# Author:" (owner 2026-10-01): the shown name gets " By <Author>"; a missing or EMPTY author shows no "By" part.
// The filename and the card/filename invariant above stay on the plain card-derived name.
$deckOf = fn(string $authorLine) => SWUBotDeckFromFixtureText($authorLine . "\nLeader\n1 ASH_009\nBase\n1 HMW_033\nDeck\n3 SEC_213\n");
$plain = SWUBotDeckDisplayName('ASH_009', 'HMW_033', ['SEC_213' => 3]);
$check(SWUBotFixtureDisplayName($deckOf('# Author: Ninin')) === $plain . ' By Ninin', 'an author shows as "' . $plain . ' By Ninin"; got "' . SWUBotFixtureDisplayName($deckOf('# Author: Ninin')) . '"');
$check(SWUBotFixtureDisplayName($deckOf('# Author:   ')) === $plain, 'an EMPTY "# Author:" shows no By part');
$check(SWUBotFixtureDisplayName($deckOf('# Archetype: x')) === $plain, 'a MISSING "# Author:" shows no By part');
$check($deckOf('# Author: Star Wars Dad')['author'] === 'Star Wars Dad', 'the author keeps its spaces ("Star Wars Dad")');
// Every creator deck credits its author — a deck added WITHOUT one is the failure this catches (the folder grows:
// a whole-folder "everyone else is Star Wars Dad" rule broke the day Ninin's second list landed). Known decks are
// pinned by name.
$authors = [];
foreach (glob('./SWUSim/Tests/BotFixtures/force-fam-HMW-predictions/*.txt') ?: [] as $p) $authors[basename($p, '.txt')] = SWUBotDeckFromFixtureText((string)file_get_contents($p))['author'];
$check(count($authors) >= 5 && !in_array('', $authors, true), 'every force-fam-HMW-predictions deck names its author: ' . json_encode($authors));
$known = ['ahsoka-tano_ash_yellow' => 'Ninin', 'doctor-hemlock_hmw_red' => 'Ninin', 'director-krennic_law_blue-splash' => 'Ninin', 'doctor-hemlock_hmw_yellow_naboo' => 'Star Wars Dad',
          'maul_hmw_blue' => 'Star Wars Dad', 'tarfful_hmw_blue_kashyyyk' => 'Star Wars Dad', 'wicket_hmw_green-splash' => 'Star Wars Dad',
          'general-grievous_hmw_blue_kashyyyk' => 'Ninin', 'obi--wan-kenobi_lof_yellow-force' => 'MasterJay'];
$wrong = array_filter($known, fn($a, $f) => ($authors[$f] ?? null) !== $a, ARRAY_FILTER_USE_BOTH);
$check(empty($wrong), 'each known creator deck credits the right author', json_encode(array_map(fn($f) => $authors[$f] ?? '(missing)', array_keys($wrong))));

// ⚠ weak-2026-09 is deliberately NOT in that list. Its names encode the DEFECT under test (badcurve,
// neutralunits, noremoval) and three of them share one leader+base, so the convention would both destroy
// the only information in the name and collide 3 ways. Asserted so nobody "completes" the rename later.
$weak = array_map(fn($p) => basename($p, '.txt'), glob('./SWUSim/Tests/BotFixtures/weak-2026-09/*.txt') ?: []);
$check(in_array('badcurve', $weak, true) && in_array('noremoval', $weak, true),
    'weak-2026-09 keeps its defect-named fixtures (badcurve, noremoval) — NOT renamed');

bot_test_finish();
