<?php
// Generates SWUSim/Rl/CardTags.generated.php — the per-card effect/keyword tags read by SWUBotCardTags().
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/rl/tag_cards.php
//
// TAGS V3 (owner rulings 2026-09-28, two forms). Structure:
//
//   1. BOTH FACES, FACE-SCOPED — ['front' => [...], 'deployed' => [...]]. v2 read $textData only, so 87
//      leaders' deployed side was invisible. The faces can want OPPOSITE decisions (LOF Kit Fisto is a
//      pointless flip with no Jedi on board, so he is worth MORE unflipped).
//   2. KEYWORDS ARE TAGS. Parenthesised reminder text is still stripped for the EFFECT patterns, but the
//      keyword itself is tagged. HAS vs GRANTS is decided by LINE POSITION: a keyword the card HAS starts a
//      line ("Grit", "Restore 1 (…)", "Smuggle [7 resources …]"); one it GRANTS sits mid-sentence ("…gains
//      Ambush"). SHD_036 First Light has BOTH and only the line-initial one counts.
//   3. WHO IT HURTS IS PART OF THE TAG. Four text regions are pulled out and scanned under their own prefix
//      so a drawback can never read as an upside:
//        • bracketed COSTS  → cost-…    ("Action [Exhaust, defeat a friendly unit]", Smuggle costs)
//        • Bounty clauses   → bounty-…  (the OPPONENT collects it — a liability, not my draw)
//        • self-harm        → self-burn / self-damage (Operation Cinder deals 5 to MY base)
//      Owner: "one convention for both."
//
// ⚠ `wipe` IS NOW DERIVED, NOT PRIMARY. The owner retired it in favour of damage-wipe-{friendly,enemy} and
// defeat-wipe-{friendly,enemy}. It is still EMITTED, from the ENEMY halves only, because ~20 sites consume it
// — including a per-archetype WEIGHT (BotArchetypes.php), the wipe-plan rule (BotRules), six resourcing reads
// and the wipekeep/wipethreat/wipegate features. Deriving it keeps all of them correct while the four specific
// tags become available for weighting. Deriving from the ENEMY halves is also strictly MORE correct than v2,
// which tagged an unqualified self-harming wipe as a board answer.
// ⚠ RL MOVE KEYS ARE PINNED to the nine v2 tags — see SWUBotCardTagsForRlKey().
// ⚠ A NEW TAG IS INERT UNTIL WIRED: _SWUBotPlayValue sums $W[$tag] ?? 0.0.

global $textData, $deployTextData;
if (!isset($textData)) { chdir(__DIR__ . '/../../..'); include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; }

const SWU_BOT_TAG_KEYWORDS = ['Sentinel', 'Ambush', 'Saboteur', 'Shielded', 'Overwhelm', 'Grit', 'Raid',
                              'Restore', 'Hidden', 'Bounty', 'Plot', 'Smuggle', 'Exploit', 'Coordinate',
                              'Piloting', 'Fortify'];

// Keywords a card GRANTS to something else. Conditional and often aimed elsewhere, so a grant never implies
// the plain keyword tag (owner ruling 10).
const SWU_BOT_TAG_GRANTABLE = ['Sentinel', 'Raid', 'Overwhelm', 'Ambush', 'Restore', 'Grit', 'Saboteur',
                               'Hidden', 'Shielded'];

// Token UNITS a card creates — a new BODY, which is a go-wide plan. Token UPGRADES (Shield, Experience,
// Advantage, Weakness) are created-then-given and are tagged gives-… instead, because who benefits is the
// strategically useful fact about them.
const SWU_BOT_TAG_TOKENS = ['Credit' => 'create-credit-token', 'Battle Droid' => 'create-battle-droid-token',
                            'Spy' => 'create-spy-token', 'Beast' => 'create-beast-token',
                            'Clone Trooper' => 'create-clone-trooper-token', 'Mandalorian' => 'create-mandalorian-token',
                            'TIE Fighter' => 'create-tie-fighter-token', 'X-Wing' => 'create-x-wing-token'];

const SWU_BOT_TAGS_V2 = ['removal', 'damage', 'draw', 'buff', 'bounce', 'exhaust', 'heal', 'wipe', 'burn'];

