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
$check(!$has('ASH_151', 'damage-enemy-unit'), 'Operation Cinder: the wipe clause is not ALSO counted as targeted damage');
// v3 splits this into a ONE-SIDED mass effect, but the derived `wipe` still matches v2: it needs BOTH halves,
// so an enemy-only sweeper is not a wipe. That keeps BotResourcing's keep and the p12 A/B baseline put.
$check($has('HMW_054', 'damage-wipe-enemy') && !$has('HMW_054', 'damage-wipe-friendly'),
    'Seismic Detonation "each ENEMY unit" -> damage-wipe-enemy only, it never touches my board');
$check(!$has('HMW_054', 'wipe'), 'and a one-sided sweeper does not derive `wipe` - same set of cards as v2');
$check($has('SOR_233', 'damage-enemy-unit') && $has('SOR_233', 'draw'), 'I Am Your Father → damage-enemy-unit + draw');
$check($has('SOR_074', 'heal'), 'Repair → heal');
$check($has('SOR_220', 'buff'), 'Surprise Strike → buff');
$check($has('SEC_078', 'wipe'), 'Hyperspace Disaster "Defeat all space units." → wipe');
$check($has('ASH_053', 'wipe'), 'Pre Vizsla "Defeat any number of non-leader units…" → wipe');
$check($has('LAW_101', 'debuff-all-enemy-units') && !$has('LAW_101', 'wipe'), 'Lawbringer "each enemy unit with that aspect -2/-2" → damage, not wipe');
$check($has('JTL_043', 'removal'), 'No Glory "Take control … then defeat it." → removal');
$check($has('LAW_131', 'removal') && $has('JTL_079', 'removal'), 'Incapacitate -2/-2 and Out the Airlock -5/-5 → removal');
$check($has('LOF_035', 'removal') && $has('LOF_070', 'removal'), '"you may give a unit -3/-3" → removal');
$check($has('LAW_132', 'removal'), 'The Tree Remembers "…If it costs 3 or less, defeat it." → removal');
$check($has('ASH_052', 'removal') && $has('ASH_052', 'heal'), 'Chimaera ASH → removal + heal (the engine that healed 26 in one real game)');
$check($has('ASH_148', 'damage-enemy-unit'), 'Ninth Sister "deal damage equal to its cost divided…" → damage-enemy-unit (spread cards carry it too)');
$check(!$has('SOR_216', 'removal'), 'Disarm (-4/-0) defeats nothing → not removal');
$check(SWUBotCardTags('SOR_095') === [], 'a vanilla unit (Battlefield Marine) → no tags');
$check(SWUBotCardTags('NOT_A_CARD') === [], 'unknown card → no tags');

