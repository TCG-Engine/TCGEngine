<?php
// Card tags (SWUSim/Rl/CardTags.php over the generated table from SWUSim/DevTools/rl/tag_cards.php).
// Every fixture's printed text was read with CardText() before use.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_card_tags_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Rl/CardTags.php';
include_once './SWUSim/Custom/BotFeatures.php';
$has  = fn($id, $t) => in_array($t, SWUBotCardTags($id), true);
$face = fn($id, $f, $t) => in_array($t, SWUBotCardTagsForFace($id, $f), true);

// ── The nine v2 tags still behave (these assertions predate v3 and must survive it) ──────────────────────────
$check($has('SOR_078', 'removal'), 'Vanquish → removal');
$check($has('SOR_222', 'bounce'), 'Waylay → bounce');
$check($has('ASH_151', 'wipe'), 'Operation Cinder → wipe');
$check(!$has('ASH_151', 'damage'), 'Operation Cinder: the wipe clause is not ALSO counted as targeted damage');
// v3 splits this into a ONE-SIDED mass effect, but the derived `wipe` still matches v2: it needs BOTH halves,
// so an enemy-only sweeper is not a wipe. That keeps BotResourcing's keep and the p12 A/B baseline put.
$check($has('HMW_054', 'damage-wipe-enemy') && !$has('HMW_054', 'damage-wipe-friendly'),
    'Seismic Detonation "each ENEMY unit" -> damage-wipe-enemy only, it never touches my board');
$check(!$has('HMW_054', 'wipe'), 'and a one-sided sweeper does not derive `wipe` - same set of cards as v2');
$check($has('SOR_233', 'damage') && $has('SOR_233', 'draw'), 'I Am Your Father → damage + draw');
$check($has('SOR_074', 'heal'), 'Repair → heal');
$check($has('SOR_220', 'buff'), 'Surprise Strike → buff');
$check($has('SEC_078', 'wipe'), 'Hyperspace Disaster "Defeat all space units." → wipe');
$check($has('ASH_053', 'wipe'), 'Pre Vizsla "Defeat any number of non-leader units…" → wipe');
$check($has('LAW_101', 'damage') && !$has('LAW_101', 'wipe'), 'Lawbringer "each enemy unit with that aspect -2/-2" → damage, not wipe');
$check($has('JTL_043', 'removal'), 'No Glory "Take control … then defeat it." → removal');
$check($has('LAW_131', 'removal') && $has('JTL_079', 'removal'), 'Incapacitate -2/-2 and Out the Airlock -5/-5 → removal');
$check($has('LOF_035', 'removal') && $has('LOF_070', 'removal'), '"you may give a unit -3/-3" → removal');
$check($has('LAW_132', 'removal'), 'The Tree Remembers "…If it costs 3 or less, defeat it." → removal');
$check($has('ASH_052', 'removal') && $has('ASH_052', 'heal'), 'Chimaera ASH → removal + heal (the engine that healed 26 in one real game)');
$check($has('ASH_148', 'damage'), 'Ninth Sister "deal damage equal to its cost divided…" → damage');
$check(!$has('SOR_216', 'removal'), 'Disarm (-4/-0) defeats nothing → not removal');
$check(SWUBotCardTags('SOR_095') === [], 'a vanilla unit (Battlefield Marine) → no tags');
$check(SWUBotCardTags('NOT_A_CARD') === [], 'unknown card → no tags');

// ── v3: the STALE table was the bug that started this (77 cards matched a rule and had none) ─────────────────
// HMW_186 Mining Guild Trespasser closed all three of the 2026-09-28 game logs and was tagged NOTHING, so the
// scorer priced it as a vanilla body.
$check($has('HMW_186', 'damage') && $has('HMW_186', 'burn'),
    'Mining Guild Trespasser "deal 2 damage to a base and 2 damage to an enemy unit" → damage + burn');

// ── v3: BOTH FACES, face-scoped (owner ruling: the two faces can want opposite decisions) ────────────────────
$check(SWUBotCardTagsAreFaceScoped(), 'v3 is face-scoped');
$check($face('SOR_001', 'deployed', 'restore'), 'Director Krennic SOR_001: the DEPLOYED side has Restore — v2 read textData only and saw nothing');
$check(SWUBotCardTagsForFace('SOR_001', 'front') === [], 'SOR_001 front face has no tags of its own');
$check($face('LAW_018', 'front', 'mill-opponent') && $face('LAW_018', 'front', 'mill-self'),
    'Lando LAW_018 "discard a card from A deck" → BOTH mill tags; which deck is the whole decision');
$check($face('LAW_018', 'deployed', 'sacrifice') && !$face('LAW_018', 'front', 'sacrifice'),
    'Lando LAW_018: only the DEPLOYED side defeats a Credit — the faces really differ');