// MASS effects, matched and REMOVED before anything else so "deal 5 damage to each unit" is not also counted as
// targeted damage. An UNQUALIFIED "each unit" hits both sides and earns both halves.
// ⚠ "EACH OF" IS THE DISCRIMINATOR, not the qualifier's position. "Deal 1 damage to each of up to 3 units"
// (JTL_170, LAW_183, SEC_155 and 4 more) is TARGETED damage with a variable count — the player picks — and is
// deliberately NOT a wipe (owner ruling 5, form 3). But "each OTHER unit", "each DAMAGED unit", "each JEDI unit",
// "each NON-VEHICLE enemy unit" are all genuine mass effects, and an earlier version of these patterns demanded
// the qualifier immediately after the quantifier, so it silently missed 10 of them (ASH_083 Summa-verminoth,
// IBH_072 Avenger, LOF_170 Bendu, SHD_158 Wild Rancor, SEC_183 Topple the Summit, TWI_013 Mace Windu,
// TWI_239 Execute Order 66, LOF_141 Death Field, LOF_042 Always Two) while v2 missed them too.
// "each CHOSEN unit" is excluded for the same reason as "each of": the player named the targets.
// ⚠ ALSO REQUIRES "units" on the defeat side: LOF_147's "Defeat any number of friendly CREDIT TOKENS" was a
// `wipe` in v2 — a board answer that answers no board.
const SWU_BOT_WIPE_NOT = '(?!of\b|chosen\b)';
const SWU_BOT_WIPE_PATTERNS = [
    'damage-wipe-enemy'    => '/deal \d+ damage to each ' . SWU_BOT_WIPE_NOT . '[^.]{0,26}?enemy[^.]{0,18}?units?/i',
    'damage-wipe-friendly' => '/deal \d+ damage to each ' . SWU_BOT_WIPE_NOT . '[^.]{0,26}?friendly[^.]{0,18}?units?/i',
    'damage-wipe-both'     => '/deal \d+ damage to each ' . SWU_BOT_WIPE_NOT . '[^.]{0,26}?units?/i',
    'defeat-wipe-enemy'    => '/defeat (each|all|any number of) ' . SWU_BOT_WIPE_NOT . '[^.]{0,26}?enemy[^.]{0,18}?units/i',
    'defeat-wipe-friendly' => '/defeat (each|all|any number of) ' . SWU_BOT_WIPE_NOT . '[^.]{0,26}?friendly[^.]{0,18}?units/i',
    'defeat-wipe-both'     => '/defeat (each|all|any number of) ' . SWU_BOT_WIPE_NOT . '[^.]{0,26}?units/i',
];

// SELF-HARM, matched and REMOVED before the ordinary damage patterns (owner rulings 2-4).
const SWU_BOT_SELF_PATTERNS = [
    'self-burn'   => '/deal \d+ damage to (your|their) base/i',
    'self-damage' => '/(deal \d+ damage to (a |another )?friendly[^.]*?unit|deal \d+ damage to (this unit|itself|him|her|them)\b)/i',
];

