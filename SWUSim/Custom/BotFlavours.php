<?php
// FLAVOUR REGISTRY (RL bots spec, Section 5 "Flavour profiles"): the owner's flavour tags per ARCHETYPE, keyed by
// leader + the base ("LEADER|BASE_ID", else "LEADER|Aspect", else "LEADER|*"), so any deck — not only the fixtures — gets its
// flavours in live play. A deck not listed has none and plays its style alone. Source: the owner's labels (spec,
// "Archetype vocabulary"), as tagged in SWUSim/Tests/BotFixtures/meta-2026-09/README.md (bot_flavours_test.php
// checks every fixture header against this table).
require_once __DIR__ . '/../Rl/CardTags.php';

const SWU_BOT_FLAVOURS = [
    'ASH_009|Vigilance' => ['ground', 'combo'],          // Ahsoka, Blue
    'ASH_009|Cunning'   => ['mixed-space'],              // Ahsoka, Yellow
    'JTL_009|*'         => ['burn'],                     // Boba Fett (JTL)
    'ASH_017|*'         => ['go-wide', 'mixed'],         // Greef Karga
    'JTL_006|*'         => ['space'],                    // Darth Vader (JTL)
    'LAW_004|*'         => ['hard'],                     // Aurra Sing
    'SEC_010|*'         => ['hard'],                     // Dedra Meero
    'LAW_008|*'         => ['credit-ramp'],              // Director Krennic
    'LAW_018|*'         => ['credit-ramp', 'tempo'],     // Lando (LAW)
    'JTL_005|*'         => ['capital-ship'],             // Admiral Piett — red (Normal) and blue (Control)
    'JTL_012|*'         => ['space', 'combo', 'pilot'],  // Luke (JTL)
    'LOF_009|*'         => ['tempo', 'force'],           // Darth Maul
    'LOF_002|*'         => ['tempo', 'force'],           // Mother Talzin
    // Second fixture batch (2026-09-15). A label that names a BASE is keyed by the base's CardID (owner: "the flavor
    // profiles should come from the leader/base combo"); one that names a colour, by the aspect.
    'ASH_001|JTL_028'   => ['go-tall', 'upgrades'],      // The Armorer, Nabat Village
    'LOF_008|LOF_019'   => ['go-wide', 'force', 'high-hp'], // Obi-Wan, Vergence Temple (not the 28-HP Force-base lists)
    'JTL_002|Cunning'   => ['combo', 'when-defeated'],   // Thrawn (JTL), Yellow
    'JTL_002|JTL_024'   => ['bombs'],                    // Thrawn (JTL), Data Vault
    'ASH_014|JTL_021'   => ['hard', 'combo'],            // The Mandalorian (ASH), Colossus
    'LAW_004|JTL_024'   => ['setup'],                    // Aurra Sing, Data Vault (the red list stays 'hard')
    'LAW_013|LAW_019'   => ['hyper', 'credit'],          // Chewbacca (LAW), Alliance Outpost
];

// Flavours that change the ARCHETYPE RANK rather than a single weight (feature 'flavourrank').
// ⚠ History: giving flavours teeth as weight MULTIPLIERS failed its gate at 49.8%, and 'tempo' changed literally zero
// games, because tempo is about SEQUENCING (when to take initiative, exhausting a blocker first) which the scorer
// cannot express (part-3 plan, "Flavour layer, attempt 1 — REVERTED"). A rank shift is a much larger lever: it moves
// the whole kill ladder a step, so midrange's "remove a 3-cost blocker" becomes soft control's "remove a 2-cost".
// It is gated separately so the fidelity sweep can attribute it.
const SWU_BOT_FLAVOUR_RANK_SHIFT = [
    'tempo' => 1,   // owner, 2026-09-17: "a tempo deck would trade. a normal midrange deck would hit base"
];