$check($has('LAW_018', 'sacrifice'), 'SWUBotCardTags() unions the faces, so v2 callers see everything');

// ── v3: keywords a card HAS are tagged; keywords it GRANTS are not ───────────────────────────────────────────
$check($has('LOF_061', 'shielded'), 'Secretive Sage "Shielded (…)" → shielded');
$check($has('SHD_036', 'grit') && $has('SHD_036', 'smuggle'), 'First Light SHD_036 → grit + smuggle');
$check(!$has('SOR_100', 'ambush'), 'Wedge GRANTS Ambush mid-sentence — he does not have it');
$check($has('SHD_036', 'grit'), 'SHD_036 has "Grit" on its own line AND "gains Grit" in a clause — line position decides');
$check($has('TWI_106', 'coordinate'), 'Coordinate prints its payoff after a DASH ("Coordinate - Ambush") — the separator set must allow it');
$check($has('SHD_027', 'bounty'), 'Bounty prints after a dash too ("Bounty - Draw a card.")');

// ── v3: a BOUNTY payoff is the OPPONENT'S reward, so it must not read as mine ────────────────────────────────
$check($has('SHD_027', 'bounty-draw') && !$has('SHD_027', 'draw'),
    'SHD_027 "Bounty - Draw a card." → bounty-draw, NOT draw: the opponent collects it');

// ── v3: the splits, so each half can be priced on its own ───────────────────────────────────────────────────
$check($has('SOR_218', 'exhaust-enemy-unit') && $has('SOR_218', 'gives-shield'),
    'Asteroid Sanctuary → exhaust-enemy-unit + gives-shield (a Shield is defensive, not a stat buff)');
$check(!$has('SOR_218', 'buff'), 'giving a Shield token is no longer folded into buff');
$check($has('JTL_237', 'indirect-damage') && !$has('JTL_237', 'burn'),
    'indirect damage is its own tag, no longer burn (owner ruling C12)');
$check($has('ASH_079', 'create-mandalorian-token') && $has('ASH_079', 'gives-sentinel'),
    'Koska Reeves ASH_079 → create-mandalorian-token + gives-sentinel (was untagged entirely)');
// LAW_008's sacrifice is a bracketed COST, so it is cost-sacrifice — asserted in the cost section below.
$check($has('LAW_008', 'credit-ramp') && $has('LAW_008', 'create-credit-token'),
    'Krennic LAW_008 -> credit-ramp + create-credit-token');
// ⚠ Expendable Mercenary RESOURCES itself out of the discard, it does not replay itself — that is ramp, not
// recursion, and it is the half of the Krennic engine that refills the resource row. `recursion` is reserved
// for playing/returning a card from the discard (HMW_109 below).
$check($has('LAW_159', 'resource-ramp') && !$has('LAW_159', 'recursion'),
    'Expendable Mercenary "resource this unit from its owner\'s discard pile" → resource-ramp, not recursion');
$check($has('HMW_109', 'recursion'), 'Tireless Magnaguard "play this unit from your discard pile" → recursion');
$check($has('SOR_044', 'restore') && !$has('SOR_044', 'heal'),
    'Restore is the KEYWORD tag and is not an immediate heal (owner ruling B5)');

// ── The table's shape and vocabulary ────────────────────────────────────────────────────────────────────────
$all = $GLOBALS['SWUBotCardTagTable'] ?? [];
$bad = $badFace = $badCase = [];
foreach ($all as $id => $faces) {
    if (!is_array($faces)) { $bad[] = "$id:not-an-array"; continue; }
    foreach ($faces as $f => $tags) {
        if ($f !== 'front' && $f !== 'deployed') $badFace[] = "$id:$f";
        foreach ($tags as $t) { if (!preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $t)) $badCase[] = "$id:$t"; }
    }
}
$check(count($all) > 1500, 'the v3 table is populated — ' . count($all) . ' cards (v2 had 950)');
$check(empty($badFace), 'every key is a face name: ' . json_encode(array_slice($badFace, 0, 5)));
$check(empty($badCase), 'every tag is kebab-case: ' . json_encode(array_slice($badCase, 0, 5)));
$check(empty($bad), 'every entry is face-scoped: ' . json_encode(array_slice($bad, 0, 5)));

// ── RL move keys are PINNED to the nine v2 names, or every trained policy's key space changes ────────────────
$rl = SWUBotCardTagsForRlKey('ASH_079');
$check($rl === [] || $rl === array_values(array_intersect($rl, SWU_BOT_TAGS_V2_SET)),
    'the RL key drops v3-only tags; ASH_079 → ' . json_encode($rl));