const SWU_BOT_TAG_PATTERNS = [
    'removal' => '/(\bdefeat (a|an|another|up to \d+|that) (?![^.]*?\bfriendly\b)[^.]*?\bunit|take control of (a|an) [^.]*?unit, then defeat it|give (a|an|another) (non-leader |enemy )?unit -\d+\/-[1-9]\d*|loses all abilities for this phase\. if it costs \d+ or less, defeat it|enemy (non-leader )?unit\. if you do, defeat those units)/i',
    'damage'  => '/(deal \d+ damage to (a|an|another|each enemy|each|up to|that)[^.]*?\bunits?\b|deal damage (equal to|divided)[^.]*?\bunits?\b|give each enemy unit[^.]*?-\d+\/-[1-9]\d*)/i',
    'burn'    => '/deal \d+ damage to (a|an|each|that|each enemy|an opponent\'s|each opponent\'s) base/i',
    'indirect-damage' => '/\bindirect damage\b/i',
    'draw'    => '/\bdraw (a|\d+|two|three) cards?\b/i',
    'heal'    => '/\bheal \d+ damage\b/i',
    'buff'    => '/(give [^.]*?\+\d+\/\+\d+|gets? \+\d+\/\+\d+ for this (phase|attack|round))/i',
    'debuff'  => '/(give [^.]*?-\d+\/-0\b|gets? -\d+\/-0\b)/i',
    // PUMP (owner 2026-10-01): "Attack with a unit. It gets +X/+0 for this attack." — an extra attack AND a
    // power boost scoped to it, on any card type: events, leader Actions, and units' When Played ("Lieutenant
    // abilities" colloquially — keyed on the TEXT, never the name, since not every Lieutenant has one).
    // CONDITIONAL boosts count ("If it's a Rebel unit, it gets +2/+0") — the owner: decks running them meet the
    // condition. Spans up to three sentences so SHD_145 Headhunting's "…gets +2/+0 for its attack" is reached.
    // Kept ALONGSIDE `buff`, never instead of it: 41 of these already carry buff and their weights must not move.
    // Phase-long boosts that merely permit an attack (ASH_109 T-6 Shuttle 1974, +2/+2 for this phase) are buff.
    'pump'    => '/(\battack with\b[^.]*\.?[^.]*?(?:\.[^.]*?)?\+(?:\d+|X)\/\+0\b[^.]*?for (?:this|its) attack|\battack with\b[^.]*\.\s*for this attack, (?:it|he|she|they) gets \+(?:\d+|X)\/\+0)/i',
    // GRANTS-ATTACK (owner 2026-10-01): the card's effect gives an attack NOW — events, leader/unit Actions,
    // When Played, chained "when this unit completes an attack: you may attack with another unit", and upgrades
    // that attack with the unit they attach to (SHD_223 Snapshot Reflexes, LOF_140, TWI_248, TS26_25). `pump` is
    // the SUBSET whose attack also gets +X/+0. ⚠ Requires "attack WITH": "this unit may attack units in either
    // arena" (ASH_037 Red Leader) and "can attack ground units" (SHD_230) are targeting permissions, not attacks.
    'grants-attack' => '/\battack with\b/i',
    // SHOOT-FIRST (owner 2026-10-01): deals its combat damage before the defender — player slang, after the
    // SOR_217 event, for any first-strike ability, whether granted for one attack or printed on the unit.
    // ⚠ LAW_086 The Stranger is the REVERSE ("have the defending unit deal combat damage before this unit").
    'shoot-first' => '/deals? (?:its )?combat damage before the (?:defender|defending unit)/i',
    // The attack this card gives can't hit a base (owner 2026-10-01, for LOF_124 Niman Strike). Scoped to THAT
    // attack only — "for this phase" (JTL_092, JTL_206) and permanent restrictions (SOR_072, ASH_034) are not it.
    'attack-no-base' => '/(can[\'’]t attack bases for (?:this|these|the second) attacks?|for this attack, [^.]*?can[\'’]t attack bases)/i',
    'gives-shield'     => '/give [^.]*?shield tokens?/i',
    'gives-experience' => '/give [^.]*?experience tokens?/i',
    'gives-weakness'   => '/give [^.]*?weakness tokens?/i',
    // ⚠ ADVANTAGE MUST HAVE A HOME: v2's `buff` matched "give … (experience|shield|advantage) token", and
    // splitting the first two out left Advantage matching NOTHING — it silently un-tagged ASH_044 Barriss
    // Offee and broke the 'holdanswers' probe. Caught by bot_lossmining_test.
    'gives-advantage'  => '/give [^.]*?advantage tokens?/i',
    'bounce'  => '/return [^.]*? to (its|their) owner\'?s hand/i',
    'exhaust-enemy-unit'      => '/\bexhaust (a|an|another|each|up to \d+|that) (?![^.]*?\bfriendly\b)[^.]*?\bunit/i',
    'exhaust-friendly-unit'   => '/\bexhaust [^.]*?\bfriendly\b[^.]*?\bunit/i',
    'exhaust-enemy-resource'  => '/\bexhaust [^.]{0,40}(an enemy|that player|each opponent\'s|an opponent\'s)[^.]{0,20}resource/i',
    'exhaust-friendly-resource' => '/\bexhaust [^.]{0,40}(a friendly|your own|your)[^.]{0,20}resource/i',
    'sacrifice'        => '/\bdefeat (a|an|another|up to \d+|that) [^.]*?\bfriendly\b[^.]*?\bunit|\bdefeat (a|an|another) friendly\b/i',
    'capture'          => '/\bcaptures?\b/i',
    // Owner ruling 15: take-control is NOT treated as removal for now.
    'take-control'     => '/take control of/i',
    'defeat-upgrade'   => '/defeat (an|a|that|up to \d+|all) [^.]{0,24}upgrade/i',
    // Owner ruling 8: more specific than defeat-upgrade, because an upgrade-defeat is misvalued when the
    // opponent only has Experience tokens out.
    'defeat-shield'    => '/defeat (the |its |all |a |each )?(defender\'s )?shield/i',
    'defeat-friendly-resource' => '/defeat [^.]{0,30}friendly resource/i',
    'defeat-enemy-resource'    => '/defeat [^.]{0,30}(an enemy|each opponent\'s|an opponent\'s) resource/i',
    'steal-enemy-resource'     => '/take control of an enemy resource/i',
    'resource-ramp'    => '/(resource (this|that|it|a card|up to \d+)|move a card to (your|their) resources|put the top card[^.]*?as a resource)/i',
    'credit-ramp'      => '/create (a|an|\d+|two|three) credit tokens?/i',
    'discount'         => '/costs? \d+ resources? less/i',
    'recursion'        => '/(play [^.]*?from (your|their) discard pile|return [^.]*?from (your|their) discard pile to (your|their) hand)/i',
    // ⚠ "from A deck" is EITHER deck and earns BOTH: LAW_018 Lando's "discard a card from a deck" is the only
    // such card, and choosing WHICH deck is the whole point of the owner's 2026-09-23 Lando ruling.
    'mill-self'        => '/discard[^.]*?\bfrom (your|their|a) deck/i',
    'mill-opponent'    => '/discard[^.]*?\bfrom (an opponent\'s|each opponent\'s|a) deck/i',
    'search-top-deck'  => '/search the top \d+ cards/i',
    'gains-the-force'  => '/(the force is with you|create your force token)/i',
    'uses-the-force'   => '/use the force/i',
];