function SWUBotFlavourRankShift(int $seat): int {
    if (function_exists('SWUBotFeatureOn') && !SWUBotFeatureOn('flavourrank')) return 0;
    // No live board (unit tests, offline tooling): there are no deck flavours to read. A pure scoring
    // input must never require game state — SWUBotWeights() calls through here on every decision.
    if (!function_exists('GetLeader')) return 0;
    $shift = 0;
    foreach (SWUBotDeckFlavours($seat) as $f) $shift += intval(SWU_BOT_FLAVOUR_RANK_SHIFT[$f] ?? 0);
    return $shift;
}

function SWUBotDeckFlavours(int $seat): array {
    $leader = strval((GetLeader($seat)[0] ?? null)->CardID ?? '');
    $base = GetBase($seat)[0] ?? null;
    $baseID = $base !== null ? strval($base->CardID ?? '') : '';
    $aspect = $baseID !== '' ? strval(CardAspect($baseID) ?? '') : '';
    return SWU_BOT_FLAVOURS["$leader|$baseID"] ?? SWU_BOT_FLAVOURS["$leader|$aspect"] ?? SWU_BOT_FLAVOURS["$leader|*"] ?? [];
}

// A card this deck should not resource while it holds filler (feature 'keep'): its answers (removal, wipe), its
// reach (burn), and its flavour's key cards — a Capital Ship in a capital-ship deck.
function SWUBotIsKeyCard(int $seat, string $cardID): bool {
    if (array_intersect(SWUBotCardTags($cardID), ['removal', 'wipe', 'burn'])) return true;
    return in_array('capital-ship', SWUBotDeckFlavours($seat), true) && str_contains(strval(CardTrait($cardID) ?? ''), 'Capital Ship');
}

// REMOVAL CLASS of a removal EVENT, read from its PRINTED TEXT (owner, 2026-09-18, Q5.5). For proposal
// 'earlyremoval' (BotFeatures.php). Three classes, not two — see the ⚠ below.
//   'restricted' — the text caps what it can kill: "costs N or less", "N or less remaining HP", "N or less power".
//                  Owner: "use restricted removal for cheap stuff early on … late game, it's an auto resource."
//                  (Crushing Blow LOF_077, The Tree Remembers LAW_132, Takedown SOR_077, …)
//   'bombkiller' — defeats a unit with NO cap: "defeat a (non-leader) unit", "take control of a non-leader unit",
//                  -5/-5 or more, or "5 or more power". Owner: "save it for bombs." PREMIER-LEGAL ones (sets
//                  JTL/LOF/SEC/IBH/LAW/ASH/HMW, SWUFormatLegalSets('premier')): No Glory Only Results JTL_043,
//                  Lost and Forgotten LAW_133, Out the Airlock JTL_079, It's Worse LOF_264, Display Piece LAW_103.
//                  ⚠ Only the FIRST THREE appear in any 2026-09 fixture (No Glory 29 decks, Lost and Forgotten 23,
//                  Out the Airlock 1). Vanquish, Rival's Fall, Fell the Dragon and Lethal Crackdown are ROTATED
//                  (SOR/SHD/TWI). The classifier stays card-agnostic on purpose — other formats still play them.
//   'other'      — everything else: -2/-2 shrinks, "an opponent chooses", conditional targets (Direct Hit's
//                  "Vehicle", Get Lost's "upgraded"), attack tricks. Neutral: neither held nor pushed early.
// ⚠ "Flexible" is NOT one class. A -2/-2 (Incapacitate, Knowledge and Defense) has no restriction TEXT but can
// never kill a bomb, so "hold it for bombs" would be actively wrong for it. The owner's principle is "spend
// small-killers on small things, save bomb-killers for bombs"; 'other' is the honest bucket for the cards that
// are neither. Verified over all 37 removal events in the pool on 2026-09-18.
// Returns [class, capKind, capN] — capKind/capN are null unless 'restricted'.
function SWUBotRemovalClass(string $cardID): array {
    if (!str_contains(strval(CardType($cardID)), 'Event') || !in_array('removal', SWUBotCardTags($cardID), true)) return ['', null, null];
    $t = strval(CardText($cardID));
    if (preg_match('/costs? (\d+) or less/i', $t, $m)) return ['restricted', 'cost', intval($m[1])];
    if (preg_match('/(\d+) or less (remaining HP|HP)/i', $t, $m)) return ['restricted', 'remaining', intval($m[1])];
    if (preg_match('/(\d+) or less power/i', $t, $m)) return ['restricted', 'power', intval($m[1])];
    if (stripos($t, 'chooses') !== false) return ['other', null, null];                      // the victim picks
    if (preg_match('/take control of a non-leader unit/i', $t)) return ['bombkiller', null, null];
    // UNCONDITIONAL only: "…unit" must end the clause, or be followed by "with N or more power" (Fell the Dragon,
    // SHD_078 — rotated out of Premier, kept for other formats — it can ONLY hit bombs, so holding it is right). A trailing qualifier makes it
    // conditional: "a unit WITH power and remaining HP both equal to…" (One in a Million SEC_053), "a unit THAT
    // dealt damage to a base" (Retaliation SEC_077), "WITH power equal to or less than the chosen unit" (Nothing
    // Left to Fear LAW_041). A first cut matched "defeat a unit\b" and misfiled all three as bomb-killers.
    if (preg_match('/defeat (a|an)( enemy)?( non-leader)? unit(\.|,|$| with \d+ or more power)/im', $t)) return ['bombkiller', null, null];
    if (preg_match('/-(\d+)\/-\d+/', $t, $m) && intval($m[1]) >= 5) return ['bombkiller', null, null];
    return ['other', null, null];
}