$check(in_array('removal', SWUBotCardTagsForRlKey('SOR_078'), true), 'the RL key keeps the v2 tags it always had');
$check(!in_array('create-mandalorian-token', SWUBotCardTagsForRlKey('ASH_079'), true), 'a v3-only tag never reaches an RL key');

// ── The frozen generations ──────────────────────────────────────────────────────────────────────────────────
SWUBotSetDisabledFeatures(['tags2']);
$check(SWUBotCardTags('JTL_043') === [] && SWUBotCardTags('SOR_078') === ['removal'], '@no-tags2 reads the frozen v1 table');
$check(SWUBotCardTagsForFace('SOR_001', 'deployed') === [] && !SWUBotCardTagsAreFaceScoped(),
    '@no-tags2 is not face-scoped, and says so rather than reporting "this face does nothing"');
SWUBotSetDisabledFeatures(['tags3']);
$check($has('JTL_237', 'burn') && !$has('JTL_237', 'indirect-damage'), '@no-tags3 reads the frozen v2 table (indirect was burn there)');
$check(SWUBotCardTags('HMW_186') === [], '@no-tags3 still shows the stale v2 gap, so the A/B has a real baseline');
SWUBotSetDisabledFeatures([]);
$check($has('HMW_186', 'burn'), 'and with everything on, the gap is closed');

// ── v3 form 2 (owner rulings 2026-09-28): who it HURTS is part of the tag ────────────────────────────────────
// A drawback must never read as an upside. ASH_151 Operation Cinder is the owner's own example: "it may seem
// very powerful as a wipe, but it has the drawback of 5 to your own base".
$check($has('ASH_151', 'damage-wipe-friendly') && $has('ASH_151', 'damage-wipe-enemy'),
    'Operation Cinder hits BOTH sides -> damage-wipe-friendly + damage-wipe-enemy');
$check($has('ASH_151', 'self-burn'), 'Operation Cinder "deal 5 damage to your base" -> self-burn, the drawback is visible');
$check(!$has('ASH_151', 'burn'), 'damaging MY OWN base is not reach - `burn` would have priced it as a win condition');
$check($has('SHD_036', 'cost-self-damage') && !$has('SHD_036', 'damage') && !$has('SHD_036', 'self-damage'),
    "First Light's Smuggle cost \"deal 4 damage to a friendly unit\" -> cost-self-damage only");
$check($has('LAW_008', 'cost-sacrifice') && !$has('LAW_008', 'sacrifice'),
    'Krennic LAW_008 "[Exhaust, defeat a friendly unit]" -> cost-sacrifice: it is a price, not an effect');

// A v2 FALSE POSITIVE the split removed: "defeat any number of friendly CREDIT TOKENS" is not a board answer.
$check(!$has('LOF_147', 'wipe') && !$has('LOF_147', 'defeat-wipe-friendly'),
    "Kit Fisto's Aethersprite defeats friendly CREDIT TOKENS, not units - no longer a wipe");

// ── v3 form 2: the new categories ───────────────────────────────────────────────────────────────────────────
$check($has('SEC_163', 'defeat-upgrade'), 'Outer Rim Constable "You may defeat an upgrade" -> defeat-upgrade (was untagged)');
$check($has('SOR_100', 'gives-ambush') && !$has('SOR_100', 'ambush'), 'Wedge GRANTS Ambush -> gives-ambush, never ambush');
$check($has('JTL_043', 'take-control'), 'No Glory -> take-control');
$check($has('SHD_213', 'steal-enemy-resource'), 'DJ "Take control of an enemy resource" -> steal-enemy-resource');

// ── DERIVED compatibility tags. ~24 sites read `wipe` and `exhaust`, including two per-archetype WEIGHTS that
// _SWUBotPlayValue sums as $W[$tag] ?? 0.0 - so a missing name silently ZEROES a weight instead of erroring.
$check($has('ASH_151', 'wipe'), 'wipe is still emitted, derived from BOTH halves being present');
$check($has('SOR_218', 'exhaust'), 'exhaust is still emitted, derived from exhaust-enemy-unit');
$check(!$has('ASH_231', 'exhaust') && $has('ASH_231', 'exhaust-friendly-unit'),
    'exhausting a FRIENDLY unit does not derive exhaust - it is a cost, and v2 never matched it either');
$bad = [];
foreach ($GLOBALS['SWUBotCardTagTable'] as $id => $faces) {
    foreach ($faces as $tags) foreach ($tags as $t) {
        if ($t === 'bounty-exhaust' || $t === 'cost-wipe' || $t === 'bounty-wipe') $bad[] = "$id:$t";
    }
}
$check(empty($bad), 'a derived alias is never prefixed (no bounty-exhaust / cost-wipe): ' . json_encode($bad));