// Keywords the card HAS: the name starts a line, optionally with a number, then end-of-line, a parenthetical
// reminder, a bracketed cost, a comma, or a DASH.
// ⚠ THE DASH IS LOAD-BEARING. Bounty and Coordinate print their payoff after one ("Bounty - Draw a card.",
// "Coordinate - Ambush") and without it BOTH keywords tagged zero cards while every other keyword worked.
function SWUBotTagKeywords(string $text): array {
    $out = [];
    foreach (preg_split('/\r?\n/', $text) as $line) {
        $line = trim($line);
        foreach (SWU_BOT_TAG_KEYWORDS as $kw) {
            if (preg_match('/^' . preg_quote($kw, '/') . '(\s+\d+)?\s*($|[(\[,]|[-–—]\s)/', $line)) $out[] = strtolower($kw);
        }
    }
    return array_values(array_unique($out));
}

// Split out the text regions whose effects belong to somebody else, or are a cost rather than a payoff.
// Returns [remaining, bountyText, costText].
function SWUBotSplitOwnedClauses(string $text): array {
    $bounty = [];
    $rest = preg_replace_callback('/^\s*Bounty\s*[-–—]\s*([^\n]*)/mi', function ($m) use (&$bounty) {
        $bounty[] = $m[1];
        return '';
    }, $text);
    $costs = [];
    $rest = preg_replace_callback('/\[([^\]]*)\]/', function ($m) use (&$costs) {
        $costs[] = $m[1];
        return ' ';
    }, $rest);
    return [$rest, implode("\n", $bounty), implode("\n", $costs)];
}