// Would a RESTRICTED removal event find a legal ENEMY target right now? Honours the printed cap, "non-leader",
// and a named arena ("enemy space unit", JTL_055).
function SWUBotRestrictedRemovalHasTarget(int $seat, string $cardID): bool {
    [$cls, $kind, $n] = SWUBotRemovalClass($cardID);
    if ($cls !== 'restricted') return false;
    $t = strval(CardText($cardID));
    $nonLeader = stripos($t, 'non-leader') !== false;
    $arena = stripos($t, 'space unit') !== false ? 'Space' : (stripos($t, 'ground unit') !== false ? 'Ground' : '');
    foreach (SWUBotUnits(SWUBotOpponent($seat)) as $v) {
        if ($nonLeader && $v['isLeader']) continue;
        if ($arena !== '' && $v['arena'] !== $arena) continue;
        $val = $kind === 'cost' ? $v['cost'] : ($kind === 'remaining' ? $v['remaining'] : $v['power']);
        if ($val <= $n) return true;
    }
    return false;
}

// The most expensive enemy unit THIS removal event can legally hit — what a bomb-killer would be spent on.
// 0 on an empty board. A deployed LEADER unit counts unless the card says "non-leader" (owner, 2026-09-18:
// "non-leader unit is not a big restriction … most units are non-leader units" — so it is a TARGETING filter,
// not a class restriction, and cards WITHOUT it can hit a deployed leader). Out the Airlock JTL_079 ("-5/-5 to a
// unit") can; No Glory and Lost and Forgotten ("non-leader unit") cannot.
// ⚠ SCOPE, measured 2026-09-18: in current PREMIER this matters for exactly ONE card in ONE fixture — the two
// bomb-killers that dominate the pool (No Glory, Lost and Forgotten) both say "non-leader". Rival's Fall SHD_079,
// the example this was first written around, is rotated out of Premier. It still matters in formats where SHD/
// TWI are legal. The leader-killers Premier ACTUALLY plays are WIPES (Hyperspace Disaster SEC_078, Single Reactor
// Ignition LAW_044) and unit abilities (Annihilator JTL_041), none of which are removal EVENTS — see the
// OTMTCGE memory `control-loses-by-not-reaching-round-8`.
// Owner 5.8: "if the leader is deployed as a pilot or as a unit, then board wipe is very valuable to remove their
// leader unit" — so a leader unit is bomb-worthy IN EITHER FORM, whatever its card costs.
// ⚠ Its card cost cannot be trusted for this. A leader deployed as a UNIT reads 5-6 (the leader card), but a
// PILOT leader makes the SHIP it rides a leader unit (IsLeaderUnit, KeywordEffects.php — "Attached unit is a
// leader unit"), and the view's 'cost' is the SHIP's. Pilot-Vader (JTL) on a 3-cost ship would read as cost 3 —
// "cheap" — and the bomb-killer would be held against the very threat it exists for. Owner, Q13, confirms such
// a unit is a leader unit: The Tree Remembers "can be used on leader units if they have a pilot leader on a
// cheap ship". Two earlier cuts got this wrong: the first ignored leaders entirely, the second counted them at
// card cost and so still missed every pilot leader on a cheap ship.
const SWU_BOT_LEADER_TARGET_WORTH = 6;
function SWUBotBestEnemyTargetCost(int $seat, string $cardID = ''): int {
    $leadersOk = $cardID !== '' && stripos(strval(CardText($cardID)), 'non-leader') === false;
    $best = 0;
    foreach (SWUBotUnits(SWUBotOpponent($seat)) as $v) {
        if ($v['isLeader']) {
            if ($leadersOk) $best = max($best, SWU_BOT_LEADER_TARGET_WORTH, $v['cost']);
            continue;
        }
        $best = max($best, $v['cost']);
    }
    return $best;
}