// ── v3: the STALE table was the bug that started this (77 cards matched a rule and had none) ─────────────────
// HMW_186 Mining Guild Trespasser closed all three of the 2026-09-28 game logs and was tagged NOTHING, so the
// scorer priced it as a vanilla body.
$check($has('HMW_186', 'damage-enemy-unit') && $has('HMW_186', 'damage-enemy-base'),
    'Mining Guild Trespasser "deal 2 damage to a base and 2 damage to an enemy unit" → damage-enemy-unit + damage-enemy-base');

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
$check($has('JTL_237', 'indirect-damage') && !$has('JTL_237', 'damage-enemy-base'),
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
$check($has('HMW_186', 'damage-enemy-base'), 'and with everything on, the gap is closed');

// ── v3 form 2 (owner rulings 2026-09-28): who it HURTS is part of the tag ────────────────────────────────────
// A drawback must never read as an upside. ASH_151 Operation Cinder is the owner's own example: "it may seem
// very powerful as a wipe, but it has the drawback of 5 to your own base".
$check($has('ASH_151', 'damage-wipe-friendly') && $has('ASH_151', 'damage-wipe-enemy'),
    'Operation Cinder hits BOTH sides -> damage-wipe-friendly + damage-wipe-enemy');
$check($has('ASH_151', 'self-burn'), 'Operation Cinder "deal 5 damage to your base" -> self-burn, the drawback is visible');
$check(!$has('ASH_151', 'damage-enemy-base'), 'damaging MY OWN base is not reach - damage-enemy-base would have priced it as a win condition');
$check(!$has('SOR_217', 'burn') && !$has('HMW_186', 'burn'), '`burn` is RETIRED (2026-10-01) — damage-enemy-base carries it');
$check($has('SHD_036', 'cost-self-damage') && !$has('SHD_036', 'damage-enemy-unit') && !$has('SHD_036', 'self-damage'),
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

// ── PUMP and ATTACK-NO-BASE (owner 2026-10-01). Pump = "attack with a unit, it gets +X/+0 for this attack".
// The wording varies more than it looks, and each of these was missed by `buff` or left wholly untagged.
$check($has('SEC_179', 'pump'), 'Aggressive Negotiations "For this attack, it gets +1/+0 for each card" -> pump (was untagged)');
$check($has('JTL_156', 'pump'), 'Trench Run "For this attack, it gets +4/+0 and gains…" -> pump (was untagged)');
$check($has('SOR_220', 'pump') && $has('SOR_220', 'buff'), 'Surprise Strike -> pump, and KEEPS buff so its weight does not move');
$check($has('SOR_240', 'pump'), 'Fleet Lieutenant "If it\'s a Rebel unit, it gets +2/+0" -> pump: a conditional boost still counts');
$check($has('IBH_064', 'pump'), 'Hoth Lieutenant, a UNIT\'s When Played attack -> pump');
$check($has('TWI_011', 'pump'), 'Ahsoka TWI_011, a LEADER Action attack -> pump');
$check($has('SHD_145', 'pump'), 'Headhunting "…gets +2/+0 for its attack", three sentences after "Attack with" -> pump');
$check(!$has('ASH_109', 'pump') && $has('ASH_109', 'buff'),
    'T-6 Shuttle 1974 "+2/+2 for this phase. You may attack" -> buff, NOT pump: the boost is not scoped to the attack');
$check($has('LOF_124', 'pump') && $has('LOF_124', 'attack-no-base'), 'Niman Strike -> pump + attack-no-base');
$check($has('LOF_140', 'attack-no-base'), 'Darth Maul\'s Lightsaber "For this attack, he … can\'t attack bases" -> attack-no-base');
$check(!$has('JTL_092', 'attack-no-base') && !$has('SOR_072', 'attack-no-base'),
    'a phase-long (Scramble Fighters) or permanent (Entrenched) restriction is not attack-no-base');
$check(!in_array('pump', SWUBotCardTagsForRlKey('SEC_179'), true), 'pump never reaches an RL key (pinned to v2)');

// GRANTS-ATTACK: the extra attack itself, so `pump` is its subset ("Lieutenant" units carry both).
$check($has('SOR_240', 'grants-attack') && $has('SOR_240', 'pump'), 'Fleet Lieutenant -> grants-attack AND pump');
$check($has('SHD_223', 'grants-attack'), 'Snapshot Reflexes, an UPGRADE that attacks with its host -> grants-attack (was untagged)');
$check($has('TWI_248', 'grants-attack'), 'Ahsoka\'s Padawan Lightsaber "you may attack with a unit" -> grants-attack (was untagged)');
$check($has('SHD_128', 'grants-attack'), 'Outflank "Attack with 2 units" -> grants-attack (was untagged)');
$check($has('ASH_109', 'grants-attack') && !$has('ASH_109', 'pump'), 'T-6 Shuttle 1974 grants an attack, but its phase-long +2/+2 is no pump');
$check(!$has('ASH_037', 'grants-attack'), 'Red Leader "may attack units in either arena" is a targeting PERMISSION, not an attack');

// SHOOT-FIRST: any first-strike, granted or printed.
$check($has('SOR_217', 'shoot-first') && $has('SOR_217', 'pump'), 'Shoot First -> shoot-first + pump');
$check($has('SOR_198', 'shoot-first') && !$has('SOR_198', 'grants-attack'), 'Han Solo SOR_198 "While attacking, … before the defender" -> shoot-first, no attack granted');
$check($has('LAW_219', 'shoot-first'), 'Anakin\'s Podracer "…before the defending unit" -> shoot-first');
$check(!$has('LAW_086', 'shoot-first'), 'The Stranger lets the DEFENDER strike first - the reverse, not shoot-first');

// POWER-STRIKE (owner 2026-10-01): damage equal to a unit's POWER, to a unit. Both wordings, and alongside `damage`.
$check($has('SOR_127', 'power-strike'), 'Strike True "a friendly unit deals damage equal to its power" -> power-strike (the fight form `damage` never matched)');
$check($has('HMW_151', 'power-strike') && $has('HMW_151', 'resource-ramp'), 'Overgrowth -> power-strike, and keeps resource-ramp');
$check($has('LAW_168', 'power-strike') && $has('LAW_168', 'gives-experience'), 'Haymaker -> power-strike + gives-experience');
$check($face('LAW_008', 'deployed', 'power-strike') && !$face('LAW_008', 'front', 'power-strike'),
    'Krennic (LAW): the power strike is his DEPLOYED side (When Deployed), not the front');
$check($has('ASH_102', 'power-strike') && $has('ASH_102', 'damage-enemy-unit'), 'Ravager "have it deal damage equal to its power" -> power-strike + damage-enemy-unit');
$check($has('SOR_092', 'power-strike'), 'Overwhelming Barrage, the DIVIDED form, is still a power strike');
$check(!$has('LOF_128', 'power-strike') && !$has('HMW_192', 'power-strike'),
    'Protect the Pod (remaining HP) and Volley Fire (Raid) scale with something other than power -> not power-strike');
$check($has('HMW_036', 'power-strike') && !$has('HMW_036', 'damage-friendly-unit'),
    'Kelnacca "equal to THIS UNIT\'S power" is a power strike, never damage to itself');
$check($has('LOF_091', 'power-strike'), 'Craving Power "deal damage to an enemy unit equal to attached unit\'s power" — the reversed word order');

// DIRECTIONAL DAMAGE / HEAL (owner 2026-10-01), alongside the old names, which stay exactly as they were.
$check($has('SOR_052', 'heal-friendly-units-spread') && $has('SOR_052', 'heal-friendly-base-spread') && $has('SOR_052', 'damage-friendly-unit'),
    'Redemption -> heal-friendly-units-spread + heal-friendly-base-spread + damage-friendly-unit (the owner\'s minimum)');
$check($has('ASH_052', 'heal-on-enemy-defeat') && !$has('LAW_133', 'heal-on-enemy-defeat'),
    'Chimaera "When an enemy unit is defeated: Heal 2" -> heal-on-enemy-defeat; Lost and Forgotten (a one-shot heal) is not an engine');
$check($has('SOR_172', 'damage-enemy-unit') && !$has('SOR_172', 'damage'), 'Open Fire "Deal 4 damage to a unit" (unqualified) -> damage-enemy-unit; `damage` is retired');

// ── `damage` RETIRED (owner 2026-10-01): no card may carry it, and every replacement is in place ─────────────
$stale = [];
foreach ($GLOBALS['SWUBotCardTagTable'] as $id => $faces) foreach ($faces as $tags) foreach ($tags as $t) {
    if (in_array($t, ['damage', 'bounty-damage', 'cost-damage', 'burn', 'bounty-burn', 'cost-burn'], true)) $stale[] = "$id:$t";
}
$check(empty($stale), 'no card carries the retired `damage` or `burn` tag: ' . json_encode(array_slice($stale, 0, 5)));
$check($has('HMW_186', 'damage-enemy-unit') && $has('SOR_134', 'damage-enemy-unit'),
    'the verb-less second clause "deal 2 damage to a base AND 2 damage to an enemy unit" -> damage-enemy-unit');
$check($has('SHD_172', 'damage-enemy-unit'), 'Krayt Dragon "…to their base OR a ground unit" -> damage-enemy-unit');
$check($has('HMW_263', 'damage-enemy-unit') && $has('HMW_263', 'damage-friendly-unit'),
    'Wrecker: each player picks one of THEIR OWN units -> enemy AND friendly damage (owner)');
$check($has('JTL_140', 'damage-enemy-unit') && $has('SOR_135', 'damage-enemy-unit'),
    'spread cards carry damage-enemy-unit too (owner), so one weighted tag covers all enemy damage');
$check(!$has('TWI_155', 'damage-enemy-unit') && $has('TWI_155', 'damage-friendly-unit'),
    'Twice the Pride damages YOUR attached unit — it was mis-valued as `damage`, now only the friendly tag');
$check($has('SEC_051', 'debuff-all-enemy-units') && $has('TWI_075', 'debuff-all-enemy-units'),
    'Bo-Katan SEC_051 / Disruptive Burst "give each enemy unit -X/-X" -> debuff-all-enemy-units');
$check(in_array('damage-enemy-unit', SWUBotCardTagsForRlKey('SOR_172'), true) && !in_array('damage', SWUBotCardTagsForRlKey('SOR_172'), true),
    'RL keys carry damage-enemy-unit now (owner: break the keys)');
$check($has('SOR_127', 'damage-enemy-unit') && $has('SOR_127', 'power-strike'), 'Strike True -> power-strike + damage-enemy-unit (owner)');
$check($has('JTL_140', 'damage-enemy-unit-spread') && !$has('JTL_140', 'damage-all-units-spread'),
    'IG-2000 "1 damage to each of up to 3 units" -> enemy SPREAD (owner), not all-units');
$check($has('SOR_092', 'damage-all-units-spread') && $has('SOR_092', 'damage-enemy-unit-spread')
    && $has('ASH_148', 'damage-all-units-spread') && $has('ASH_148', 'damage-enemy-unit-spread'),
    'Overwhelming Barrage and Ninth Sister (unqualified divided) get BOTH spread tags (owner)');
$check($has('SOR_135', 'damage-enemy-unit-spread') && !$has('SOR_135', 'damage-all-units-spread'),
    'Palpatine SOR "divided among ENEMY units" -> enemy spread only');
$check($has('ASH_151', 'damage-friendly-base') && $has('ASH_151', 'self-burn'), 'Operation Cinder: self-burn -> also damage-friendly-base');
$check($has('ASH_012', 'damage-enemy-base'), 'Vane "Deal 2 damage to a base" -> damage-enemy-base (from burn)');
$check($has('SHD_036', 'cost-damage-friendly-unit') && !$has('SHD_036', 'damage-friendly-unit'),
    'First Light\'s Smuggle COST damages your unit -> cost-damage-friendly-unit, never the payoff tag');
// Regression guards from the 2026-10-01 review: each was a FALSE hit or miss of the first draft.
$check(!$has('LOF_108', 'damage-friendly-unit') && !$has('SEC_042', 'damage-friendly-unit'), '"would deal damage … prevent" is PREVENTION, not damage');
$check(!$has('TWI_016', 'damage-enemy-unit'), 'Jango TWI "When a friendly unit deals damage to an enemy unit:" is a TRIGGER, not damage');
$check(!$has('TWI_234', 'damage-enemy-unit'), 'The Invisible Hand damages a BASE "for each unit" — not unit damage');
$check($has('ASH_035', 'damage-enemy-unit'), 'Repulsor Train "to a ground unit for each friendly exhausted unit" IS enemy damage');
$check($has('TWI_155', 'damage-friendly-unit'), 'Twice the Pride "Deal 2 damage to attached unit" damages your own unit');

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
    'attack-no-base' => 11,
    'bounce' => 57,
    'bounty' => 11,
    'bounty-capture' => 1,
    'bounty-damage-enemy-unit' => 2,
    'bounty-draw' => 4,
    'bounty-exhaust-enemy-unit' => 1,
    'bounty-gives-experience' => 1,
    'bounty-recursion' => 1,
    'bounty-removal' => 1,
    'bounty-resource-ramp' => 1,
    'buff' => 123,
    'capture' => 29,
    'coordinate' => 22,
    'cost-bounce' => 2,
    'cost-damage-friendly-base' => 1,
    'cost-damage-friendly-unit' => 2,
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
    'damage-all-units-spread' => 4,
    'damage-enemy-base' => 39,
    'damage-enemy-unit' => 254,
    'damage-enemy-unit-spread' => 16,
    'damage-friendly-base' => 12,
    'damage-friendly-unit' => 39,
    'damage-wipe-enemy' => 25,
    'damage-wipe-friendly' => 15,
    'debuff' => 51,
    'debuff-all-enemy-units' => 3,
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
    'grants-attack' => 108,
    'grit' => 54,
    'heal' => 93,
    'heal-friendly-base-spread' => 2,
    'heal-friendly-units-spread' => 4,
    'heal-on-enemy-defeat' => 2,
    'hidden' => 66,
    'indirect-damage' => 22,
    'mill-opponent' => 4,
    'mill-self' => 20,
    'overwhelm' => 89,
    'piloting' => 38,
    'plot' => 30,
    'power-strike' => 22,
    'pump' => 62,
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
    'shoot-first' => 9,
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