// The EFFECT patterns over already-cleaned text. Shared by the card's own effects, its Bounty payoff and its
// costs, each under a different prefix.
function SWUBotTagEffects(string $rest): array {
    $tags = [];
    // 1. MASS effects first, removing each matched clause.
    foreach (SWU_BOT_WIPE_PATTERNS as $tag => $re) {
        if (!preg_match($re, $rest)) continue;
        $verb = str_starts_with($tag, 'damage') ? 'damage-wipe' : 'defeat-wipe';
        $side = substr($tag, strrpos($tag, '-') + 1);
        foreach ($side === 'both' ? ['friendly', 'enemy'] : [$side] as $s) $tags[] = "$verb-$s";
        $rest = preg_replace($re, '', $rest);
    }
    // 2. SELF-HARM next, so it cannot also read as damage/burn aimed outward.
    foreach (SWU_BOT_SELF_PATTERNS as $tag => $re) {
        if (!preg_match($re, $rest)) continue;
        $tags[] = $tag;
        $rest = preg_replace($re, '', $rest);
    }
    // 3. Everything else.
    foreach (SWU_BOT_TAG_PATTERNS as $tag => $re) {
        if (preg_match($re, $rest)) $tags[] = $tag;
    }
    // A capture removes a threat as surely as a defeat does (owner ruling 15 of form 1).
    if (in_array('capture', $tags, true) && !in_array('removal', $tags, true)) $tags[] = 'removal';
    // A MASS FRIENDLY DEFEAT IS ALSO A SACRIFICE (owner ruling 6, form 3: "part of me thinks sacrifice might be
    // better"). Analysed: the class is only TWO cards — HMW_253 Forced Pacification ("Defeat any number of
    // friendly units") and LOF_042 Always Two ("Choose 2 friendly Sith units … Defeat all other friendly
    // units") — and both are sacrifice ENGINES, paying your own board for a payoff, never an answer to theirs.
    // Tagged as BOTH: `sacrifice` is what it IS and is the name existing code already prices (the 'fodder'
    // path, SWUBotSacrificeCost), while `defeat-wipe-friendly` carries HOW MUCH is lost. Same shape as
    // capture + removal above.
    if (in_array('defeat-wipe-friendly', $tags, true) && !in_array('sacrifice', $tags, true)) $tags[] = 'sacrifice';
    // Keywords GRANTED to something else.
    foreach (SWU_BOT_TAG_GRANTABLE as $kw) {
        if (preg_match('/gains?\s+' . preg_quote($kw, '/') . '\b/i', $rest)) $tags[] = 'gives-' . strtolower($kw);
    }
    foreach (SWU_BOT_TAG_TOKENS as $name => $tag) {
        if (preg_match('/create (?:a|an|\d+|two|three) ' . preg_quote($name, '/') . ' tokens?/i', $rest)) $tags[] = $tag;
    }
    return array_values(array_unique($tags));
}

function SWUBotTagText(string $text): array {
    $tags = SWUBotTagKeywords($text);
    // Reminder text describes the keyword, not what playing the card does. Measured in v2: `buff` hit 605.
    $rest = preg_replace('/\([^)]*\)/', '', $text);
    [$rest, $bountyText, $costText] = SWUBotSplitOwnedClauses($rest);
    foreach (SWUBotTagEffects($bountyText) as $t) $tags[] = 'bounty-' . $t;
    foreach (SWUBotTagEffects($costText) as $t) $tags[] = 'cost-' . $t;
    foreach (SWUBotTagEffects($rest) as $t) $tags[] = $t;
    // ── DERIVED COMPATIBILITY TAGS ──────────────────────────────────────────────────────────────────────────
    // `wipe` and `exhaust` were RENAMED/SPLIT by the v3 rulings, but ~24 sites consume them — including two
    // per-archetype WEIGHTS (BotArchetypes.php: 'wipe' and 'exhaust'), which _SWUBotPlayValue sums as
    // $W[$tag] ?? 0.0, so dropping the name silently ZEROES the weight rather than erroring. Both derive from
    // the HOSTILE half only, which is exactly what v2's patterns matched and what every consumer means by them
    // ("is this an answer?"). The specific halves stay available for future weighting.
    // ⚠ DERIVED HERE, NOT IN SWUBotTagEffects, or the bounty/cost scans would emit 'bounty-exhaust' and
    // 'cost-wipe' — a compatibility alias for a region that has no consumer.
    // ⚠ `wipe` DERIVES FROM *BOTH* HALVES, WHICH REPRODUCES V2 EXACTLY — not from the enemy half alone.
    // v2's patterns were UNQUALIFIED ("deal N damage to each unit", "defeat each/all/any number of units") and
    // therefore never matched an enemy-only sweeper; in v3 an unqualified clause emits both halves, so "both
    // present" is the same set of cards. Deriving from the enemy half instead newly made ASH_112 Luke and
    // HMW_054 Seismic Detonation wipes, which moved BotResourcing's keep (+100 when 3+ enemy units) and broke
    // the PRE-p12 baseline that bot_midrange_levers_test pins for a MEASURED A/B. Ruling 25 is "add now, wire
    // deliberately": the retag must not shift behaviour on its own. Treating a one-sided sweeper as a wipe is
    // probably an improvement, but it is a deliberate wiring change with its own measurement, not a side effect.
    if (count(array_intersect($tags, ['damage-wipe-friendly', 'damage-wipe-enemy'])) === 2
        || count(array_intersect($tags, ['defeat-wipe-friendly', 'defeat-wipe-enemy'])) === 2) $tags[] = 'wipe';
    if (in_array('exhaust-enemy-unit', $tags, true)) $tags[] = 'exhaust';
    return array_values(array_unique($tags));
}

