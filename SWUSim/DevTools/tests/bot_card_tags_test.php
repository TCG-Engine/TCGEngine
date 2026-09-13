<?php
// Phase 1a Task 4 — coarse card tags (SWUSim/Rl/CardTags.php over the generated table from
// SWUSim/DevTools/rl/tag_cards.php). Each fixture's printed text was read with CardText() before use:
//   SOR_078 Vanquish          "Defeat a non-leader unit."                                  → removal
//   SOR_222 Waylay            "Return a non-leader unit to its owner's hand."              → bounce
//   ASH_151 Operation Cinder  "Deal 5 damage to your base. Then, deal 5 damage to each unit." → wipe (not damage)
//   HMW_054 Seismic Detonation "…deal 3 damage to each ENEMY unit in that arena."          → damage, NOT wipe
//   SOR_233 I Am Your Father  "Deal 7 damage to an enemy unit unless … If they do, draw 3 cards." → damage + draw
//   SOR_074 Repair            "Heal 3 damage from a unit or base."                         → heal
//   SOR_220 Surprise Strike   "Attack with a unit. It gets +3/+0 for this attack."         → buff
//   SOR_218 Asteroid Sanctuary "Exhaust an enemy unit. / Give a Shield token to …"         → exhaust + buff
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_card_tags_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Rl/CardTags.php';
include_once './SWUSim/Custom/BotFeatures.php';
$has = fn($id, $t) => in_array($t, SWUBotCardTags($id), true);

$check($has('SOR_078', 'removal'), 'Vanquish → removal');
$check($has('SOR_222', 'bounce'), 'Waylay → bounce');
$check($has('ASH_151', 'wipe'), 'Operation Cinder → wipe');
$check(!$has('ASH_151', 'damage'), 'Operation Cinder: the wipe clause is not ALSO counted as targeted damage');
$check($has('HMW_054', 'damage') && !$has('HMW_054', 'wipe'), 'Seismic Detonation hits EACH ENEMY unit → damage, not wipe');
$check($has('SOR_233', 'damage') && $has('SOR_233', 'draw'), 'I Am Your Father → damage + draw');
$check($has('SOR_074', 'heal'), 'Repair → heal');
$check($has('SOR_220', 'buff'), 'Surprise Strike → buff');
$check($has('SOR_218', 'exhaust') && $has('SOR_218', 'buff'), 'Asteroid Sanctuary → exhaust + buff (the Shield token)');
$check(!$has('ASH_103', 'removal'), 'Long Live the Empire — "Defeat a FRIENDLY Imperial unit" is a sacrifice, not removal');
$check(!$has('SOR_095', 'buff') && !$has('SOR_239', 'buff'), 'keyword reminder text is not an effect (no buff from a Raid/Saboteur reminder)');
$check(SWUBotCardTags('NOT_A_CARD') === [], 'unknown card → no tags');
$check(SWUBotCardTags('SOR_095') === [], 'a vanilla unit (Battlefield Marine) → no tags');
$all = $GLOBALS['SWUBotCardTagTable'] ?? [];
$valid = ['removal', 'damage', 'draw', 'buff', 'bounce', 'exhaust', 'heal', 'wipe', 'burn'];   // 'burn': tags v2, owner OK 2026-09-14
$bad = [];
foreach ($all as $id => $tags) { foreach ($tags as $t) { if (!in_array($t, $valid, true)) $bad[] = "$id:$t"; } }
$check(count($all) > 100 && empty($bad), 'the generated table is populated and uses only the nine tags — ' . count($all) . ' cards, bad=' . json_encode(array_slice($bad, 0, 5)));

// ── Tags v2 (Phase 1b part 2, Task 5; diagnosis 2026-09-14: the Phase 1a patterns missed most removal and all
// burn, so the scorer valued Pre Vizsla like a vanilla 8-drop — cast 5 of 55 times when playable). Texts read
// with CardText() before use.
$check($has('SEC_078', 'wipe'), 'Hyperspace Disaster "Defeat all space units." → wipe');
$check($has('ASH_053', 'wipe'), 'Pre Vizsla "Defeat any number of non-leader units with a total of 6 or less remaining HP" → wipe');
$check($has('LAW_101', 'damage') && !$has('LAW_101', 'wipe'), 'Lawbringer "Give each enemy unit with that aspect -2/-2" → damage (enemy-only, like Seismic Detonation), not wipe');
$check($has('JTL_043', 'removal'), 'No Glory "Take control of a non-leader unit, then defeat it." → removal');
$check($has('LAW_131', 'removal') && $has('JTL_079', 'removal'), 'Incapacitate -2/-2 and Out the Airlock -5/-5 → removal');
$check($has('LOF_035', 'removal') && $has('LOF_070', 'removal'), '"you may give a unit -3/-3" (Talzin\'s Assassin, Anakin) → removal');
$check($has('LAW_132', 'removal'), 'The Tree Remembers "…If it costs 3 or less, defeat it." → removal');
$check($has('ASH_052', 'removal'), 'Chimaera "…an enemy non-leader unit. If you do, defeat those units." → removal');
$check($has('ASH_148', 'damage'), 'Ninth Sister "deal damage equal to its cost divided…" → damage');
$check(!$has('SOR_216', 'removal'), 'Disarm (-4/-0) defeats nothing → not removal');
foreach (['JTL_237', 'JTL_240', 'JTL_143', 'JTL_181'] as $burn) $check($has($burn, 'burn'), "$burn deals indirect damage → burn");
$check(!$has('SOR_095', 'burn') && SWUBotCardTags('SOR_095') === [], 'a vanilla unit still has no tags');
SWUBotSetDisabledFeatures(['tags2']);
$check(SWUBotCardTags('JTL_043') === [] && SWUBotCardTags('SOR_078') === ['removal'], '@no-tags2 reads the frozen Phase 1a table');
SWUBotSetDisabledFeatures([]);

bot_test_finish();