// Would this BOMB-KILLER actually defeat enemy unit $v? "non-leader" is a targeting filter (owner, 2026-09-18),
// and a -N/-N card only kills a unit with N or less remaining HP — Out the Airlock JTL_079 (-5/-5) cannot kill a
// 6-HP unit, so a 6-HP unit must not count as something it removes. Unconditional defeat / take control: yes.
function SWUBotRemovalKills(string $cardID, array $v): bool {
    $t = strval(CardText($cardID));
    if ($v['isLeader'] && stripos($t, 'non-leader') !== false) return false;
    if (preg_match('/-(\d+)\/-\d+/', $t, $m)) return intval($v['remaining']) <= intval($m[1]);
    return true;
}

// PROPOSAL 'threathold' — the threat-aware HOLD. Owner ruling 2026-09-18: "the bot should analyze better when a
// threat is present and if it's not really considered a bomb, it can still use removal if that would be the best
// way to mitigate damage to base." Replaces earlyremoval's cost-only hold, which measured −22 (p=0.011) because
// against aggro — no bombs — it held No Glory and Lost and Forgotten forever.
// HOLD only when EVERY enemy unit this card would kill is BOTH cheap (cost ≤ 3, a leader counts as 6) AND
// low-threat (< 3 base damage per round). Threat 3 is derived from the owner's damage budget: 3 power reaching the
// base from round 3 to 6 is ~12 damage, ~40% of a 30-HP base, which breaks the 50-60% budget alongside anything
// else; 2 power is ~8 (~27%).
const SWU_BOT_THREAT_WORTH = 3;
function SWUBotShouldHoldBombKiller(int $seat, string $cardID): bool {
    foreach (SWUBotUnits(SWUBotOpponent($seat)) as $v) {
        if (!SWUBotRemovalKills($cardID, $v)) continue;
        $cost = $v['isLeader'] ? SWU_BOT_LEADER_TARGET_WORTH : intval($v['cost']);
        if ($cost > SWU_BOT_CHEAP_TARGET_COST) return false;                          // a bomb-worthy target
        if (SWUBotUnitBaseThreat($seat, $v) >= SWU_BOT_THREAT_WORTH) return false;    // a real damage threat
    }
    return true;   // only cheap, harmless targets (or none) — save it
}

// The cards this seat owns and can see (deck, hand, resources, discard, non-leader non-token units in play): the
// deck's composition, for the resource stop.
function SWUBotOwnCardIDs(int $seat): array {
    $ids = [];
    foreach ([GetDeck($seat), GetHand($seat), GetResources($seat), GetDiscard($seat)] as $zone) {
        foreach ($zone as $o) { if ($o !== null && empty($o->removed)) $ids[] = strval($o->CardID ?? ''); }
    }
    foreach (SWUBotUnits($seat) as $v) { if (!$v['isLeader'] && $v['cost'] > 0) $ids[] = $v['cardID']; }
    return $ids;
}