// A TOKEN card's own text DESCRIBES the mechanic it is, so it must not be read as a card that DOES it: the
// Shield token printings (ASH_T03, HMW_T01, …) say "defeat this Shield" and were picking up `defeat-shield`,
// which would have priced them as upgrade removal (owner ruling 7, form 3).
const SWU_BOT_TAG_TOKEN_SELF_DROP = ['defeat-shield', 'defeat-upgrade', 'gives-shield'];
function SWUBotDropSelfDescribingTokenTags(array $tags, string $type): array {
    if (!str_contains($type, 'Token')) return $tags;
    return array_values(array_diff($tags, SWU_BOT_TAG_TOKEN_SELF_DROP));
}

global $typeData;
$table = [];
foreach ($textData as $cardID => $text) {
    $faces = [];
    $type  = strval($typeData[$cardID] ?? '');
    $front = SWUBotDropSelfDescribingTokenTags(SWUBotTagText(strval($text)), $type);
    if (!empty($front)) $faces['front'] = $front;
    $dep = SWUBotDropSelfDescribingTokenTags(SWUBotTagText(strval($deployTextData[$cardID] ?? '')), $type);
    if (!empty($dep)) $faces['deployed'] = $dep;
    if (!empty($faces)) $table[strval($cardID)] = $faces;
}
ksort($table, SORT_STRING);

$out = "<?php\n// GENERATED by SWUSim/DevTools/rl/tag_cards.php — do not edit by hand; re-run the generator.\n"
     . "// TAGS V3: FACE-SCOPED ('front' / 'deployed') effect + keyword tags, kebab-case. SWUBotCardTags()\n"
     . "// returns the UNION of both faces so every v2 caller is unchanged; SWUBotCardTagsForFace() reads one.\n"
     . "\$GLOBALS['SWUBotCardTagTable'] = [\n";
foreach ($table as $id => $faces) {
    $parts = [];
    foreach ($faces as $face => $tags) {
        $parts[] = var_export($face, true) . ' => [' . implode(', ', array_map(fn($t) => var_export($t, true), $tags)) . ']';
    }
    $out .= '    ' . var_export($id, true) . ' => [' . implode(', ', $parts) . "],\n";
}
$out .= "];\n";
@mkdir('./SWUSim/Rl', 0775, true);
file_put_contents('./SWUSim/Rl/CardTags.generated.php', $out);

// Review aid. ⚠ Counts DISTINCT CARDS, not face-occurrences: a leader tagged on both faces would otherwise be
// counted twice and print as a duplicate in its own example list.
$counts = [];
$withDeployed = 0;
foreach ($table as $id => $faces) {
    if (isset($faces['deployed'])) $withDeployed++;
    $seen = [];
    foreach ($faces as $tags) foreach ($tags as $t) $seen[$t] = true;
    foreach (array_keys($seen) as $t) $counts[$t][] = $id;
}
ksort($counts);
echo 'Tagged ' . count($table) . ' of ' . count($textData) . " cards; {$withDeployed} carry a deployed face; "
   . count($counts) . " distinct tags.\n";
foreach ($counts as $t => $ids) {
    echo sprintf("  %-28s %5d  e.g. %s\n", $t, count($ids), implode(' ', array_slice($ids, 0, 5)));
}