// ── COUNT REGRESSION GUARD (owner ruling 21). A data import that silently re-tags 200 cards, or a pattern edit
// that quietly stops matching, moves these numbers. The tolerance is generous because new sets legitimately add
// cards; what it really catches is a tag going to ZERO, doubling, appearing, or vanishing.
const TAG_BASELINE = [
    'ambush' => 87,
    'bounce' => 57,
    'bounty' => 11,
    'bounty-capture' => 1,
    'bounty-damage' => 2,
    'bounty-draw' => 4,
    'bounty-exhaust-enemy-unit' => 1,
    'bounty-gives-experience' => 1,
    'bounty-recursion' => 1,
    'bounty-removal' => 1,
    'bounty-resource-ramp' => 1,
    'buff' => 123,
    'burn' => 39,
    'capture' => 29,
    'coordinate' => 22,
    'cost-bounce' => 2,
    'cost-defeat-friendly-resource' => 1,
    'cost-defeat-upgrade' => 1,
    'cost-exhaust-friendly-unit' => 2,
    'cost-mill-self' => 1,
    'cost-removal' => 1,
    'cost-sacrifice' => 8,
    'cost-self-burn' => 1,
    'cost-self-damage' => 2,
    'cost-uses-the-force' => 12,
    'create-battle-droid-token' => 25,
    'create-beast-token' => 19,
    'create-clone-trooper-token' => 18,
    'create-credit-token' => 28,
    'create-mandalorian-token' => 16,
    'create-spy-token' => 27,
    'create-tie-fighter-token' => 8,
    'create-x-wing-token' => 10,
    'credit-ramp' => 28,
    'damage' => 197,
    'damage-wipe-enemy' => 25,
    'damage-wipe-friendly' => 15,
    'debuff' => 51,
    'defeat-friendly-resource' => 4,
    'defeat-shield' => 4,
    'defeat-upgrade' => 27,
    'defeat-wipe-enemy' => 8,
    'defeat-wipe-friendly' => 10,
    'discount' => 91,
    'draw' => 111,
    'exhaust' => 80,
    'exhaust-enemy-resource' => 1,
    'exhaust-enemy-unit' => 80,
    'exhaust-friendly-unit' => 11,
    'exploit' => 22,
    'fortify' => 15,
    'gains-the-force' => 23,
    'gives-advantage' => 34,
    'gives-ambush' => 23,
    'gives-experience' => 123,
    'gives-grit' => 14,
    'gives-hidden' => 8,
    'gives-overwhelm' => 27,
    'gives-raid' => 42,
    'gives-restore' => 22,
    'gives-saboteur' => 13,
    'gives-sentinel' => 52,
    'gives-shield' => 74,
    'gives-shielded' => 5,
    'gives-weakness' => 22,
    'grit' => 54,
    'heal' => 93,
    'hidden' => 66,
    'indirect-damage' => 22,
    'mill-opponent' => 4,
    'mill-self' => 20,
    'overwhelm' => 89,
    'piloting' => 38,
    'plot' => 30,
    'raid' => 97,
    'recursion' => 37,
    'removal' => 103,
    'resource-ramp' => 19,
    'restore' => 97,
    'saboteur' => 71,
    'sacrifice' => 37,
    'search-top-deck' => 58,
    'self-burn' => 12,
    'self-damage' => 34,
    'sentinel' => 116,
    'shielded' => 65,
    'smuggle' => 31,
    'steal-enemy-resource' => 1,
    'take-control' => 23,
    'uses-the-force' => 32,
    'wipe' => 22,
];
$live = [];
foreach ($GLOBALS['SWUBotCardTagTable'] as $id => $faces) {
    $seen = [];
    foreach ($faces as $tags) foreach ($tags as $t) $seen[$t] = true;
    foreach (array_keys($seen) as $t) $live[$t] = ($live[$t] ?? 0) + 1;
}
$vanished = array_values(array_diff(array_keys(TAG_BASELINE), array_keys($live)));
$appeared = array_values(array_diff(array_keys($live), array_keys(TAG_BASELINE)));
$drifted = [];
foreach (TAG_BASELINE as $t => $n) {
    $now = $live[$t] ?? 0;
    if ($now < $n * 0.75 || $now > $n * 1.25) $drifted[] = "$t: $n to $now";
}
$check(empty($vanished), 'no tag vanished: ' . json_encode($vanished));
$check(empty($appeared), 'no unlisted tag appeared (update TAG_BASELINE deliberately): ' . json_encode($appeared));
$check(empty($drifted), 'no tag drifted more than 25 percent: ' . json_encode(array_slice($drifted, 0, 6)));
$check(count($live) === count(TAG_BASELINE), 'the taxonomy is ' . count(TAG_BASELINE) . ' tags; got ' . count($live));

bot_test_finish();
